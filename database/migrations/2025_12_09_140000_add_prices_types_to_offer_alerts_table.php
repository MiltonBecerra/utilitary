<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('offer_alerts', function (Blueprint $table) {
            $table->decimal('public_price', 10, 2)->nullable()->after('current_price');
            $table->decimal('cmr_price', 10, 2)->nullable()->after('public_price');
            $table->string('price_type')->default('public')->after('cmr_price'); // public|cmr
        });
    }

    public function down(): void
    {
        Schema::table('offer_alerts', function (Blueprint $table) {
            $table->dropColumn(['public_price', 'cmr_price', 'price_type']);
        });
    }
};
