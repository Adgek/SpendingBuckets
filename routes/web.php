<?php

declare(strict_types=1);

use App\Http\Controllers\BucketController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('buckets.index'));

Route::resource('buckets', BucketController::class);

Route::get('deposits', [DepositController::class, 'index'])->name('deposits.index');
Route::get('deposits/create', [DepositController::class, 'create'])->name('deposits.create');
Route::post('deposits', [DepositController::class, 'store'])->name('deposits.store');

Route::get('expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');

Route::get('transfers/create', [TransferController::class, 'create'])->name('transfers.create');
Route::post('transfers', [TransferController::class, 'store'])->name('transfers.store');
