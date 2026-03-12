<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransferRequest;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransferController extends Controller
{
    public function store(StoreTransferRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $referenceId = Str::uuid()->toString();

        $transactions = DB::transaction(function () use ($validated, $referenceId) {
            $outflow = Transaction::create([
                'bucket_id' => $validated['source_bucket_id'],
                'amount' => -$validated['amount'],
                'type' => Transaction::TYPE_TRANSFER,
                'reference_id' => $referenceId,
                'description' => $validated['description'] ?? null,
            ]);

            $inflow = Transaction::create([
                'bucket_id' => $validated['destination_bucket_id'],
                'amount' => $validated['amount'],
                'type' => Transaction::TYPE_TRANSFER,
                'reference_id' => $referenceId,
                'description' => $validated['description'] ?? null,
            ]);

            return [$outflow, $inflow];
        });

        return response()->json(['data' => $transactions], 201);
    }
}
