<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('currency-alert.index');
});

require __DIR__ . '/modules/core.php';
require __DIR__ . '/modules/currency_alert.php';
require __DIR__ . '/modules/offer_alerts.php';
require __DIR__ . '/modules/supermarket_comparator.php';
require __DIR__ . '/modules/name_raffle.php';
require __DIR__ . '/modules/sunat_rxh.php';
