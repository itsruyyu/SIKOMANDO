<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_criteria', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('code', 100)->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();

            $table->string('criterion_type', 50)->default('score');
            $table->decimal('default_weight', 8, 4)->nullable();
            $table->decimal('minimum_score', 8, 2)->nullable();
            $table->decimal('maximum_score', 8, 2)->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['criterion_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_criteria');
    }
};