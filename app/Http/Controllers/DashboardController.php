<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Bucket;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $now = Carbon::now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $totalMonthlyTarget = (int) Bucket::where('type', Bucket::TYPE_FIXED)->sum('monthly_target');

        $totalFundedThisMonth = (int) Transaction::where('type', Transaction::TYPE_ALLOCATION)
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->sum('amount');

        $totalFundedLastMonth = (int) Transaction::where('type', Transaction::TYPE_ALLOCATION)
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('amount');

        $perPaycheck = (int) round($totalMonthlyTarget / 4);

        $currentMonthLabel = $now->format('F Y');
        $lastMonthLabel = $now->copy()->subMonth()->format('F Y');

        $buckets = Bucket::where('type', Bucket::TYPE_FIXED)
            ->orderBy('priority_order')
            ->get()
            ->map(function (Bucket $bucket) use ($currentMonthStart, $currentMonthEnd) {
                $bucket->funded_this_month = (int) $bucket->transactions()
                    ->where('type', Transaction::TYPE_ALLOCATION)
                    ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
                    ->sum('amount');

                return $bucket;
            });

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
