<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('proposal_id');
            $table->uuid('recommendation_id')
                ->nullable();

            $table->string('approval_number', 100)
                ->nullable()
                ->unique();

            $table->string('approval_type', 100)
                ->default('proposal')
                ->index();

            $table->unsignedInteger('required_levels')
                ->default(1);

            $table->unsignedInteger('current_level')
                ->default(1);

            $table->string('status', 50)
                ->default('pending')
                ->index();

            $table->text('summary')->nullable();
            $table->text('notes')->nullable();

            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();

            $table->timestampsTz();

            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
                ->restrictOnDelete();

            $table->foreign('recommendation_id')
                ->references('id')
                ->on('recommendations')
                ->nullOnDelete();

            $table->index([
                'proposal_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};