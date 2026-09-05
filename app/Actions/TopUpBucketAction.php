<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Bucket;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Moves exactly enough out of primary savings to bring an overdrawn bucket back to
 * zero. Recorded as an ordinary paired transfer so it reads as one in the audit
 * trail rather than inventing a new ledger type.
 */
class TopUpBucketAction
{
    /**
     * @return int the amount moved, in cents
     */
    public function execute(Bucket $bucket): int
    {
        $savings = Bucket::where('is_primary_savings', true)->first();

        if (!$savings) {
            throw new RuntimeException(
                'No primary savings bucket is designated, so there is nowhere to take the money from.'
            );
        }

        if ($savings->is($bucket)) {
            throw new RuntimeException(
                "{$bucket->name} is the primary savings bucket, so it cannot top itself up. Transfer from another bucket instead."
            );
        }

        $shortfall = -$bucket->balance;

        if ($shortfall <= 0) {
            throw new RuntimeException("{$bucket->name} is not in the red, so there is nothing to top up.");
        }

        $referenceId = Str::uuid()->toString();
        $description = "Top up {$bucket->name} from {$savings->name}";

        DB::transaction(function () use ($bucket, $savings, $shortfall, $referenceId, $description) {
            Transaction::create([
                'bucket_id' => $savings->id,
                'amount' => -$shortfall,
                'type' => Transaction::TYPE_TRANSFER,
                'balance_type' => Transaction::BALANCE_TOTAL,
                'reference_id' => $referenceId,
                'description' => $description,
            ]);

            Transaction::create([
                'bucket_id' => $bucket->id,
                'amount' => $shortfall,
                'type' => Transaction::TYPE_TRANSFER,
                'balance_type' => Transaction::BALANCE_TOTAL,
                'reference_id' => $referenceId,
                'description' => $description,
            ]);
        });

        return $shortfall;
    }
}
