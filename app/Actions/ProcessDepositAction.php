<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Bucket;
use App\Models\Deposit;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

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
        $month = $deposit->deposit_date->format('Y-m');
        $monthStart = $deposit->deposit_date->copy()->startOfMonth();
        $monthEnd = $deposit->deposit_date->copy()->endOfMonth();

        $fixedBuckets = Bucket::where('type', Bucket::TYPE_FIXED)
            ->orderBy('priority_order', 'asc')
            ->get();

        foreach ($fixedBuckets as $bucket) {
            if ($remaining <= 0) {
                break;
            }

            $alreadyFunded = Transaction::where('bucket_id', $bucket->id)
                ->where('type', Transaction::TYPE_ALLOCATION)
                ->whereHas('deposit', function ($query) use ($monthStart, $monthEnd) {
                    $query->whereBetween('deposit_date', [$monthStart, $monthEnd]);
                })
                ->sum('amount');

            $remainingNeed = $bucket->monthly_target - (int) $alreadyFunded;

            if ($remainingNeed <= 0) {
                continue;
            }

            $allocation = min($remainingNeed, $remaining);

            Transaction::create([
                'bucket_id' => $bucket->id,
                'deposit_id' => $deposit->id,
                'amount' => $allocation,
                'type' => Transaction::TYPE_ALLOCATION,
                'description' => "Allocation to {$bucket->name}",
            ]);

            $remaining -= $allocation;
        }

        return $remaining;
    }

    private function distributeExcess(Deposit $deposit, int $remaining): void
    {
        $excessBuckets = Bucket::where('type', Bucket::TYPE_EXCESS)
            ->whereNotNull('excess_percentage')
            ->get();

        if ($excessBuckets->isEmpty()) {
            return;
        }

        $totalPercentage = $excessBuckets->sum('excess_percentage');
        $overflow = 0;
        $allocations = [];

        // First pass: calculate raw shares, apply caps
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

        // Find the primary savings bucket (uncapped excess bucket, prefer "Savings" name)
        $savingsBucket = $excessBuckets->first(fn (Bucket $b) => $b->cap === null);

        // Add overflow to savings bucket
        if ($savingsBucket && $overflow > 0) {
            $allocations[$savingsBucket->id] += $overflow;
        }

        // Handle rounding remainder: total allocated so far vs remaining
        $totalAllocated = array_sum($allocations);
        $roundingRemainder = $remaining - $totalAllocated;

        if ($roundingRemainder > 0 && $savingsBucket) {
            $allocations[$savingsBucket->id] += $roundingRemainder;
        }

        // Create transactions
        foreach ($allocations as $bucketId => $amount) {
            if ($amount <= 0) {
                continue;
            }

            $bucket = $excessBuckets->firstWhere('id', $bucketId);

            Transaction::create([
                'bucket_id' => $bucketId,
                'deposit_id' => $deposit->id,
                'amount' => $amount,
                'type' => Transaction::TYPE_ALLOCATION,
                'description' => "Excess allocation to {$bucket->name}",
            ]);
        }
    }
}
