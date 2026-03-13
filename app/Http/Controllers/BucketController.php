<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBucketRequest;
use App\Http\Requests\UpdateBucketRequest;
use App\Models\Bucket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BucketController extends Controller
{
    public function index(): View
    {
        $buckets = Bucket::withSum('transactions', 'amount')
            ->orderBy('priority_order')
            ->get();

        $fixedBuckets = $buckets->where('type', Bucket::TYPE_FIXED);
        $excessBuckets = $buckets->where('type', Bucket::TYPE_EXCESS);
        $totalBalance = $buckets->sum('transactions_sum_amount');
        $totalMonthlyTarget = (int) $fixedBuckets->sum('monthly_target');
        $perPaycheck = (int) round($totalMonthlyTarget / 4);

        return view('buckets.index', compact('buckets', 'fixedBuckets', 'excessBuckets', 'totalBalance', 'totalMonthlyTarget', 'perPaycheck'));
    }

    public function show(Bucket $bucket): View
    {
        $bucket->loadSum('transactions', 'amount');
        $bucket->load(['transactions' => fn ($q) => $q->latest()]);

        return view('buckets.show', compact('bucket'));
    }

    public function create(): View
    {
        return view('buckets.create');
    }

    public function store(StoreBucketRequest $request): RedirectResponse
    {
        Bucket::create($request->validated());

        return redirect()->route('buckets.index')->with('success', 'Bucket created successfully.');
    }

    public function edit(Bucket $bucket): View
    {
        $bucket->loadSum('transactions', 'amount');

        return view('buckets.edit', compact('bucket'));
    }

    public function update(UpdateBucketRequest $request, Bucket $bucket): RedirectResponse
    {
        $bucket->update($request->validated());

        return redirect()->route('buckets.index')->with('success', 'Bucket updated successfully.');
    }

    public function destroy(Bucket $bucket): RedirectResponse
    {
        if ($bucket->balance > 0) {
            return redirect()->route('buckets.edit', $bucket)
                ->with('error', "Cannot delete bucket \"{$bucket->name}\" — it still has a balance of $" . number_format($bucket->balance / 100, 2) . '. Transfer or sweep the funds first.');
        }

        $bucket->delete();

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
