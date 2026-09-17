<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('grant_program_id')
                ->constrained('grant_programs')
                ->cascadeOnDelete();

            $table->foreignUuid('document_type_id')
                ->nullable()
                ->constrained('document_types')
                ->restrictOnDelete();

            $table->foreignUuid('requirement_id')
                ->nullable()
                ->constrained('requirements')
                ->restrictOnDelete();

            $table->string('scope', 50)->default('proposal');
            $table->boolean('is_mandatory')->default(true);
            $table->unsignedInteger('maximum_files')->default(1);

            $table->jsonb('validation_rules')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();

            $table->index([
                'grant_program_id',
                'scope',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_requirements');
    }
};
