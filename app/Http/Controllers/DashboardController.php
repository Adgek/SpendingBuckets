<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Bucket;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public const PAYCHECKS_PER_MONTH = 4;

    public function __invoke(): View
    {
        $now = Carbon::now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $totalMonthlyTarget = (int) Bucket::where('type', Bucket::TYPE_FIXED)->sum('monthly_target');

        $totalFundedThisMonth = (int) Transaction::where('type', Transaction::TYPE_ALLOCATION)
            ->whereHas('deposit', fn ($q) => $q->whereBetween('deposit_date', [$currentMonthStart, $currentMonthEnd]))
            ->sum('amount');

        $totalFundedLastMonth = (int) Transaction::where('type', Transaction::TYPE_ALLOCATION)
            ->whereHas('deposit', fn ($q) => $q->whereBetween('deposit_date', [$lastMonthStart, $lastMonthEnd]))
            ->sum('amount');

        $perPaycheck = (int) round($totalMonthlyTarget / self::PAYCHECKS_PER_MONTH);

        $currentMonthLabel = $now->format('F Y');
        $lastMonthLabel = $now->copy()->subMonth()->format('F Y');

        $buckets = Bucket::where('type', Bucket::TYPE_FIXED)
            ->orderBy('priority_order')
            ->addSelect(['funded_this_month' => Transaction::selectRaw('COALESCE(SUM(transactions.amount), 0)')
                ->whereColumn('transactions.bucket_id', 'buckets.id')
                ->where('transactions.type', Transaction::TYPE_ALLOCATION)
                ->whereHas('deposit', fn ($q) => $q->whereBetween('deposit_date', [$currentMonthStart, $currentMonthEnd]))
            ])
            ->get();

        return view('dashboard', compact(
            'currentMonthLabel',
            'lastMonthLabel',
            'totalMonthlyTarget',
            'totalFundedThisMonth',
            'totalFundedLastMonth',
            'perPaycheck',
            'buckets',
        ));
    }
}
