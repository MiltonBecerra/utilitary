<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smc_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('smc_purchases')->cascadeOnDelete();
            $table->string('store', 50)->nullable();
            $table->string('store_label', 80)->nullable();
            $table->string('title', 255);
            $table->text('url')->nullable();
            $table->text('image_url')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit', 10)->default('un');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('card_price', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smc_purchase_items');
    }
};
