<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lpj_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('proposal_id');
            $table->uuid('submitted_by');
            $table->uuid('verified_by')->nullable();

            $table->string('lpj_number', 100)
                ->nullable()
                ->unique();

            $table->string('status', 50)
                ->default('draft')
                ->index();

            $table->decimal('total_received', 18, 2)->default(0);
            $table->decimal('total_spent', 18, 2)->default(0);
            $table->decimal('remaining_balance', 18, 2)->default(0);

            $table->text('summary')->nullable();
            $table->text('notes')->nullable();
            $table->text('verification_notes')->nullable();

            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('verified_at')->nullable();

            $table->timestampsTz();

            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
                ->restrictOnDelete();

            $table->foreign('submitted_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('verified_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index([
                'proposal_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lpj_submissions');
    }
};