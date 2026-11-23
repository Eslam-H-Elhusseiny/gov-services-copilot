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
        Schema::create('approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_run_id')->constrained('workflow_runs')->onDelete('RESTRICT');
            $table->foreignId('officer_id')->constrained('users')->onDelete('RESTRICT');
            $table->enum('decision', ['approve', 'reject', 'edit_and_approve']);
            $table->text('edited_content')->nullable();
            $table->text('approved_content')->nullable();
            $table->text('review_comment')->nullable();
            $table->timestamp('created_at');

            $table->index(['workflow_run_id']);
            $table->index(['officer_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
