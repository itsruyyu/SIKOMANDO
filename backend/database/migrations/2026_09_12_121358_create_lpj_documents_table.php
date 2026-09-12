<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lpj_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('lpj_submission_id');
            $table->uuid('uploaded_by');

            $table->string('document_type', 100)
                ->index();

            $table->string('document_title', 255);

            $table->string('original_filename', 255);
            $table->string('stored_filename', 255);

            $table->string('disk', 100)
                ->default('private');

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

            $table->foreign('lpj_submission_id')
                ->references('id')
                ->on('lpj_submissions')
                ->cascadeOnDelete();

            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->index([
                'lpj_submission_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lpj_documents');
    }
};