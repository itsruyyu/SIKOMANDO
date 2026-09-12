<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_records', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('proposal_id');
            $table->uuid('created_by');

            $table->string('monitoring_number', 100)
                ->nullable()
                ->unique();

            $table->string('monitoring_type', 100)
                ->default('periodic')
                ->index();

            $table->string('status', 50)
                ->default('draft')
                ->index();

            $table->date('monitoring_date')->nullable();

            $table->decimal('progress_percentage', 5, 2)
                ->nullable();

            $table->decimal('financial_progress_percentage', 5, 2)
                ->nullable();

            $table->text('summary')->nullable();
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('notes')->nullable();

            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('verified_at')->nullable();

            $table->uuid('verified_by')->nullable();

            $table->timestampsTz();

            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
                ->restrictOnDelete();

            $table->foreign('created_by')
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
        Schema::dropIfExists('monitoring_records');
    }
};