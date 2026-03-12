<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransferRequest;
use App\Models\Bucket;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function create(): View
    {
        $buckets = Bucket::orderBy('name')->get();

        return view('transfers.create', compact('buckets'));
    }

    public function store(StoreTransferRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $referenceId = Str::uuid()->toString();

        DB::transaction(function () use ($validated, $referenceId) {
            Transaction::create([
                'bucket_id' => $validated['source_bucket_id'],
                'amount' => -$validated['amount'],
                'type' => Transaction::TYPE_TRANSFER,
                'reference_id' => $referenceId,
                'description' => $validated['description'] ?? null,
            ]);

            Transaction::create([
                'bucket_id' => $validated['destination_bucket_id'],
                'amount' => $validated['amount'],
                'type' => Transaction::TYPE_TRANSFER,
                'reference_id' => $referenceId,
                'description' => $validated['description'] ?? null,
            ]);
        });

        return redirect()->route('buckets.index')->with('success', 'Transfer completed successfully.');
    }
}
