<?php

namespace App\Modules\Utilities\NameRaffle\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Utility;

class NameRaffleController extends Controller
{
    protected function utility(): ?Utility
    {
        try {
            return Utility::firstOrCreate(
                ['slug' => 'name-raffle'],
                [
                    'name' => 'Sorteo de nombres',
                    'description' => 'Sorteo de nombres con multiples ganadores y guardado de resultados.',
                    'icon' => 'fas fa-random',
                    'is_active' => true,
                ]
            );
        } catch (\Throwable $e) {
            \Log::warning('name_raffle_utility_load_failed', ['error' => $e->getMessage()]);
            return Utility::where('slug', 'name-raffle')->first();
        }
    }

    public function index()
    {
        $utility = $this->utility();
        $comments = $utility ? $utility->comments()->latest()->get() : collect();

        return view('modules.name_raffle.index', [
            'utility' => $utility,
            'comments' => $comments,
        ]);
    }
}




