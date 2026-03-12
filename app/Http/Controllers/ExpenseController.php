<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Models\Bucket;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function create(): View
    {
        $buckets = Bucket::orderBy('name')->get();

        return view('expenses.create', compact('buckets'));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Transaction::create([
            'bucket_id' => $validated['bucket_id'],
            'amount' => -$validated['amount'],
            'type' => Transaction::TYPE_EXPENSE,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('buckets.index')->with('success', 'Expense recorded successfully.');
    }
}
