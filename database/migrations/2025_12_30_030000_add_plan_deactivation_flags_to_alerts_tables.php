<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->boolean('plan_deactivated')->default(false);
            $table->string('plan_deactivated_from_status')->nullable();
        });

        Schema::table('offer_alerts', function (Blueprint $table) {
            $table->boolean('plan_deactivated')->default(false);
            $table->string('plan_deactivated_from_status')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropColumn(['plan_deactivated', 'plan_deactivated_from_status']);
        });

        Schema::table('offer_alerts', function (Blueprint $table) {
            $table->dropColumn(['plan_deactivated', 'plan_deactivated_from_status']);
        });
    }
};
