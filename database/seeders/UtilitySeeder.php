<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Utility;

class UtilitySeeder extends Seeder
{
    /**
     * Seed the utilities table with default records.
     *
     * @return void
     */
    public function run()
    {
        Utility::updateOrCreate(
            ['slug' => 'currency-alert'],
            [
                'name' => 'Alerta de divisas',
                'description' => 'Monitorea casas de cambio y recibe alertas personalizadas.',
                'icon' => 'fas fa-bell',
                'is_active' => true,
            ]
        );
    }
}
