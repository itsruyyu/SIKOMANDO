<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranking_rule_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('grant_program_id')
                ->constrained('grant_programs')
                ->cascadeOnDelete();

            $table->foreignUuid('policy_version_id')
                ->nullable()
                ->constrained('policy_versions')
                ->restrictOnDelete();

            $table->string('rule_code', 100);
            $table->string('rule_name', 200);

            $table->jsonb('rule_definition');
            $table->unsignedInteger('priority')->default(0);

            $table->string('status', 50)->default('draft')->index();

            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignUuid('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->index([
                'grant_program_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranking_rule_configurations');
    }
};
