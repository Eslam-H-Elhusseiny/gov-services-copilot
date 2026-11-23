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
        Schema::create('case_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->onDelete('RESTRICT');
            $table->enum('sender_type', ['officer', 'ai_assistant']);
            $table->text('content');
            $table->json('citations')->nullable();
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_messages');
    }
};
