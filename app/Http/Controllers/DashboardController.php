<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Bucket;
use App\Models\IncomeSource;
use App\Models\Transaction;
use App\Services\ActivePeriodService;
use App\Services\ActivityFeedService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public const PAYCHECKS_PER_MONTH = 4;

    public const RECENT_ACTIVITY_LIMIT = 8;

    public function __invoke(
        Request $request,
        ActivePeriodService $periodService,
        ActivityFeedService $activityFeed,
    ): View
    {
        $viewingMonth = $request->query('month');
        $isHistorical = false;

        if ($viewingMonth && preg_match('/^\d{4}-\d{2}$/', $viewingMonth)) {
            $periodStart = Carbon::createFromFormat('Y-m', $viewingMonth)->startOfMonth();
            $isHistorical = true;
        } else {
            $periodStart = $periodService->current();
        }

        $periodEnd = $periodStart->copy()->endOfMonth();
        $previousMonth = $periodStart->copy()->subMonth();
        $previousMonthStart = $previousMonth->copy()->startOfMonth();
        $previousMonthEnd = $previousMonth->copy()->endOfMonth();

        $totalMonthlyTarget = (int) Bucket::where('type', Bucket::TYPE_FIXED)->sum('monthly_target');

        $totalFundedThisMonth = (int) Transaction::where('type', Transaction::TYPE_ALLOCATION)
            ->whereHas('deposit', fn ($q) => $q->whereBetween('deposit_date', [$periodStart, $periodEnd]))
            ->sum('amount');

        $totalFundedLastMonth = (int) Transaction::where('type', Transaction::TYPE_ALLOCATION)
            ->whereHas('deposit', fn ($q) => $q->whereBetween('deposit_date', [$previousMonthStart, $previousMonthEnd]))
            ->sum('amount');

        $otherIncome = IncomeSource::monthlyTotal();

        // Gross is the raw target split four ways; per-paycheck is what is left for the
        // paychecks to cover once other income has been counted.
        $grossPerPaycheck = (int) round($totalMonthlyTarget / self::PAYCHECKS_PER_MONTH);
        $perPaycheck = (int) round(max(0, $totalMonthlyTarget - $otherIncome) / self::PAYCHECKS_PER_MONTH);

        $recentActivity = $activityFeed->entries(ActivityFeedService::TYPE_ALL, self::RECENT_ACTIVITY_LIMIT);

        $currentMonthLabel = $periodStart->format('F Y');
        $lastMonthLabel = $previousMonth->format('F Y');

        $buckets = Bucket::where('type', Bucket::TYPE_FIXED)
            ->orderBy('priority_order')
            ->addSelect(['funded_this_month' => Transaction::selectRaw('COALESCE(SUM(transactions.amount), 0)')
                ->whereColumn('transactions.bucket_id', 'buckets.id')
                ->where('transactions.type', Transaction::TYPE_ALLOCATION)
                ->whereHas('deposit', fn ($q) => $q->whereBetween('deposit_date', [$periodStart, $periodEnd]))
            ])
            ->get();

        return view('dashboard', compact(
            'currentMonthLabel',
            'lastMonthLabel',
            'totalMonthlyTarget',
            'totalFundedThisMonth',
            'totalFundedLastMonth',
            'perPaycheck',
            'grossPerPaycheck',
            'otherIncome',
            'recentActivity',
            'buckets',
            'isHistorical',
        ));
    }
}
