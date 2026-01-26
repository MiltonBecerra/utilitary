<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Utilities\OfferAlerts\Http\Controllers\OfferAlertController;

// Offer alerts (public)
Route::get('/offer-alerts/create', [OfferAlertController::class, 'create'])->name('offer-alerts.create');
Route::post('/offer-alerts', [OfferAlertController::class, 'store'])->name('offer-alerts.store');
Route::get('/offer-alerts/public/{token}', [OfferAlertController::class, 'showPublic'])->name('offer-alerts.public.show');
Route::get('/offer-alerts', [OfferAlertController::class, 'index'])->name('offer-alerts.index');
Route::patch('/offer-alerts/{offerAlert}', [OfferAlertController::class, 'update'])->name('offer-alerts.update');
Route::get("/offer-alerts/{offerAlert}/change-price-type/{type}", [OfferAlertController::class, "changePriceType"])->name("offer-alerts.change-price-type")->middleware("signed");
Route::delete('/offer-alerts/{offerAlert}', [OfferAlertController::class, 'destroy'])->name('offer-alerts.destroy');

Route::middleware('auth')->group(function () {
    Route::get('/offer-alerts/{offerAlert}', [OfferAlertController::class, 'show'])->name('offer-alerts.show');
});
