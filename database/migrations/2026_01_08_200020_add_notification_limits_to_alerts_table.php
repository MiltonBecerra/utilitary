<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->timestamp('last_notified_at')->nullable()->after('frequency');
            $table->date('daily_notified_date')->nullable()->after('last_notified_at');
            $table->unsignedInteger('daily_notified_count')->default(0)->after('daily_notified_date');
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropColumn(['last_notified_at', 'daily_notified_date', 'daily_notified_count']);
        });
    }
};
