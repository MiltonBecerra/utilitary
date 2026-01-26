<?php

use Illuminate\Database\Seeder;
use App\Models\OfferAlert;

class FixOfferAlertTitlesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Iniciando limpieza de títulos con entidades HTML...');
        
        $fixedCount = 0;
        $totalCount = 0;
        
        // Obtener todas las alertas con títulos que contienen entidades HTML
        OfferAlert::where('title', 'like', '%&%')->chunk(100, function ($alerts) use (&$fixedCount, &$totalCount) {
            foreach ($alerts as $alert) {
                $totalCount++;
                
                $originalTitle = $alert->title;
                $cleanTitle = stripslashes(html_entity_decode($originalTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                
                // Solo actualizar si hay cambios
                if ($originalTitle !== $cleanTitle) {
                    $alert->title = $cleanTitle;
                    $alert->save();
                    $fixedCount++;
                    
                    $this->command->line("ID {$alert->id}: '{$originalTitle}' -> '{$cleanTitle}'");
                }
            }
        });
        
        $this->command->info("Proceso completado:");
        $this->command->info("- Total de alertas revisadas: {$totalCount}");
        $this->command->info("- Alertas corregidas: {$fixedCount}");
        
        if ($fixedCount > 0) {
            $this->command->info("✅ Se han corregido {$fixedCount} títulos con entidades HTML.");
        } else {
            $this->command->info("ℹ️  No se encontraron títulos con entidades HTML que necesiten corrección.");
        }
    }
}