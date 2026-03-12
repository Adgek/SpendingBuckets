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
        $buckets = Bucket::with('transactions')->orderBy('priority_order')->get();
        $buckets->each(fn (Bucket $bucket) => $bucket->append('balance'));

        return view('buckets.index', compact('buckets'));
    }

    public function show(Bucket $bucket): View
    {
        $bucket->load('transactions');
        $bucket->append('balance');

        return view('buckets.show', compact('bucket'));
    }

    public function create(): View
    {
        return view('buckets.create');
    }

    public function store(StoreBucketRequest $request): RedirectResponse
    {
        Bucket::create($request->validated());

        return redirect('/buckets')->with('success', 'Bucket created successfully.');
    }

    public function edit(Bucket $bucket): View
    {
        return view('buckets.edit', compact('bucket'));
    }

    public function update(UpdateBucketRequest $request, Bucket $bucket): RedirectResponse
    {
        $bucket->update($request->validated());

        return redirect('/buckets')->with('success', 'Bucket updated successfully.');
    }

    public function destroy(Bucket $bucket): RedirectResponse
    {
        $bucket->delete();

        return redirect('/buckets')->with('success', 'Bucket deleted successfully.');
    }
}
