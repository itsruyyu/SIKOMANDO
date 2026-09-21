<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('disbursement_transactions', function (Blueprint $table) {
            $table->uuid('idempotency_key')->nullable()->unique()->after('transaction_number');
            $table->unique('bank_reference', 'disbursement_transactions_bank_reference_unique');
        });

        // Add check constraints in PostgreSQL
        DB::statement('ALTER TABLE disbursement_transactions ADD CONSTRAINT chk_disbursement_tx_amount_positive CHECK (amount > 0)');
        DB::statement('ALTER TABLE disbursements ADD CONSTRAINT chk_disbursements_paid_ceiling CHECK (paid_amount >= 0 AND (approved_amount IS NULL OR paid_amount <= approved_amount))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE disbursements DROP CONSTRAINT IF EXISTS chk_disbursements_paid_ceiling');
        DB::statement('ALTER TABLE disbursement_transactions DROP CONSTRAINT IF EXISTS chk_disbursement_tx_amount_positive');

        Schema::table('disbursement_transactions', function (Blueprint $table) {
            $table->dropUnique('disbursement_transactions_bank_reference_unique');
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};

