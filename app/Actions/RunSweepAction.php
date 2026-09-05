<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Bucket;
use App\Models\Period;
use App\Models\PeriodBucketSnapshot;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class RunSweepAction
{
    /** @return array<int, array{bucket: string, amount: int}> */
    public function execute(?string $month = null): array
    {
        $sweepMonth = $month
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $period = Period::where('month', $sweepMonth)->first();

        if ($period && $period->closed_at !== null) {
            throw new RuntimeException(
                "{$sweepMonth->format('F Y')} has already been closed. Cannot sweep the same month twice."
            );
        }

        $primarySavings = Bucket::where('is_primary_savings', true)->first();

        if (!$primarySavings) {
            throw new RuntimeException(
                'No primary savings bucket designated. Mark one bucket with is_primary_savings = true.'
            );
        }

        $sweepableBuckets = Bucket::where('sweeps_excess', true)->get();
        $results = [];

        DB::transaction(function () use ($sweepableBuckets, $primarySavings, $sweepMonth, &$results) {
            $sweepReferenceId = Str::uuid()->toString();
            $sweptAt = $sweepMonth->copy()->endOfMonth();

            // Phase 1: Collect all sweep funds
            $totalSweepPool = 0;
            $sweepSources = [];

            foreach ($sweepableBuckets as $bucket) {
                $balance = $bucket->balance;

                if ($balance <= 0) {
                    continue;
                }

                $this->createSweepTransaction([
                    'bucket_id' => $bucket->id,
                    'amount' => -$balance,
                    'type' => Transaction::TYPE_SWEEP,
                    'reference_id' => $sweepReferenceId,
                    'description' => "End-of-month sweep from {$bucket->name}",
                ], $sweptAt);

                $sweepSources[] = ['bucket' => $bucket->name, 'amount' => $balance];
                $totalSweepPool += $balance;
            }

            if ($totalSweepPool === 0) {
                return;
            }

            // Phase 2: Distribute to sweep-receive buckets by priority
            $receiveTargets = Bucket::where('receives_sweeps', true)
                ->orderBy('priority_order')
                ->get();

            $remaining = $totalSweepPool;

            foreach ($receiveTargets as $target) {
                if ($remaining <= 0) {
                    break;
                }

                $currentBalance = $target->balance;

                if ($target->cap !== null) {
                    $room = $target->cap - $currentBalance;

                    if ($room <= 0) {
                        continue;
                    }

                    $allocate = min($remaining, $room);
                } else {
                    $allocate = $remaining;
                }

                $this->createSweepTransaction([
                    'bucket_id' => $target->id,
                    'amount' => $allocate,
                    'type' => Transaction::TYPE_SWEEP,
                    'reference_id' => $sweepReferenceId,
                    'description' => "Sweep receive into {$target->name}",
                ], $sweptAt);

                $remaining -= $allocate;
            }

            // Phase 3: Remainder to primary savings
            if ($remaining > 0) {
                $this->createSweepTransaction([
                    'bucket_id' => $primarySavings->id,
                    'amount' => $remaining,
                    'type' => Transaction::TYPE_SWEEP,
                    'reference_id' => $sweepReferenceId,
                    'description' => 'Sweep remainder to primary savings',
                ], $sweptAt);
            }

            foreach ($sweepSources as $source) {
                $results[] = ['bucket' => $source['bucket'], 'amount' => $source['amount']];
            }
        });

        // Snapshot total balance across all buckets and close the period
        $closingBalance = (int) Bucket::withSum('transactions', 'amount')
            ->get()
            ->sum('transactions_sum_amount');

        if (!$period) {
            $period = Period::create(['month' => $sweepMonth]);
        }
        $period->update([
            'closed_at' => Carbon::now(),
            'closing_balance' => $closingBalance,
        ]);

        // Create per-bucket snapshots for historical breakdown
        $this->createBucketSnapshots($period, $sweepMonth);

        return $results;
    }

    /**
     * Sweep transactions are stamped with the last day of the month being closed rather than
     * the moment the sweep was run, so they land in the period they belong to even when the
     * sweep happens weeks later.
     *
     * @param array<string, mixed> $attributes
     */
    private function createSweepTransaction(array $attributes, Carbon $sweptAt): void
    {
        $txn = new Transaction($attributes);
        $txn->timestamps = false;
        $txn->created_at = $sweptAt;
        $txn->save();
    }

    private function createBucketSnapshots(Period $period, Carbon $sweepMonth): void
    {
        $monthStart = $sweepMonth->copy()->startOfMonth();
        $monthEnd = $sweepMonth->copy()->endOfMonth();

        $allBuckets = Bucket::all();

        foreach ($allBuckets as $bucket) {
            $funded = (int) Transaction::where('bucket_id', $bucket->id)
                ->where('type', Transaction::TYPE_ALLOCATION)
                ->whereHas('deposit', fn ($q) => $q->whereBetween('deposit_date', [$monthStart, $monthEnd]))
                ->sum('amount');

            $paid = (int) abs(Transaction::where('bucket_id', $bucket->id)
                ->where('type', Transaction::TYPE_EXPENSE)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('amount'));

            $swept = (int) abs(Transaction::where('bucket_id', $bucket->id)
                ->where('type', Transaction::TYPE_SWEEP)
                ->where('amount', '<', 0)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('amount'));

            PeriodBucketSnapshot::create([
                'period_id' => $period->id,
                'bucket_id' => $bucket->id,
                'monthly_target' => $bucket->monthly_target ?? 0,
                'funded' => $funded,
                'paid' => $paid,
                'swept' => $swept,
                'closing_balance' => $bucket->balance,
            ]);
        }
    }
}
