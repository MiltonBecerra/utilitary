<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscription_utility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('utility_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['subscription_id', 'utility_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_utility');
    }
};
