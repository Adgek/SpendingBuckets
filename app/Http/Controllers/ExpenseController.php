<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Models\Bucket;
use App\Models\Transaction;
use App\Services\ActivePeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function create(ActivePeriodService $periodService): View
    {
        $buckets = Bucket::orderBy('name')->get();

        // Never offer a date past the month being worked in, so the picker matches
        // the validation rule in StoreExpenseRequest.
        $maxDate = Carbon::now()->min(
            $periodService->current()->copy()->endOfMonth()
        );

        return view('expenses.create', compact('buckets', 'maxDate'));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $expenseDate = Carbon::parse($validated['expense_date']);
        $now = Carbon::now();

        // If the chosen date is today, preserve the current time; otherwise use end-of-day so
        // the expense sorts naturally on its date.
        $createdAt = $expenseDate->isSameDay($now)
            ? $now
            : $expenseDate->copy()->endOfDay();

        $transaction = new Transaction([
            'bucket_id' => $validated['bucket_id'],
            'amount' => -$validated['amount'],
            'type' => Transaction::TYPE_EXPENSE,
            'description' => $validated['description'] ?? null,
        ]);
        $transaction->timestamps = false;
        $transaction->created_at = $createdAt;
        $transaction->save();

        return redirect()->route('buckets.index')->with('success', 'Expense recorded successfully.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        if ($transaction->type !== Transaction::TYPE_EXPENSE) {
            abort(404);
        }

        $transaction->delete();

        return redirect()->route('buckets.index')->with('success', 'Expense undone successfully.');
    }
}
