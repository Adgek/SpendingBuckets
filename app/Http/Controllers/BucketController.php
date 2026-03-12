<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBucketRequest;
use App\Http\Requests\UpdateBucketRequest;
use App\Models\Bucket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BucketController extends Controller
{
    public function index(): JsonResponse
    {
        $buckets = Bucket::with('transactions')->orderBy('priority_order')->get();

        $buckets->each(fn (Bucket $bucket) => $bucket->append('balance'));

        return response()->json(['data' => $buckets]);
    }

    public function show(Bucket $bucket): JsonResponse
    {
        $bucket->load('transactions');
        $bucket->append('balance');

        return response()->json(['data' => $bucket]);
    }

    public function store(StoreBucketRequest $request): JsonResponse
    {
        $bucket = Bucket::create($request->validated());

        return response()->json(['data' => $bucket], 201);
    }

    public function update(UpdateBucketRequest $request, Bucket $bucket): JsonResponse
    {
        $bucket->update($request->validated());

        return response()->json(['data' => $bucket]);
    }

    public function destroy(Bucket $bucket): Response
    {
        $bucket->delete();

        return response()->noContent();
    }
}
