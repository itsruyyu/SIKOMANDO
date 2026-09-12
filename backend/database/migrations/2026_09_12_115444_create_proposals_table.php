<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('proposal_number', 100)->unique();

            $table->foreignUuid('grant_program_id')
                ->constrained('grant_programs')
                ->restrictOnDelete();

            $table->foreignUuid('organization_id')
                ->constrained('organizations')
                ->restrictOnDelete();

            $table->foreignUuid('applicant_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('title', 255);
            $table->text('background')->nullable();
            $table->text('objectives')->nullable();
            $table->text('benefits')->nullable();
            $table->text('activities')->nullable();
            $table->text('expected_outputs')->nullable();

            $table->decimal('requested_amount', 18, 2)->default(0);
            $table->decimal('approved_amount', 18, 2)->nullable();

            $table->string('status', 50)->default('draft')->index();

            $table->unsignedInteger('revision_count')->default(0);

            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignUuid('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'grant_program_id',
                'status',
            ]);

            $table->index([
                'organization_id',
                'status',
            ]);

            $table->index([
                'applicant_id',
                'status',
            ]);

            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};