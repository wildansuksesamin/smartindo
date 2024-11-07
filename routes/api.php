<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/access/token', [App\Http\Controllers\AccessController::class, 'Token']);
Route::post('/access/register', [App\Http\Controllers\AccessController::class, 'Register']);
Route::post('/access/login', [App\Http\Controllers\AccessController::class, 'Login']);
Route::post('/access/change-pass', [App\Http\Controllers\AccessController::class, 'ChangePass']);
Route::post('/access/change-pin', [App\Http\Controllers\AccessController::class, 'ChangePIN']);

Route::post('/tabungan/list-account', [App\Http\Controllers\TabunganController::class, 'ListAccount']);
Route::post('/tabungan/history-account', [App\Http\Controllers\TabunganController::class, 'HistoryAccount']);
Route::post('/deposito/history-account', [App\Http\Controllers\TabunganController::class, 'HistoryDeposito']);
Route::post('/pinjaman/list-account', [App\Http\Controllers\TabunganController::class, 'ListPinjaman']);
Route::post('/pinjaman/history-account', [App\Http\Controllers\TabunganController::class, 'HistoryPinjaman']);

Route::post('/account/save', [App\Http\Controllers\AccountController::class, 'SaveAccount']);
Route::post('/account/delete', [App\Http\Controllers\AccountController::class, 'DeleteAccount']);

Route::post('/transfer-lpd/check-account', [App\Http\Controllers\TransferLPDController::class, 'CheckAccount']);
Route::post('/transfer-lpd/inquiryTransfer', [App\Http\Controllers\TransferLPDController::class, 'InquiryTransfer']);
Route::post('/transfer-lpd/postingTransfer', [App\Http\Controllers\TransferLPDController::class, 'PostingTransfer']);

if (env('APP_POLICY') == 'elink'){
    Route::post('/transfer-bank/check-account', [App\Http\Controllers\TransfereLinkController::class, 'CheckAccount']);
    Route::post('/transfer-bank/inquiryTransfer', [App\Http\Controllers\TransfereLinkController::class, 'InquiryTransfer']);
    Route::post('/transfer-bank/postingTransfer', [App\Http\Controllers\TransfereLinkController::class, 'PostingTransfer']);
}else if (env('APP_POLICY') == 'development' || env('APP_POLICY') == 'production'){
    Route::post('/transfer-bank/check-account', [App\Http\Controllers\TransferBankController::class, 'CheckAccount']);
    Route::post('/transfer-bank/inquiryTransfer', [App\Http\Controllers\TransferBankController::class, 'InquiryTransfer']);
    Route::post('/transfer-bank/postingTransfer', [App\Http\Controllers\TransferBankController::class, 'PostingTransfer']);
}

Route::post('/ppob/check-account', [App\Http\Controllers\PPOBController::class, 'CheckAccount']);
Route::post('/ppob/payment', [App\Http\Controllers\PPOBController::class, 'Payment']);
