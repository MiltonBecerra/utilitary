<?php

use Illuminate\Support\Facades\Route;
use App\Modules\SunatRxH\Http\Controllers\SunatRxHController;

Route::prefix('utilitary/sunat-rxh')->name('sunat.rxh.')->group(function () {
    Route::get('/', [SunatRxHController::class, 'index'])->name('index');
    Route::post('/emit', [SunatRxHController::class, 'store'])->name('store');
});
