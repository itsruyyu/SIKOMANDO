<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_document_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('proposal_document_id')
                ->constrained('proposal_documents')
                ->cascadeOnDelete();

            $table->foreignUuid('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->unsignedInteger('version_number');
            $table->string('original_filename', 255);
            $table->string('stored_filename', 255);
            $table->string('storage_disk', 100)->default('private');
            $table->string('storage_path', 500);
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('file_hash', 128)->nullable();

            $table->string('status', 50)->default('active')->index();
            $table->text('change_notes')->nullable();

            $table->timestampTz('created_at');

            $table->unique([
                'proposal_document_id',
                'version_number',
            ]);

            $table->index([
                'proposal_document_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_document_versions');
    }
};
