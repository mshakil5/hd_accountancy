<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PassportAuthController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\ReceiptController;

Route::post('login', [PassportAuthController::class, 'login']);
Route::post('forgot-password', [PassportAuthController::class, 'forgotPassword']);
Route::post('verify-otp', [PassportAuthController::class, 'verifyOtp']);
Route::post('reset-password', [PassportAuthController::class, 'resetPassword']);

Route::middleware('auth:api')->group(function () {
    Route::get('me', [PassportAuthController::class, 'me']);
    Route::post('logout', [PassportAuthController::class, 'logout']);

    Route::get('businesses', [BusinessController::class, 'index']);
    Route::get('businesses/{id}', [BusinessController::class, 'show']);

    Route::get('businesses/{businessId}/receipts', [ReceiptController::class, 'index']);
    Route::post('businesses/{businessId}/receipts', [ReceiptController::class, 'store']);
    Route::get('receipts/{id}', [ReceiptController::class, 'show']);
});