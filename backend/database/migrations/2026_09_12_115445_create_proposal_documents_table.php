<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('proposal_id')
                ->constrained('proposals')
                ->cascadeOnDelete();

            $table->foreignUuid('document_type_id')
                ->constrained('document_types')
                ->restrictOnDelete();

            $table->foreignUuid('document_requirement_id')
                ->nullable()
                ->constrained('document_requirements')
                ->restrictOnDelete();

            $table->string('original_filename', 255);
            $table->string('stored_filename', 255);
            $table->string('storage_disk', 100)->default('public');
            $table->string('storage_path', 500);

            $table->string('mime_type', 150);
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('file_hash', 128)->nullable();

            $table->unsignedInteger('version')->default(1);
            $table->string('status', 50)->default('uploaded')->index();

            $table->text('notes')->nullable();

            $table->foreignUuid('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('uploaded_at')->nullable();

            $table->foreignUuid('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'proposal_id',
                'document_type_id',
            ]);

            $table->index([
                'proposal_id',
                'status',
            ]);

            $table->index('file_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_documents');
    }
};
