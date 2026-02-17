<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smc_agent_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_id', 80)->nullable()->index();
            $table->string('device_id', 120)->index();
            $table->string('store', 30);
            $table->string('status', 20)->default('pending')->index();
            $table->json('items');
            $table->json('progress')->nullable();
            $table->json('result')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'status', 'id'], 'smc_agent_jobs_device_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smc_agent_jobs');
    }
};
