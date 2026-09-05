<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Deposit;
use App\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * Builds a single chronological feed out of the four things that move money:
 * deposits, expenses, transfers and sweeps. Transfers and sweeps are paired back
 * up by `reference_id` so one real-world event reads as one row.
 */
class ActivityFeedService
{
    public const TYPE_ALL = 'all';
    public const TYPE_DEPOSITS = 'deposits';
    public const TYPE_EXPENSES = 'expenses';
    public const TYPE_TRANSFERS = 'transfers';
    public const TYPE_SWEEPS = 'sweeps';

    public const TYPES = [
        self::TYPE_ALL,
        self::TYPE_DEPOSITS,
        self::TYPE_EXPENSES,
        self::TYPE_TRANSFERS,
        self::TYPE_SWEEPS,
    ];

    /**
     * Normalise a user-supplied filter, falling back to "all".
     */
    public static function normaliseType(?string $type): string
    {
        return in_array($type, self::TYPES, true) ? $type : self::TYPE_ALL;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function entries(string $type = self::TYPE_ALL, ?int $limit = null): Collection
    {
        $type = self::normaliseType($type);

        $entries = new Collection();

        if ($type === self::TYPE_ALL || $type === self::TYPE_DEPOSITS) {
            $entries = $entries->concat($this->deposits());
        }

        if ($type === self::TYPE_ALL || $type === self::TYPE_EXPENSES) {
            $entries = $entries->concat($this->expenses());
        }

        if ($type === self::TYPE_ALL || $type === self::TYPE_TRANSFERS) {
            $entries = $entries->concat($this->paired(Transaction::TYPE_TRANSFER));
        }

        if ($type === self::TYPE_ALL || $type === self::TYPE_SWEEPS) {
            $entries = $entries->concat($this->paired(Transaction::TYPE_SWEEP));
        }

        $entries = $entries
            ->sortByDesc(fn (array $entry) => $entry['sort_at']->getTimestamp())
            ->values();

        return $limit === null ? $entries : $entries->take($limit)->values();
    }

    /**
     * Roll a set of ledger legs up into one line per bucket, so a sweep that drained
     * three buckets into savings reads as three rows in and one row out.
     *
     * @param  Collection<int, Transaction>  $transactions
     * @return Collection<int, array{bucket: string, amount: int}>
     */
    private function byBucket(Collection $transactions): Collection
    {
        return $transactions
            ->groupBy(fn (Transaction $t) => $t->bucket->name ?? 'Deleted bucket')
            ->map(fn (Collection $group, string $bucket) => [
                'bucket' => $bucket,
                'amount' => abs((int) $group->sum('amount')),
            ])
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function deposits(): Collection
    {
        return Deposit::with(['transactions.bucket'])
            ->get()
            ->map(fn (Deposit $deposit) => [
                'kind' => 'deposit',
                'occurred_at' => $deposit->deposit_date,
                'sort_at' => $deposit->deposit_date->copy()->endOfDay(),
                'title' => 'Deposit',
                'description' => $deposit->description,
                'amount' => (int) $deposit->amount,
                'deposit' => $deposit,
                'transactions' => $deposit->transactions,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function expenses(): Collection
    {
        return Transaction::with('bucket')
            ->where('type', Transaction::TYPE_EXPENSE)
            ->get()
            ->map(fn (Transaction $transaction) => [
                'kind' => 'expense',
                'occurred_at' => $transaction->created_at,
                'sort_at' => $transaction->created_at,
                'title' => $transaction->bucket->name ?? 'Deleted bucket',
                'description' => $transaction->description,
                'amount' => abs((int) $transaction->amount),
                'transaction' => $transaction,
            ]);
    }

    /**
     * Transfers and sweeps are stored as two (or more) ledger rows sharing a
     * `reference_id`. Rows without one predate that convention, so they stand alone.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function paired(string $transactionType): Collection
    {
        return Transaction::with('bucket')
            ->where('type', $transactionType)
            ->get()
            ->groupBy(fn (Transaction $transaction) => $transaction->reference_id ?? 'unpaired-' . $transaction->id)
            ->map(function (Collection $group) use ($transactionType) {
                $sources = $this->byBucket($group->where('amount', '<', 0));
                $destinations = $this->byBucket($group->where('amount', '>', 0));

                $first = $group->first();

                return [
                    'kind' => $transactionType === Transaction::TYPE_SWEEP ? 'sweep' : 'transfer',
                    'occurred_at' => $first->created_at,
                    'sort_at' => $first->created_at,
                    'title' => $transactionType === Transaction::TYPE_SWEEP ? 'Sweep' : 'Transfer',
                    'description' => $first->description,
                    'amount' => (int) max($sources->sum('amount'), $destinations->sum('amount')),
                    'from' => $sources->count() === 1 ? $sources->first()['bucket'] : null,
                    'to' => $destinations->count() === 1 ? $destinations->first()['bucket'] : null,
                    'sources' => $sources,
                    'destinations' => $destinations,
                    'balance_type' => $first->balance_type ?? null,
                ];
            })
            ->values();
    }
}
