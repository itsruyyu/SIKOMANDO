<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numbering_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('grant_program_id')
                ->nullable()
                ->constrained('grant_programs')
                ->cascadeOnDelete();

            $table->string('document_type', 100);
            $table->string('prefix', 100)->nullable();
            $table->string('format_pattern', 255);

            $table->unsignedBigInteger('current_sequence')->default(0);
            $table->unsignedInteger('reset_period')->default(1);

            $table->string('status', 50)->default('active')->index();

            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignUuid('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique([
                'grant_program_id',
                'document_type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numbering_configurations');
    }
};