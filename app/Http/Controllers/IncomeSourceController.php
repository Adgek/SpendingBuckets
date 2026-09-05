<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeSourceRequest;
use App\Http\Requests\UpdateIncomeSourceRequest;
use App\Models\IncomeSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IncomeSourceController extends Controller
{
    public function index(): View
    {
        $incomeSources = IncomeSource::orderByDesc('is_active')->orderBy('name')->get();
        $monthlyTotal = IncomeSource::monthlyTotal();
        $perPaycheck = (int) round($monthlyTotal / DashboardController::PAYCHECKS_PER_MONTH);

        return view('income-sources.index', compact('incomeSources', 'monthlyTotal', 'perPaycheck'));
    }

    public function create(): View
    {
        return view('income-sources.create');
    }

    public function store(StoreIncomeSourceRequest $request): RedirectResponse
    {
        IncomeSource::create($request->validated());

        return redirect()->route('income-sources.index')->with('success', 'Income source added successfully.');
    }

    public function edit(IncomeSource $incomeSource): View
    {
        return view('income-sources.edit', compact('incomeSource'));
    }

    public function update(UpdateIncomeSourceRequest $request, IncomeSource $incomeSource): RedirectResponse
    {
        $incomeSource->update($request->validated());

        return redirect()->route('income-sources.index')->with('success', 'Income source updated successfully.');
    }

    public function destroy(IncomeSource $incomeSource): RedirectResponse
    {
        $incomeSource->delete();

        return redirect()->route('income-sources.index')->with('success', 'Income source removed successfully.');
    }
}
