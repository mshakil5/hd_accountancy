<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PassportAuthController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\ReceiptController;

Route::post('login',           [PassportAuthController::class, 'login']);
Route::post('forgot-password', [PassportAuthController::class, 'forgotPassword']);
Route::post('verify-otp',      [PassportAuthController::class, 'verifyOtp']);
Route::post('reset-password',  [PassportAuthController::class, 'resetPassword']);

Route::middleware('auth:api')->get('/user', function (Request $request) {
  return $request->user();
});