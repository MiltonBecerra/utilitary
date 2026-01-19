<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('offer_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('guest_id')->nullable();
            $table->foreignId('utility_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('public_token')->nullable()->unique();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('url');
            $table->string('title')->nullable();
            $table->string('store')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('current_price', 10, 2);
            $table->decimal('target_price', 10, 2)->nullable();
            $table->boolean('notify_on_any_drop')->default(false);
            $table->decimal('last_notified_price', 10, 2)->nullable();
            $table->string('status')->default('monitoring');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_alerts');
    }
};
