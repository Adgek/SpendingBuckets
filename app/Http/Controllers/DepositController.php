<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ProcessDepositAction;
use App\Http\Requests\StoreDepositRequest;
use App\Models\Deposit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepositController extends Controller
{
    public function index(): View
    {
        $deposits = Deposit::latest('deposit_date')->get();

        return view('deposits.index', compact('deposits'));
    }

    public function create(): View
    {
        return view('deposits.create');
    }

    public function store(StoreDepositRequest $request, ProcessDepositAction $action): RedirectResponse
    {
        $deposit = Deposit::create($request->validated());

        try {
            $action->execute($deposit);
        } catch (\RuntimeException $e) {
            $deposit->delete();

            return redirect()->route('deposits.create')
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return redirect()->route('buckets.index')->with('success', 'Deposit processed successfully.');
    }
}
