<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

class ExpenseController extends Controller
{
    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $transaction = Transaction::create([
            'bucket_id' => $validated['bucket_id'],
            'amount' => -$validated['amount'],
            'type' => Transaction::TYPE_EXPENSE,
            'description' => $validated['description'] ?? null,
        ]);

        $transaction->load('bucket');

        return response()->json(['data' => $transaction], 201);
    }
}
