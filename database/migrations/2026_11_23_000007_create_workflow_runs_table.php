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
        Schema::create('workflow_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->onDelete('RESTRICT');
            $table->string('run_uuid')->unique();
            $table->string('correlation_id')->nullable();
            $table->enum('run_type', ['officer', 'evaluation'])->default('officer');
            $table->enum('current_state', [
                'RECEIVED',
                'ELIGIBILITY_CHECK',
                'PROCEDURE_RESOLUTION',
                'RESPONSE_DRAFTING',
                'NEEDS_INFORMATION',
                'PENDING_APPROVAL',
                'APPROVED',
                'REJECTED',
                'COMPLETED',
                'FAILED',
                'CANCELLED',
            ])->default('RECEIVED');
            $table->text('draft_response')->nullable();
            $table->text('final_response')->nullable();
            $table->unsignedInteger('estimated_tokens')->default(0);
            $table->unsignedInteger('reserved_tokens')->default(0);
            $table->timestamp('cancellation_requested_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_runs');
    }
};
