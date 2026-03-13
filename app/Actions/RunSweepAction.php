<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Bucket;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class RunSweepAction
{
    /** @return array<int, array{bucket: string, amount: int}> */
    public function execute(): array
    {
        $primarySavings = Bucket::where('is_primary_savings', true)->first();

        if (!$primarySavings) {
            throw new RuntimeException(
                'No primary savings bucket designated. Mark one bucket with is_primary_savings = true.'
            );
        }

        $sweepableBuckets = Bucket::where('sweeps_excess', true)->get();
        $results = [];

        DB::transaction(function () use ($sweepableBuckets, $primarySavings, &$results) {
            foreach ($sweepableBuckets as $bucket) {
                $balance = $bucket->balance;

                if ($balance <= 0) {
                    continue;
                }

                $referenceId = Str::uuid()->toString();

                Transaction::create([
                    'bucket_id' => $bucket->id,
                    'amount' => -$balance,
                    'type' => Transaction::TYPE_SWEEP,
                    'reference_id' => $referenceId,
                    'description' => "End-of-month sweep from {$bucket->name}",
                ]);

                Transaction::create([
                    'bucket_id' => $primarySavings->id,
                    'amount' => $balance,
                    'type' => Transaction::TYPE_SWEEP,
                    'reference_id' => $referenceId,
                    'description' => "End-of-month sweep from {$bucket->name}",
                ]);

                $results[] = ['bucket' => $bucket->name, 'amount' => $balance];
            }
        });

        return $results;
    }
}
