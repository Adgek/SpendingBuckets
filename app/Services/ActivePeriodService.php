<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Deposit;
use App\Models\Period;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ActivePeriodService
{
    /**
     * The active period is the earliest unclosed period.
     * If no periods exist, falls back to the current calendar month.
     * Never exceeds the current calendar month.
     */
    public function current(): Carbon
    {
        $this->ensurePeriods();

        $unclosed = Period::whereNull('closed_at')
            ->orderBy('month')
            ->first();

        if ($unclosed) {
            $currentMonth = Carbon::now()->startOfMonth();
            return $unclosed->month->gt($currentMonth) ? $currentMonth : $unclosed->month->copy()->startOfMonth();
        }

        return Carbon::now()->startOfMonth();
    }

    /**
     * Returns all closed periods, most recent first.
     *
     * @return Collection<int, Period>
     */
    public function closedPeriods(): Collection
    {
        return Period::whereNotNull('closed_at')
            ->orderByDesc('month')
            ->get();
    }

    /**
     * Ensure period records exist from the first deposit month through the current calendar month.
     */
    public function ensurePeriods(): void
    {
        $earliestDeposit = Deposit::orderBy('deposit_date')->value('deposit_date');

        if (!$earliestDeposit) {
            return;
        }

        $start = Carbon::parse($earliestDeposit)->startOfMonth();
        $end = Carbon::now()->startOfMonth();

        $existing = Period::pluck('month')
            ->map(fn ($m) => Carbon::parse($m)->format('Y-m'))
            ->toArray();

        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            if (!in_array($cursor->format('Y-m'), $existing)) {
                Period::create(['month' => $cursor->copy()->startOfMonth()]);
            }
            $cursor->addMonth();
        }
    }
}
