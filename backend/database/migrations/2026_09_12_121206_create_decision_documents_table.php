<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decision_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('decision_id');
            $table->uuid('decision_template_id')->nullable();
            $table->uuid('decision_template_version_id')->nullable();
            $table->uuid('uploaded_by')->nullable();

            $table->string('document_type', 100)
                ->default('decision_letter')
                ->index();

            $table->string('document_title', 255);

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

            $table->unsignedInteger('version')
                ->default(1);

            $table->string('status', 50)
                ->default('draft')
                ->index();

            $table->timestampTz('generated_at')->nullable();
            $table->timestampTz('uploaded_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestampsTz();

            $table->foreign('decision_id')
                ->references('id')
                ->on('decisions')
                ->cascadeOnDelete();

            $table->foreign('decision_template_id')
                ->references('id')
                ->on('decision_templates')
                ->nullOnDelete();

            $table->foreign('decision_template_version_id')
                ->references('id')
                ->on('decision_template_versions')
                ->nullOnDelete();

            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index([
                'decision_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decision_documents');
    }
};
