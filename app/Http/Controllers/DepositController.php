<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ProcessDepositAction;
use App\Http\Requests\StoreDepositRequest;
use App\Models\Deposit;
use App\Services\ActivityFeedService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DepositController extends Controller
{
    /**
     * The ledger history page: deposits, expenses, transfers and sweeps in one feed,
     * narrowed by the ?type= selector.
     */
    public function index(Request $request, ActivityFeedService $activityFeed): View
    {
        $activityType = ActivityFeedService::normaliseType($request->query('type'));
        $entries = $activityFeed->entries($activityType);

        return view('deposits.index', compact('entries', 'activityType'));
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

    public function destroy(Deposit $deposit): RedirectResponse
    {
        DB::transaction(function () use ($deposit) {
            $deposit->transactions()->delete();
            $deposit->delete();
        });

        return redirect()->route('deposits.index')->with('success', 'Deposit undone successfully.');
    }
}
