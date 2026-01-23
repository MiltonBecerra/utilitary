<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Utilities\SupermarketComparator\Http\Controllers\SupermarketComparatorController;

// Supermarket price comparator (public)
Route::get('/supermarket-comparator', [SupermarketComparatorController::class, 'index'])->name('supermarket-comparator.index');
Route::get('/supermarket-comparator/brands', [SupermarketComparatorController::class, 'brands'])->name('supermarket-comparator.brands');
Route::post('/supermarket-comparator/search', [SupermarketComparatorController::class, 'search'])->name('supermarket-comparator.search');
Route::post('/supermarket-comparator/retry-search', [SupermarketComparatorController::class, 'retrySearch'])->name('supermarket-comparator.retry-search');
Route::post('/supermarket-comparator/compare', [SupermarketComparatorController::class, 'compare'])->name('supermarket-comparator.compare');
Route::post('/supermarket-comparator/purchases', [SupermarketComparatorController::class, 'savePurchase'])->name('supermarket-comparator.purchases.store');
Route::get('/supermarket-comparator/purchases/{purchase}', [SupermarketComparatorController::class, 'showPurchase'])->name('supermarket-comparator.purchases.show');
Route::get('/supermarket-comparator/purchases/{purchase}/run', [SupermarketComparatorController::class, 'runPurchase'])->name('supermarket-comparator.purchases.run');
Route::get('/supermarket-comparator/purchases/{purchase}/edit', [SupermarketComparatorController::class, 'editPurchase'])->name('supermarket-comparator.purchases.edit');
Route::put('/supermarket-comparator/purchases/{purchase}', [SupermarketComparatorController::class, 'updatePurchase'])->name('supermarket-comparator.purchases.update');
Route::delete('/supermarket-comparator/purchases/{purchase}', [SupermarketComparatorController::class, 'deletePurchase'])->name('supermarket-comparator.purchases.delete');
