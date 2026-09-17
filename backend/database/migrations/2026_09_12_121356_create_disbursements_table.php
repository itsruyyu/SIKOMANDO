<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disbursements', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('disbursement_plan_id');
            $table->uuid('proposal_id');

            $table->unsignedInteger('stage_number');

            $table->string('disbursement_number', 100)
                ->nullable()
                ->unique();

            $table->decimal('planned_amount', 18, 2);
            $table->decimal('approved_amount', 18, 2)->nullable();
            $table->decimal('paid_amount', 18, 2)->default(0);

            $table->string('status', 50)
                ->default('planned')
                ->index();

            $table->date('planned_date')->nullable();
            $table->date('approved_date')->nullable();
            $table->date('paid_date')->nullable();

            $table->text('notes')->nullable();

            $table->timestampsTz();

            $table->foreign('disbursement_plan_id')
                ->references('id')
                ->on('disbursement_plans')
                ->cascadeOnDelete();

            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
                ->restrictOnDelete();

            $table->unique([
                'disbursement_plan_id',
                'stage_number',
            ]);

            $table->index([
                'proposal_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disbursements');
    }
};
