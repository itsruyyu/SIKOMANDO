<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('proposal_id');
            $table->uuid('recommended_by');

            $table->string('recommendation_number', 100)
                ->nullable()
                ->unique();

            $table->string('status', 50)
                ->default('draft')
                ->index();

            $table->string('result', 50)
                ->nullable()
                ->index();

            $table->decimal('recommended_amount', 18, 2)
                ->nullable();

            $table->text('summary')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('completed_at')->nullable();

            $table->timestampsTz();

            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
                ->restrictOnDelete();

            $table->foreign('recommended_by')
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
        Schema::dropIfExists('recommendations');
    }
};