<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('grant_program_id')
                ->nullable()
                ->constrained('grant_programs')
                ->cascadeOnDelete();

            $table->foreignUuid('policy_version_id')
                ->nullable()
                ->constrained('policy_versions')
                ->restrictOnDelete();

            $table->string('workflow_code', 100);
            $table->jsonb('workflow_definition');

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
                'workflow_code',
            ]);

            $table->index([
                'policy_version_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_configurations');
    }
};
