<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('evaluation_id');
            $table->uuid('evaluation_criteria_id');

            $table->decimal('weight', 8, 4);
            $table->decimal('score', 12, 4)->nullable();
            $table->decimal('weighted_score', 12, 4)->nullable();

            $table->decimal('minimum_score', 12, 4)->nullable();
            $table->decimal('maximum_score', 12, 4)->nullable();

            $table->string('result', 50)
                ->default('PENDING')
                ->index();

            $table->text('notes')->nullable();

            $table->timestampTz('scored_at')->nullable();

            $table->timestampsTz();

            $table->foreign('evaluation_id')
                ->references('id')
                ->on('evaluations')
                ->cascadeOnDelete();

            $table->foreign('evaluation_criteria_id')
                ->references('id')
                ->on('evaluation_criteria')
                ->restrictOnDelete();

            $table->unique([
                'evaluation_id',
                'evaluation_criteria_id',
            ]);

            $table->index([
                'evaluation_id',
                'result',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_items');
    }
};
