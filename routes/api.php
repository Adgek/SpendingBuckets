<?php

declare(strict_types=1);

use App\Http\Controllers\BucketController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::apiResource('buckets', BucketController::class);
Route::apiResource('deposits', DepositController::class)->only(['index', 'store']);
Route::post('expenses', [ExpenseController::class, 'store']);
Route::post('transfers', [TransferController::class, 'store']);
