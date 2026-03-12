<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBucketRequest;
use App\Http\Requests\UpdateBucketRequest;
use App\Models\Bucket;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BucketController extends Controller
{
    public function index(): View
    {
        $buckets = Bucket::withSum('transactions', 'amount')
            ->orderBy('priority_order')
            ->get();

        return view('buckets.index', compact('buckets'));
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
}
