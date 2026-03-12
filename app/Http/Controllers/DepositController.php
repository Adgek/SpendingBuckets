<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ProcessDepositAction;
use App\Http\Requests\StoreDepositRequest;
use App\Models\Deposit;
use Illuminate\Http\JsonResponse;

class DepositController extends Controller
{
    public function index(): JsonResponse
    {
        $deposits = Deposit::latest('deposit_date')->get();

        return response()->json(['data' => $deposits]);
    }

    public function store(StoreDepositRequest $request, ProcessDepositAction $action): JsonResponse
    {
        $deposit = Deposit::create($request->validated());

        $action->execute($deposit);

        return response()->json(['data' => $deposit], 201);
    }
}
