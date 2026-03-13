<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RunSweepAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class SweepController extends Controller
{
    public function create(): View
    {
        return view('sweep.create');
    }

    public function store(RunSweepAction $action): RedirectResponse
    {
        try {
            $results = $action->execute();
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $count = count($results);
        $total = array_sum(array_column($results, 'amount'));

        if ($count === 0) {
            return redirect()->route('buckets.index')
                ->with('success', 'Sweep complete — no eligible buckets had a positive balance.');
        }

        return redirect()->route('buckets.index')
            ->with('success', "Sweep complete — {$count} bucket(s) swept, \$" . number_format($total / 100, 2) . ' moved to savings.')
            ->with('sweep_results', $results);
    }
}
