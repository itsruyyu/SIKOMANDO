<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->foreignUuid('assigned_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('assignment_type', 30); // VERIFICATION, EVALUATION, FIELD_SURVEY, AUDIT
            $table->string('status', 20)->default('ASSIGNED'); // ASSIGNED, IN_PROGRESS, COMPLETED, REVOKED
            $table->foreignUuid('assigned_by')->constrained('users');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignUuid('revoked_by')->nullable()->constrained('users');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['proposal_id', 'assignment_type']);
            $table->index(['assigned_user_id', 'status']);
            $table->index(['assignment_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_assignments');
    }
};
