<?php

namespace App\Console\Commands;

use App\Models\OfferAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixOfferAlertTitles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'offer-alerts:fix-titles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix HTML entities and quotes in offer alert titles';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔧 Iniciando limpieza de títulos con entidades HTML...');
        
        $fixedCount = 0;
        $totalCount = 0;
        
        // Buscar títulos con entidades HTML específicas
        $alerts = OfferAlert::where(function($query) {
            $query->where('title', 'like', '%&quot;%')
                  ->orWhere('title', 'like', '%&amp;%')
                  ->orWhere('title', 'like', '%&lt;%')
                  ->orWhere('title', 'like', '%&gt;%')
                  ->orWhere('title', 'like', '%&#039;%')
                  ->orWhere('title', 'like', '%\\\\&quot;%');
        })->get();
        
        if ($alerts->isEmpty()) {
            $this->info('✅ No se encontraron títulos con entidades HTML que necesiten corrección.');
            return 0;
        }
        
        $this->info("Se encontraron {$alerts->count()} alertas con entidades HTML.");
        $this->newLine();
        
        $progressBar = $this->output->createProgressBar($alerts->count());
        $progressBar->start();
        
        foreach ($alerts as $alert) {
            $totalCount++;
            
            $originalTitle = $alert->getRawOriginal('title'); // Obtener valor sin accessor
            
            // Limpieza multi-nivel
            $cleanTitle = $originalTitle;
            $cleanTitle = html_entity_decode($cleanTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $cleanTitle = stripslashes($cleanTitle); // Remove backslashes
            $cleanTitle = preg_replace('/\\\\&quot;/', '"', $cleanTitle); // Fix escaped quotes
            $cleanTitle = trim($cleanTitle);
            
            // Solo actualizar si hay cambios reales
            if ($originalTitle !== $cleanTitle) {
                // Actualizar directamente en BD para evitar mutators duplicados
                DB::table('offer_alerts')
                   ->where('id', $alert->id)
                   ->update(['title' => $cleanTitle]);
                
                $fixedCount++;
                
                if ($fixedCount <= 10) { // Mostrar solo primeros 10 ejemplos
                    $this->line("  📝 ID {$alert->id}:");
                    $this->line("     Antes: '{$originalTitle}'");
                    $this->line("     Después: '{$cleanTitle}'");
                    $this->newLine();
                }
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        $this->newLine();
        
        $this->info("🎉 Proceso completado:");
        $this->info("- Total de alertas revisadas: {$totalCount}");
        $this->info("- Alertas corregidas: {$fixedCount}");
        
        if ($fixedCount > 0) {
            $this->info("✅ Se han corregido {$fixedCount} títulos con entidades HTML correctamente.");
            $this->info("💡 Las vistas ahora mostrarán los títulos limpios sin &quot; ni otras entidades HTML.");
        }
        
        return 0;
    }
}