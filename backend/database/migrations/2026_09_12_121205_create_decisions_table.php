<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decisions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('proposal_id');
            $table->uuid('approval_id')->nullable();
            $table->uuid('issued_by');

            $table->string('decision_number', 150)
                ->nullable()
                ->unique();

            $table->string('decision_type', 100)
                ->default('grant_approval')
                ->index();

            $table->string('status', 50)
                ->default('draft')
                ->index();

            $table->string('result', 50)
                ->nullable()
                ->index();

            $table->date('decision_date')->nullable();

            $table->decimal('approved_amount', 18, 2)
                ->nullable();

            $table->text('title')->nullable();
            $table->text('summary')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestampTz('issued_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();

            $table->uuid('cancelled_by')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestampsTz();

            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
                ->restrictOnDelete();

            $table->foreign('approval_id')
                ->references('id')
                ->on('approvals')
                ->nullOnDelete();

            $table->foreign('issued_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('cancelled_by')
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
        Schema::dropIfExists('decisions');
    }
};