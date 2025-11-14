<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [AuthController::class, 'user']);

    Route::prefix('/transactions')->controller(TransactionController::class)->group(function () {
        Route::get('/', 'history');
        Route::post('/', 'store');
    });
});
