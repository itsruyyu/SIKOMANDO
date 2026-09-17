<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disbursement_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('proposal_id');
            $table->uuid('created_by');

            $table->string('plan_number', 100)
                ->nullable()
                ->unique();

            $table->unsignedInteger('total_stages')
                ->default(1);

            $table->decimal('planned_amount', 18, 2)
                ->default(0);

            $table->string('status', 50)
                ->default('draft')
                ->index();

            $table->text('notes')->nullable();

            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('approved_at')->nullable();

            $table->timestampsTz();

            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
                ->restrictOnDelete();

            $table->foreign('created_by')
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
        Schema::dropIfExists('disbursement_plans');
    }
};
