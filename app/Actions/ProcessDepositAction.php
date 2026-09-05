<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Bucket;
use App\Models\Deposit;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProcessDepositAction
{
    public function execute(Deposit $deposit): void
    {
        $remaining = $deposit->amount;

        if ($remaining <= 0) {
            return;
        }

        DB::transaction(function () use ($deposit, &$remaining) {
            $remaining = $this->fillFixedBuckets($deposit, $remaining);

            if ($remaining > 0) {
                $this->distributeExcess($deposit, $remaining);
            }
        });
    }

    private function fillFixedBuckets(Deposit $deposit, int $remaining): int
    {
        $monthStart = $deposit->deposit_date->copy()->startOfMonth();
        $monthEnd = $deposit->deposit_date->copy()->endOfMonth();

        $fixedBuckets = Bucket::where('type', Bucket::TYPE_FIXED)
            ->orderBy('priority_order', 'asc')
            ->get();

        foreach ($fixedBuckets as $bucket) {
            if ($remaining <= 0) {
                break;
            }

            $alreadyFunded = (int) Transaction::where('bucket_id', $bucket->id)
                ->where('type', Transaction::TYPE_ALLOCATION)
                ->whereHas('deposit', function ($query) use ($monthStart, $monthEnd) {
                    $query->whereBetween('deposit_date', [$monthStart, $monthEnd]);
                })
                ->sum('amount');

            $remainingNeed = $bucket->monthly_target - $alreadyFunded;

            if ($bucket->cap !== null) {
                $currentBalance = $bucket->balance;
                $roomUnderCap = max(0, $bucket->cap - $currentBalance);
                $remainingNeed = min($remainingNeed, $roomUnderCap);
            }

            if ($remainingNeed <= 0) {
                continue;
            }

            $allocation = min($remainingNeed, $remaining);

            $this->createAllocation($deposit, $bucket->id, $allocation, "Allocation to {$bucket->name}");

            $remaining -= $allocation;
        }

        return $remaining;
    }

    private function distributeExcess(Deposit $deposit, int $remaining): void
    {
        $primarySavings = Bucket::where('is_primary_savings', true)->first();

        if (!$primarySavings) {
            throw new RuntimeException(
                'No primary savings bucket designated. Cannot allocate excess funds. '
                . 'Mark one bucket with is_primary_savings = true.'
            );
        }

        $excessBuckets = Bucket::where('type', Bucket::TYPE_EXCESS)
            ->whereNotNull('excess_percentage')
            ->get();

        $totalPercentage = $excessBuckets->sum('excess_percentage');

        if ($totalPercentage <= 0) {
            $this->createAllocation(
                $deposit,
                $primarySavings->id,
                $remaining,
                "Excess allocation to {$primarySavings->name}"
            );
            return;
        }

        $overflow = 0;
        $allocations = [];

        foreach ($excessBuckets as $bucket) {
            $share = (int) floor($remaining * $bucket->excess_percentage / $totalPercentage);

            if ($bucket->cap !== null) {
                $currentBalance = $bucket->balance;
                $room = max(0, $bucket->cap - $currentBalance);
                $actual = min($share, $room);
                $overflow += $share - $actual;
                $allocations[$bucket->id] = $actual;
            } else {
                $allocations[$bucket->id] = $share;
            }
        }

        // Route overflow to primary savings
        if ($overflow > 0) {
            $allocations[$primarySavings->id] = ($allocations[$primarySavings->id] ?? 0) + $overflow;
        }

        // Route rounding remainder to primary savings
        $totalAllocated = array_sum($allocations);
        $roundingRemainder = $remaining - $totalAllocated;

        if ($roundingRemainder > 0) {
            $allocations[$primarySavings->id] = ($allocations[$primarySavings->id] ?? 0) + $roundingRemainder;
        }

        foreach ($allocations as $bucketId => $amount) {
            if ($amount <= 0) {
                continue;
            }

            $bucket = $excessBuckets->firstWhere('id', $bucketId) ?? $primarySavings;

            $this->createAllocation($deposit, $bucketId, $amount, "Excess allocation to {$bucket->name}");
        }
    }

    /**
     * Create an allocation transaction stamped with the deposit's date so it falls
     * in the same month as the deposit it relates to (regardless of when the user
     * actually entered the deposit).
     */
    private function createAllocation(Deposit $deposit, int $bucketId, int $amount, string $description): void
    {
        $depositDate = $deposit->deposit_date instanceof Carbon
            ? $deposit->deposit_date
            : Carbon::parse($deposit->deposit_date);

        $now = Carbon::now();

        // If the deposit is dated today, preserve the current time so chronology is correct.
        // Otherwise, use end-of-day on the deposit date so allocations sort after any
        // other activity recorded earlier that day.
        $createdAt = $depositDate->isSameDay($now)
            ? $now
            : $depositDate->copy()->endOfDay();

        $txn = new Transaction([
            'bucket_id' => $bucketId,
            'deposit_id' => $deposit->id,
            'amount' => $amount,
            'type' => Transaction::TYPE_ALLOCATION,
            'description' => $description,
        ]);
        $txn->timestamps = false;
        $txn->created_at = $createdAt;
        $txn->save();
    }
}
