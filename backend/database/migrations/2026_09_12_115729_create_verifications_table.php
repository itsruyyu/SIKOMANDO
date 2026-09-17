<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('proposal_id');
            $table->uuid('verifier_id');

            $table->string('verification_number', 100)
                ->nullable()
                ->unique();

            $table->string('status', 50)
                ->default('in_progress')
                ->index();

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

            $table->foreign('verifier_id')
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
        Schema::dropIfExists('verifications');
    }
};
