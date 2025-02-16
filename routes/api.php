<?php

use App\Http\Controllers\CryptoTradeController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'crypto', 'as' => 'crypto.'], function () {
    Route::post('/candles/{cryptoType?}', [CryptoTradeController::class, 'getCandles'])->name('getCandles');
});
