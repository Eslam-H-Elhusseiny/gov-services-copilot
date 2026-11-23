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
        Schema::create('document_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('RESTRICT');
            $table->string('version_label');
            $table->string('content_hash')->unique();
            $table->string('file_path');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('RESTRICT');
            $table->enum('status', ['pending', 'processing', 'indexed', 'failed'])->default('pending');
            $table->text('failure_details')->nullable();
            $table->unsignedInteger('chunk_count')->default(0);
            $table->timestamp('indexed_at')->nullable();
            $table->timestamp('created_at');

            $table->unique(['document_id', 'version_label']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
