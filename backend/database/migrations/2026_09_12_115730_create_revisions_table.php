<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revisions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('proposal_id');
            $table->uuid('requested_by');

            $table->unsignedInteger('revision_number');

            $table->string('status', 50)
                ->default('requested')
                ->index();

            $table->text('reason');

            $table->text('completion_notes')->nullable();

            $table->timestampTz('requested_at')->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('completed_at')->nullable();

            $table->timestampsTz();

            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
                ->restrictOnDelete();

            $table->foreign('requested_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->unique([
                'proposal_id',
                'revision_number',
            ]);

            $table->index([
                'proposal_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisions');
    }
};
