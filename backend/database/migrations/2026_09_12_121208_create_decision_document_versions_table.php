<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decision_document_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('decision_document_id');
            $table->uuid('created_by');

            $table->unsignedInteger('version_number');

            $table->string('original_filename', 255)
                ->nullable();

            $table->string('stored_filename', 255)
                ->nullable();

            $table->string('disk', 100)
                ->default('private');

            $table->text('storage_path')
                ->nullable();

            $table->string('mime_type', 150)
                ->nullable();

            $table->unsignedBigInteger('file_size')
                ->nullable();

            $table->string('file_hash', 128)
                ->nullable();

            $table->string('status', 50)
                ->default('active')
                ->index();

            $table->text('change_notes')->nullable();

            $table->timestampTz('created_at');

            $table->foreign('decision_document_id')
                ->references('id')
                ->on('decision_documents')
                ->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->unique([
                'decision_document_id',
                'version_number',
            ]);

            $table->index([
                'decision_document_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decision_document_versions');
    }
};
