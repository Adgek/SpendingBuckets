<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Period extends Model
{
    protected $fillable = [
        'month',
        'closed_at',
        'closing_balance',
    ];

    protected $casts = [
        'month' => 'date',
        'closed_at' => 'datetime',
        'closing_balance' => 'integer',
    ];

    public function bucketSnapshots(): HasMany
    {
        return $this->hasMany(PeriodBucketSnapshot::class);
    }

    /**
     * Returns a collection of sweep events that occurred during this period.
     * Each event has the form:
     *   [
     *     'reference_id' => string|null,
     *     'occurred_at'  => Carbon,
     *     'sources'      => Collection<int, ['bucket' => string, 'amount' => int]>,
     *     'destinations' => Collection<int, ['bucket' => string, 'amount' => int]>,
     *     'total'        => int,
     *   ]
     */
    public function sweepEvents(): Collection
    {
        $monthStart = $this->month->copy()->startOfMonth();
        $monthEnd = $this->month->copy()->endOfMonth();

        $sweepTxns = Transaction::with('bucket')
            ->where('type', Transaction::TYPE_SWEEP)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->orderBy('created_at')
            ->get();

        return $sweepTxns
            ->groupBy('reference_id')
            ->map(function (Collection $group) {
                $sources = $group->where('amount', '<', 0)->map(fn ($t) => [
                    'bucket' => $t->bucket->name ?? 'Deleted bucket',
                    'amount' => abs((int) $t->amount),
                ])->values();

                $destinations = $group->where('amount', '>', 0)->map(fn ($t) => [
                    'bucket' => $t->bucket->name ?? 'Deleted bucket',
                    'amount' => (int) $t->amount,
                ])->values();

                return [
                    'reference_id' => $group->first()->reference_id,
                    'occurred_at' => $group->first()->created_at,
                    'sources' => $sources,
                    'destinations' => $destinations,
                    'total' => (int) $sources->sum('amount'),
                ];
            })
            ->values();
    }
}
