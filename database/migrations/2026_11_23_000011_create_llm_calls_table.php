<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('llm_calls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_run_id')->constrained('workflow_runs')->onDelete('CASCADE');
            $table->foreignId('user_id')->constrained('users')->onDelete('RESTRICT');
            $table->string('agent')->nullable();
            $table->string('provider');
            $table->string('model');
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->decimal('input_price_per_1k', 10, 6);
            $table->decimal('output_price_per_1k', 10, 6);
            $table->string('currency')->default('USD');
            $table->foreignId('model_pricing_id')->constrained('model_pricing')->onDelete('RESTRICT');
            $table->decimal('cost', 15, 6)->nullable();
            $table->boolean('reconciled')->default(false);
            $table->unsignedInteger('overage_tokens')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->enum('status', ['success', 'failed', 'cancelled'])->default('success');
            $table->text('error_message')->nullable();
            $table->timestamp('created_at');

            $table->index(['workflow_run_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['agent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('llm_calls');
    }
};
