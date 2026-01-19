<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utility_payments', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('utility_id')->nullable()->constrained('utilities')->nullOnDelete();
            $table->string('utility_slug', 120)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_id', 120)->nullable();
            $table->string('plan', 30);
            $table->string('currency', 8)->default('PEN');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status', 30)->default('pending');
            $table->string('mp_preference_id', 120)->nullable();
            $table->string('mp_payment_id', 120)->nullable();
            $table->string('mp_status', 60)->nullable();
            $table->string('mp_status_detail', 120)->nullable();
            $table->string('mp_payment_type', 60)->nullable();
            $table->json('mp_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utility_payments');
    }
};
