<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Utilities\NameRaffle\Http\Controllers\NameRaffleController;

// Name raffle (public)
Route::get('/name-raffle', [NameRaffleController::class, 'index'])->name('name-raffle.index');
