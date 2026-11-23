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
        Schema::create('document_chunks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_version_id')->constrained('document_versions')->onDelete('CASCADE');
            $table->unsignedInteger('chunk_index');
            $table->text('content');
            $table->unsignedInteger('page_number')->nullable();
            $table->string('section')->nullable();
            $table->string('clause')->nullable();
            $table->timestamp('created_at');

            $table->unique(['document_version_id', 'chunk_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
