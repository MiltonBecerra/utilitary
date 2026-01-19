<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_consents', function (Blueprint $table) {
            $table->id();
            $table->string('guest_id', 36)->unique();
            $table->timestamp('accepted_at');
            $table->string('accepted_ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_consents');
    }
};
