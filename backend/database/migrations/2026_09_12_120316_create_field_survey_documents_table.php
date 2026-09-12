<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_survey_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('field_survey_id');
            $table->uuid('document_type_id')
                ->nullable();

            $table->uuid('uploaded_by');

            $table->string('document_title', 255);

            $table->string('original_filename', 255);
            $table->string('stored_filename', 255);

            $table->string('disk', 100)
                ->default('public');

            $table->text('storage_path');

            $table->string('mime_type', 150);
            $table->unsignedBigInteger('file_size');
            $table->string('file_hash', 128)->nullable();

            $table->unsignedInteger('version')
                ->default(1);

            $table->string('status', 50)
                ->default('uploaded')
                ->index();

            $table->text('notes')->nullable();

            $table->timestampsTz();

            $table->foreign('field_survey_id')
                ->references('id')
                ->on('field_surveys')
                ->cascadeOnDelete();

            $table->foreign('document_type_id')
                ->references('id')
                ->on('document_types')
                ->nullOnDelete();

            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->index([
                'field_survey_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_survey_documents');
    }
};