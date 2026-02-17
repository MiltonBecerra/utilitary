<?php

use App\Models\User;
use App\Modules\Utilities\SupermarketComparator\Http\Controllers\SupermarketComparatorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = User::first();
if (!$user) {
    $id = DB::table('users')->insertGetId([
        'name' => 'SMC Test',
        'email' => 'smc_test@example.com',
        'password' => bcrypt('secret123'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $user = User::find($id);
}

Auth::login($user);

DB::table('smc_agent_job_items')->delete();
DB::table('smc_agent_jobs')->delete();

$request = Request::create('/supermarket-comparator/fill-cart', 'POST', [
    'store' => 'plaza_vea',
    'device_id' => 'device-validation-001',
    'items' => [
        [
            'store' => 'plaza_vea',
            'store_label' => 'PLAZA VEA',
            'title' => 'Leche Entera UHT BAZO VELARDE Caja 946ml',
            'url' => 'https://www.plazavea.com.pe/leche-entera-uht-bazo-velarde-caja-946ml/p',
            'quantity' => 1,
        ],
        [
            'store' => 'plaza_vea',
            'store_label' => 'PLAZA VEA',
            'title' => "Huevos Pardos BELL'S Bandeja 30un",
            'url' => 'https://www.plazavea.com.pe/huevos-pardos-bells-bandeja-30un/p',
            'quantity' => 2,
        ],
    ],
]);

/** @var SupermarketComparatorController $controller */
$controller = app()->make(SupermarketComparatorController::class);
$response = $controller->fillCart($request);
$data = $response->getData(true);

$jobs = DB::table('smc_agent_jobs')->count();
$items = DB::table('smc_agent_job_items')->count();

if (($data['queued'] ?? false) && $jobs === 1 && $items === 2) {
    fwrite(STDOUT, "VALIDATION_OK\n");
    exit(0);
}

fwrite(STDERR, 'VALIDATION_FAIL: ' . json_encode([
    'response' => $data,
    'jobs' => $jobs,
    'items' => $items,
]) . PHP_EOL);
exit(1);
