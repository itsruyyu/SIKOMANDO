<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('policy_configuration_id')
                ->constrained('policy_configurations')
                ->cascadeOnDelete();

            $table->string('version_number', 50);
            $table->jsonb('configuration_data');

            $table->string('status', 50)->default('draft')->index();

            $table->dateTime('effective_from')->nullable();
            $table->dateTime('effective_until')->nullable();

            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignUuid('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            $table->timestamps();

            $table->unique([
                'policy_configuration_id',
                'version_number',
            ]);

            $table->index([
                'policy_configuration_id',
                'status',
            ]);

            $table->index([
                'effective_from',
                'effective_until',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_versions');
    }
};