<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('receipt_number', 100)->unique();
            $table->foreignUuid('proposal_id')->constrained('proposals')->restrictOnDelete();

            $table->foreignUuid('disbursement_id')->nullable()->constrained('disbursements')->nullOnDelete();
            $table->foreignUuid('realization_package_id')->nullable()->constrained('realization_packages')->nullOnDelete();

            $table->string('payer_name', 255);
            $table->string('recipient_name', 255);
            $table->decimal('amount', 18, 2)->default(0);
            $table->date('receipt_date');
            $table->text('purpose');

            $table->string('status', 30)->default('draft')->index();

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->index(['proposal_id', 'status']);
        });

        Schema::create('handovers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('handover_number', 100)->unique();
            $table->foreignUuid('proposal_id')->constrained('proposals')->restrictOnDelete();

            $table->foreignUuid('realization_package_id')->nullable()->constrained('realization_packages')->nullOnDelete();

            $table->date('handover_date');
            $table->string('giver_name', 255);
            $table->string('giver_position', 255)->nullable();
            $table->string('recipient_name', 255);
            $table->string('recipient_position', 255)->nullable();

            $table->string('status', 30)->default('draft')->index();
            $table->string('location', 255)->nullable();
            $table->text('notes')->nullable();

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->index(['proposal_id', 'status']);
        });

        Schema::create('handover_items', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('handover_id')->constrained('handovers')->cascadeOnDelete();
            $table->foreignUuid('realization_item_id')->constrained('realization_items')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->unique(['handover_id', 'realization_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handover_items');
        Schema::dropIfExists('handovers');
        Schema::dropIfExists('receipts');
    }
};
