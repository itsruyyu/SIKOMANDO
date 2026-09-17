<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rankings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('grant_program_id')
                ->constrained('grant_programs')
                ->cascadeOnDelete();

            $table->foreignUuid('ranking_rule_configuration_id')
                ->nullable()
                ->constrained('ranking_rule_configurations')
                ->nullOnDelete();

            $table->foreignUuid('policy_version_id')
                ->nullable()
                ->constrained('policy_versions')
                ->nullOnDelete();

            $table->string('ranking_number', 100)
                ->unique();

            $table->string('status', 50)
                ->default('generated')
                ->index();

            $table->jsonb('weights_snapshot')->nullable();
            $table->decimal('cutoff_score', 8, 4)->nullable();

            $table->unsignedInteger('total_proposals')->default(0);
            $table->unsignedInteger('recommended_count')->default(0);
            $table->unsignedInteger('not_recommended_count')->default(0);

            $table->text('summary')->nullable();
            $table->text('notes')->nullable();

            $table->foreignUuid('generated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignUuid('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignUuid('finalized_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestampTz('generated_at')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampTz('finalized_at')->nullable();

            $table->timestampsTz();

            $table->index(['grant_program_id', 'status']);
        });

        Schema::create('ranking_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('ranking_id')
                ->constrained('rankings')
                ->cascadeOnDelete();

            $table->foreignUuid('proposal_id')
                ->constrained('proposals')
                ->restrictOnDelete();

            $table->unsignedInteger('rank');

            $table->decimal('evaluation_score', 8, 4)->default(0);
            $table->decimal('survey_score', 8, 4)->default(0);
            $table->decimal('final_score', 8, 4)->default(0);

            $table->string('status', 50)->default('eligible');
            $table->string('recommendation_result', 50)->default('recommended');

            $table->text('recommendation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('snapshot_data')->nullable();

            $table->timestampsTz();

            $table->index(['ranking_id', 'rank']);
            $table->unique(['ranking_id', 'proposal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranking_items');
        Schema::dropIfExists('rankings');
    }
};
