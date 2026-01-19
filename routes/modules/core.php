<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Modules\Core\Http\Controllers\AlertController;
use App\Modules\Core\Http\Controllers\CommentController;
use App\Modules\Core\Http\Controllers\CommentReactionController;
use App\Modules\Core\Http\Controllers\ExchangeRateController;
use App\Modules\Core\Http\Controllers\ExchangeSourceController;
use App\Modules\Core\Http\Controllers\GuestSubscriptionController;
use App\Modules\Core\Http\Controllers\HomeController;
use App\Modules\Core\Http\Controllers\PaymentController;
use App\Modules\Core\Http\Controllers\SubscriptionController;
use App\Modules\Core\Http\Controllers\UserController;
use App\Modules\Core\Http\Controllers\UserSubscriptionController;
use App\Modules\Core\Http\Controllers\UtilityController;
use App\Modules\Core\Http\Controllers\GuestConsentController;

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::view('/politicas-privacidad', 'legal.privacy')->name('legal.privacy');
Route::view('/terminos-condiciones', 'legal.terms')->name('legal.terms');

// User Subscription Management Routes
Route::get('/upgrade-plan', [UserSubscriptionController::class, 'upgrade'])->name('user.subscription.upgrade');
Route::middleware('auth')->group(function() {
    Route::get('/my-subscription', [UserSubscriptionController::class, 'index'])->name('user.subscription');
    Route::post('/upgrade-plan', [UserSubscriptionController::class, 'processUpgrade'])->name('user.subscription.process');
});

// Guest Subscription Routes (no auth required)
Route::prefix('guest')->group(function() {
    Route::get('/upgrade-plan', [GuestSubscriptionController::class, 'upgrade'])->name('guest.subscription.upgrade');
    Route::post('/upgrade-plan', [GuestSubscriptionController::class, 'processUpgrade'])->name('guest.subscription.process');
    Route::get('/my-subscription', [GuestSubscriptionController::class, 'mySubscription'])->name('guest.subscription');
    Route::post('/consent', [GuestConsentController::class, 'store'])->name('guest.consent.store');
});

// Admin-only routes
Route::middleware(['auth', 'admin'])->group(function() {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::resource('utilities', UtilityController::class);
    Route::resource('exchangeSources', ExchangeSourceController::class);
    Route::resource('exchangeRates', ExchangeRateController::class);
    Route::resource('alerts', AlertController::class);
    Route::resource('subscriptions', SubscriptionController::class);
    Route::resource('users', UserController::class);
});

// Public comments per utility
Route::get('/utilities/{utility}/comments', [CommentController::class, 'index'])->name('utilities.comments.index');
Route::post('/utilities/{utility}/comments', [CommentController::class, 'store'])->name('utilities.comments.store');
Route::post('/comments/{comment}/react', [CommentReactionController::class, 'store'])->name('comments.react');

Route::post('/payments/{utility}', [PaymentController::class, 'createUtilityPayment'])->name('payments.utility.create');
Route::get('/payments/return', [PaymentController::class, 'paymentReturn'])->name('payments.return');

