<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('actor_id')->nullable();

            $table->string('action', 100)
                ->index();

            $table->string('module', 100)
                ->index();

            $table->string('entity_type', 150)
                ->nullable()
                ->index();

            $table->uuid('entity_id')
                ->nullable()
                ->index();

            $table->string('request_id', 150)
                ->nullable()
                ->index();

            $table->string('ip_address', 45)
                ->nullable();

            $table->text('user_agent')->nullable();

            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->jsonb('metadata')->nullable();

            $table->timestampTz('occurred_at');

            $table->foreign('actor_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index([
                'module',
                'action',
            ]);

            $table->index([
                'entity_type',
                'entity_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};