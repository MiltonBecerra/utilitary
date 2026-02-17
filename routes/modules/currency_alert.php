<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Utilities\CurrencyAlert\Http\Controllers\CurrencyAlertController;

Route::get('/currency-alert', [CurrencyAlertController::class, 'index'])->name('currency-alert.index');
Route::post('/currency-alert', [CurrencyAlertController::class, 'store'])->name('currency-alert.store');
Route::get('/currency-alert/{id}/edit', [CurrencyAlertController::class, 'edit'])->name('currency-alert.edit');
Route::put('/currency-alert/{id}', [CurrencyAlertController::class, 'update'])->name('currency-alert.update');
Route::patch('/currency-alert/{id}/deactivate', [CurrencyAlertController::class, 'deactivate'])->name('currency-alert.deactivate');
Route::delete('/currency-alert/{id}', [CurrencyAlertController::class, 'destroy'])->name('currency-alert.destroy');
