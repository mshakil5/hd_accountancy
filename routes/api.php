<?php

use App\Http\Controllers\Api\WhatsAppWebhookController;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PassportAuthController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\ReceiptController;

Route::post('login', [PassportAuthController::class, 'login']);
Route::post('forgot-password', [PassportAuthController::class, 'forgotPassword']);
Route::post('verify-otp', [PassportAuthController::class, 'verifyOtp']);
Route::post('reset-password', [PassportAuthController::class, 'resetPassword']);

Route::middleware('auth:api,api_client')->group(function () {
    Route::get('me', [PassportAuthController::class, 'me']);
    Route::post('businesses/{businessId}/bank-statements/recognise', [\App\Http\Controllers\Api\BankReconciliationController::class, 'recogniseStatement']);
    Route::get('businesses/{businessId}/bank-accounts', [\App\Http\Controllers\Api\BankReconciliationController::class, 'accounts']);
Route::post('businesses/{businessId}/bank-accounts', [\App\Http\Controllers\Api\BankReconciliationController::class, 'storeAccount']);
    Route::get('businesses/{businessId}/bank-imports', [\App\Http\Controllers\Api\BankReconciliationController::class, 'imports']);
    Route::post('businesses/{businessId}/bank-imports', [\App\Http\Controllers\Api\BankReconciliationController::class, 'upload']);
    Route::get('businesses/{businessId}/bank-imports/{importId}/download', [\App\Http\Controllers\Api\BankReconciliationController::class, 'downloadStatement']);
    Route::post('businesses/{businessId}/bank-imports/{importId}/replace', [\App\Http\Controllers\Api\BankReconciliationController::class, 'replaceStatement']);
    Route::put('businesses/{businessId}/bank-imports/{importId}/bank-account', [\App\Http\Controllers\Api\BankReconciliationController::class, 'changeBankAccount']);
    Route::get('businesses/{businessId}/bank-imports/{importId}/pdf-link', [\App\Http\Controllers\Api\BankReconciliationController::class, 'statementLink']);
    Route::get('businesses/{businessId}/bank-entries', [\App\Http\Controllers\Api\BankReconciliationController::class, 'entries']);
    Route::get('businesses/{businessId}/bank-entries/{entryId}/suggestions', [\App\Http\Controllers\Api\BankReconciliationController::class, 'suggestions']);
    Route::post('businesses/{businessId}/bank-entries/{entryId}/match', [\App\Http\Controllers\Api\BankReconciliationController::class, 'match']);
    Route::get('businesses/{businessId}/missing-receipt-requests', [\App\Http\Controllers\Api\BankReconciliationController::class, 'missingReceipts']);
    Route::get('businesses/{businessId}/account-heads', [\App\Http\Controllers\Api\BankReconciliationController::class, 'accountHeads']);
    Route::post('businesses/{businessId}/bank-entries/{entryId}/review', [\App\Http\Controllers\Api\BankReconciliationController::class, 'review']);
    Route::post('businesses/{businessId}/bank-entries/{entryId}/categorize', [\App\Http\Controllers\Api\BankReconciliationController::class, 'categorize']);
    Route::post('businesses/{businessId}/bank-entries/{entryId}/unmatch', [\App\Http\Controllers\Api\BankReconciliationController::class, 'unmatch']);
    Route::post('businesses/{businessId}/bank-entries/{entryId}/transfer', [\App\Http\Controllers\Api\BankReconciliationController::class, 'transfer']);
    Route::post('businesses/{businessId}/bank-entries/{entryId}/settle', [\App\Http\Controllers\Api\BankReconciliationController::class, 'settle']);
    Route::get('businesses/{businessId}/bank-settlements', [\App\Http\Controllers\Api\BankReconciliationController::class, 'settlements']);
    Route::post('logout', [PassportAuthController::class, 'logout']);
    Route::post('change-password', [PassportAuthController::class, 'changePassword']);

    Route::get('businesses', [BusinessController::class, 'index']);
    Route::get('businesses/{id}', [BusinessController::class, 'show']);
    Route::get('client-credentials', [BusinessController::class, 'clientCredentials']);
    Route::get('client-credentials/{id}/businesses', [BusinessController::class, 'businessesByCredential']);

Route::get('receipts', [ReceiptController::class, 'all']);
Route::get('businesses/{businessId}/receipts', [ReceiptController::class, 'index']);
Route::post('businesses/{businessId}/receipts', [ReceiptController::class, 'store']);
Route::get('receipts/{id}', [ReceiptController::class, 'show']);
Route::put('receipts/{id}', [ReceiptController::class, 'update']);
Route::delete('receipts/{id}', [ReceiptController::class, 'destroy']);
Route::post('receipts/{receiptId}/files', [ReceiptController::class, 'addFile']);
Route::delete('receipts/{receiptId}/files/{fileId}', [ReceiptController::class, 'deleteFile']);
Route::get('receipts/{receiptId}/files/{fileId}/download', [ReceiptController::class, 'downloadFile']);
Route::get('receipts/{receiptId}/files/{fileId}/link', [ReceiptController::class, 'fileLink']);
});

// Token-protected PDF stream (token issued by pdf-link, expires in 10 minutes)
Route::get('bank-imports/pdf/{token}', [\App\Http\Controllers\Api\BankReconciliationController::class, 'statementPublic'])
    ->middleware('throttle:30,1')->name('api.bank-imports.pdf-public');

Route::get('app/version', function () {
    $platform = request('platform');
    $version = request('version');
    $build = (int) request('build', 0);

    if ($platform === 'ios') {
        $latestVersion = env('IOS_APP_VERSION', '1.0.0');
        $latestBuild = (int) env('IOS_APP_BUILD', 0);
        $storeUrl = env('IOS_APP_STORE_URL', '');
    } else {
        $platform = 'android';
        $latestVersion = env('ANDROID_APP_VERSION', '1.0.0');
        $latestBuild = (int) env('ANDROID_APP_BUILD', 0);
        $storeUrl = env('ANDROID_STORE_URL', '');
    }

    $required = filter_var(env('UPDATE_REQUIRED', false), FILTER_VALIDATE_BOOLEAN);

    $updateAvailable = false;
    if ($build < $latestBuild || version_compare($version ?? '0', $latestVersion, '<') === 1) {
        $updateAvailable = true;
    }

    return response()->json([
        'update_available' => $updateAvailable,
        'update_required' => $required,
        'latest_version' => $latestVersion,
        'latest_build' => (string) $latestBuild,
        'message' => $updateAvailable
            ? 'A new version of HD Accountancy is available.'
            : 'You are on the latest version.',
        'store_url' => $storeUrl,
        'remind_later_hours' => 24,
    ]);
});

// Meta WhatsApp Cloud API webhook
Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive'])->middleware('throttle:120,1');