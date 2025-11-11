<?php

use App\Http\Controllers\Api\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('/transactions')->controller(TransactionController::class)->group(function () {
    Route::get('/', 'history');
    Route::post('/', 'store');
});
