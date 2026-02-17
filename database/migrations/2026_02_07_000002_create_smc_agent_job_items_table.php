<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smc_agent_job_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('smc_agent_jobs')->cascadeOnDelete();
            $table->string('store', 30)->nullable();
            $table->string('store_label', 80)->nullable();
            $table->string('title', 255);
            $table->text('url')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->timestamps();

            $table->index(['job_id', 'id'], 'smc_agent_job_items_job_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smc_agent_job_items');
    }
};
