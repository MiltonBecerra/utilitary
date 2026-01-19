<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('offer_alerts', function (Blueprint $table) {
            if (!Schema::hasColumn('offer_alerts', 'channel')) {
                $table->string('channel')->default('email')->after('contact_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('offer_alerts', function (Blueprint $table) {
            if (Schema::hasColumn('offer_alerts', 'channel')) {
                $table->dropColumn('channel');
            }
        });
    }
};

