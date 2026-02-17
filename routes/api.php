<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


use App\Modules\Core\Http\Controllers\PaymentController;
use App\Modules\Utilities\SupermarketComparator\Http\Controllers\SmcAgentApiController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::match(['get', 'post'], '/payments/webhook', [PaymentController::class, 'webhook'])->name('payments.webhook');

Route::get('/smc/agent/jobs/next', [SmcAgentApiController::class, 'nextJob']);
Route::post('/smc/agent/jobs/{jobId}/status', [SmcAgentApiController::class, 'updateStatus']);
