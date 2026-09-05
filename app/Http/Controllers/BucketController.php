<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBucketRequest;
use App\Http\Requests\UpdateBucketRequest;
use App\Models\Bucket;
use App\Models\IncomeSource;
use App\Services\ActivePeriodService;
use App\Services\BucketPriorityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BucketController extends Controller
{
    public function index(ActivePeriodService $periodService): View
    {
        $currentMonthStart = $periodService->current();
        $currentMonthEnd = $currentMonthStart->copy()->endOfMonth();

        $buckets = Bucket::withSum('transactions', 'amount')
            ->orderBy('priority_order')
            ->addSelect(['funded_this_month' => \App\Models\Transaction::selectRaw('COALESCE(SUM(transactions.amount), 0)')
                ->whereColumn('transactions.bucket_id', 'buckets.id')
                ->where('transactions.type', \App\Models\Transaction::TYPE_ALLOCATION)
                ->whereHas('deposit', fn ($q) => $q->whereBetween('deposit_date', [$currentMonthStart, $currentMonthEnd]))
            ])
            ->addSelect(['monthly_balance' => \App\Models\Transaction::selectRaw('COALESCE(SUM(transactions.amount), 0)')
                ->whereColumn('transactions.bucket_id', 'buckets.id')
                ->whereNotIn('transactions.type', [\App\Models\Transaction::TYPE_SWEEP])
                ->whereBetween('transactions.created_at', [$currentMonthStart, $currentMonthEnd])
            ])
            ->get();

        $fixedBuckets = $buckets->where('type', Bucket::TYPE_FIXED);
        $excessBuckets = $buckets->where('type', Bucket::TYPE_EXCESS);
        $totalBalance = $buckets->sum('transactions_sum_amount');
        $totalMonthlyTarget = (int) $fixedBuckets->sum('monthly_target');
        $otherIncome = IncomeSource::monthlyTotal();

        // Other income covers part of the monthly target, so paychecks only need to
        // carry what is left over.
        $needFromPaychecks = max(0, $totalMonthlyTarget - $otherIncome);
        $perPaycheck = (int) round($needFromPaychecks / DashboardController::PAYCHECKS_PER_MONTH);

        return view('buckets.index', compact(
            'buckets',
            'fixedBuckets',
            'excessBuckets',
            'totalBalance',
            'totalMonthlyTarget',
            'otherIncome',
            'perPaycheck',
        ));
    }

    public function show(Bucket $bucket): View
    {
        $bucket->loadSum('transactions', 'amount');
        $bucket->load(['transactions' => fn ($q) => $q->latest()]);

        $primarySavings = Bucket::where('is_primary_savings', true)->first();

        return view('buckets.show', compact('bucket', 'primarySavings'));
    }

    public function create(): View
    {
        return view('buckets.create');
    }

    public function store(StoreBucketRequest $request, BucketPriorityService $priorities): RedirectResponse
    {
        $validated = $request->validated();

        // The service owns the slot, so the stack shifts to make room instead of
        // ending up with two buckets on the same priority.
        $desired = $validated['priority_order'] ?? null;
        unset($validated['priority_order']);

        $bucket = Bucket::create($validated);
        $priorities->place($bucket, $desired);

        return redirect()->route('buckets.index')->with('success', 'Bucket created successfully.');
    }

    public function edit(Bucket $bucket): View
    {
        $bucket->loadSum('transactions', 'amount');

        return view('buckets.edit', compact('bucket'));
    }

    public function update(UpdateBucketRequest $request, Bucket $bucket, BucketPriorityService $priorities): RedirectResponse
    {
        $validated = $request->validated();

        // A missing or blank priority on edit means "leave it where it is".
        $desired = $validated['priority_order'] ?? $bucket->priority_order;
        unset($validated['priority_order']);

        $bucket->update($validated);
        $priorities->place($bucket, $desired);

        return redirect()->route('buckets.index')->with('success', 'Bucket updated successfully.');
    }

    public function destroy(Bucket $bucket, BucketPriorityService $priorities): RedirectResponse
    {
        if ($bucket->balance > 0) {
            return redirect()->route('buckets.edit', $bucket)
                ->with('error', "Cannot delete bucket \"{$bucket->name}\" — it still has a balance of $" . number_format($bucket->balance / 100, 2) . '. Transfer or sweep the funds first.');
        }

        $bucket->delete();
        $priorities->resequence();

        return redirect()->route('buckets.index')->with('success', 'Bucket deleted successfully.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:buckets,id'],
        ]);

        $ids = $validated['order'];

        DB::transaction(function () use ($ids) {
            $cases = [];
            $bindings = [];

            foreach ($ids as $index => $bucketId) {
                $cases[] = "WHEN id = ? THEN ?";
                $bindings[] = $bucketId;
                $bindings[] = $index + 1;
            }

            $casesSql = implode(' ', $cases);
            $idPlaceholders = implode(',', array_fill(0, count($ids), '?'));

            DB::update(
                "UPDATE buckets SET priority_order = CASE {$casesSql} END WHERE id IN ({$idPlaceholders})",
                array_merge($bindings, $ids)
            );
        });

        return response()->json(['message' => 'Priority order updated.']);
    }
}
