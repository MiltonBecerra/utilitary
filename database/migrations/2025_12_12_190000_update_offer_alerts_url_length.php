<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Permitir URLs largas (ej. Mercado Libre con querystring extenso)
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE `offer_alerts` MODIFY `url` TEXT');
    }

    public function down(): void
    {
        // Revertir a VARCHAR(255) en caso de rollback
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE `offer_alerts` MODIFY `url` VARCHAR(255)');
    }
};
