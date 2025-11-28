<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransactionController;

Route::post('users', [UserController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('users/balance', [UserController::class, 'get_balance']);
    Route::post('deposit', [TransactionController::class, 'deposit']);
    Route::post('withdraw', [TransactionController::class, 'withdraw']);
    Route::post('transfer', [TransactionController::class, 'transfer']);
    Route::get('history', [TransactionController::class, 'history']);
});
