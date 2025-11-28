<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::post('users', [UserController::class, 'store']);

Route::get('users', [UserController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('users/balance', [UserController::class, 'get_balance']);
});