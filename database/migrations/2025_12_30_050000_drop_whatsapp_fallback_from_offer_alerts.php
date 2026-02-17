<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('offer_alerts', 'whatsapp_fallback')) {
            Schema::table('offer_alerts', function (Blueprint $table) {
                $table->dropColumn('whatsapp_fallback');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('offer_alerts', 'whatsapp_fallback')) {
            Schema::table('offer_alerts', function (Blueprint $table) {
                $table->boolean('whatsapp_fallback')->default(false);
            });
        }
    }
};
