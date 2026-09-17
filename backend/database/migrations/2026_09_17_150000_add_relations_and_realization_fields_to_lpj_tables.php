<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lpj_submissions', function (Blueprint $table) {
            $table->uuid('organization_id')->nullable()->after('proposal_id');
            $table->uuid('grant_program_id')->nullable()->after('organization_id');
            $table->uuid('decision_id')->nullable()->after('grant_program_id');
            $table->uuid('disbursement_id')->nullable()->after('decision_id');
            $table->unsignedInteger('stage_number')->nullable()->after('disbursement_id');

            $table->uuid('approved_by')->nullable()->after('verified_by');
            $table->uuid('closed_by')->nullable()->after('approved_by');

            $table->timestampTz('approved_at')->nullable()->after('verified_at');
            $table->timestampTz('closed_at')->nullable()->after('approved_at');

            $table->text('revision_reason')->nullable()->after('verification_notes');
            $table->text('rejection_reason')->nullable()->after('revision_reason');

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->nullOnDelete();

            $table->foreign('grant_program_id')
                ->references('id')
                ->on('grant_programs')
                ->nullOnDelete();

            $table->foreign('decision_id')
                ->references('id')
                ->on('decisions')
                ->nullOnDelete();

            $table->foreign('disbursement_id')
                ->references('id')
                ->on('disbursements')
                ->nullOnDelete();

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('closed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['grant_program_id', 'status']);
            $table->index(['organization_id', 'status']);
        });

        Schema::table('lpj_items', function (Blueprint $table) {
            $table->uuid('proposal_budget_item_id')->nullable()->after('lpj_submission_id');
            $table->string('budget_item', 255)->nullable()->after('proposal_budget_item_id');
            $table->decimal('planned_amount', 18, 2)->default(0)->after('subtotal');
            $table->decimal('realized_amount', 18, 2)->default(0)->after('planned_amount');
            $table->decimal('variance', 18, 2)->default(0)->after('realized_amount');
            $table->uuid('evidence_document_id')->nullable()->after('variance');
            $table->text('evidence_path')->nullable()->after('evidence_document_id');

            $table->foreign('proposal_budget_item_id')
                ->references('id')
                ->on('proposal_budget_items')
                ->nullOnDelete();

            $table->foreign('evidence_document_id')
                ->references('id')
                ->on('lpj_documents')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lpj_items', function (Blueprint $table) {
            $table->dropForeign(['proposal_budget_item_id']);
            $table->dropForeign(['evidence_document_id']);
            $table->dropColumn([
                'proposal_budget_item_id',
                'budget_item',
                'planned_amount',
                'realized_amount',
                'variance',
                'evidence_document_id',
                'evidence_path',
            ]);
        });

        Schema::table('lpj_submissions', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['grant_program_id']);
            $table->dropForeign(['decision_id']);
            $table->dropForeign(['disbursement_id']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['closed_by']);
            $table->dropIndex(['grant_program_id', 'status']);
            $table->dropIndex(['organization_id', 'status']);
            $table->dropColumn([
                'organization_id',
                'grant_program_id',
                'decision_id',
                'disbursement_id',
                'stage_number',
                'approved_by',
                'closed_by',
                'approved_at',
                'closed_at',
                'revision_reason',
                'rejection_reason',
            ]);
        });
    }
};
