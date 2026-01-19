<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('smc_purchases', 'name')) {
            Schema::table('smc_purchases', function (Blueprint $table) {
                $table->string('name', 120)->nullable()->after('queries');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('smc_purchases', 'name')) {
            Schema::table('smc_purchases', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }
};
