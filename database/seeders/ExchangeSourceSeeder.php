<?php

namespace Database\Seeders;

use App\Models\ExchangeSource;
use Illuminate\Database\Seeder;

class ExchangeSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sources = [
            [
                'name' => 'Kambista',
                'url' => 'https://kambista.com/',
                'selector_buy' => '#val_compra', // Placeholder, handled by custom logic
                'selector_sell' => '#val_venta',
                'is_active' => true,
            ],
            [
                'name' => 'Tucambista',
                'url' => 'https://tucambista.pe/',
                'selector_buy' => 'json', // Handled by custom logic
                'selector_sell' => 'json',
                'is_active' => true,
            ],
            [
                'name' => 'Tkambio',
                'url' => 'https://tkambio.com/',
                'selector_buy' => '#buy-rate', // Placeholder
                'selector_sell' => '#sell-rate',
                'is_active' => true,
            ],
        ];

        foreach ($sources as $source) {
            ExchangeSource::create($source);
        }
    }
}
