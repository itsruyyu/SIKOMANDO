<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_weight_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('grant_program_id')
                ->constrained('grant_programs')
                ->cascadeOnDelete();

            $table->foreignUuid('policy_version_id')
                ->nullable()
                ->constrained('policy_versions')
                ->restrictOnDelete();

            $table->foreignUuid('evaluation_criteria_id')
                ->constrained('evaluation_criteria')
                ->restrictOnDelete();

            $table->decimal('weight', 8, 4);
            $table->decimal('minimum_score', 8, 2)->nullable();
            $table->decimal('maximum_score', 8, 2)->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();

            $table->unique([
                'grant_program_id',
                'evaluation_criteria_id',
                'policy_version_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_weight_configurations');
    }
};