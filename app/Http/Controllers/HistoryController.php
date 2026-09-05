<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ActivePeriodService;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(ActivePeriodService $periodService): View
    {
        $closedPeriods = $periodService->closedPeriods();
        $closedPeriods->load('bucketSnapshots.bucket');

        return view('history.index', compact('closedPeriods'));
    }
}
