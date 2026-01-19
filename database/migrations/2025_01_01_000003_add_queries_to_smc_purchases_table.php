<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('smc_purchases', function (Blueprint $table) {
            $table->json('queries')->nullable()->after('location');
            $table->string('name', 120)->nullable()->after('queries');
        });
    }

    public function down(): void
    {
        Schema::table('smc_purchases', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('queries');
        });
    }
};
