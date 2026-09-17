<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('proposal_id');
            $table->uuid('evaluator_id');

            $table->string('evaluation_number', 100)
                ->nullable()
                ->unique();

            $table->string('status', 50)
                ->default('IN_PROGRESS')
                ->index();

            $table->decimal('total_score', 12, 4)
                ->nullable();

            $table->decimal('final_score', 12, 4)
                ->nullable();

            $table->string('result', 50)
                ->nullable()
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

            $table->foreign('evaluator_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->index([
                'proposal_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
