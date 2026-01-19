<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('offer_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_alert_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->timestamp('checked_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_price_histories');
    }
};
