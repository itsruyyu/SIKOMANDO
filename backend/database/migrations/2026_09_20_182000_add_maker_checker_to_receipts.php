<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('verified_at')->nullable();
        });

        // Maker-checker database constraint (DB-03): verified_by must not equal created_by
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE receipts ADD CONSTRAINT chk_receipt_maker_checker CHECK (verified_by IS NULL OR verified_by <> created_by)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE receipts DROP CONSTRAINT IF EXISTS chk_receipt_maker_checker');
        }

        Schema::table('receipts', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['verified_by', 'verified_at']);
        });
    }
};

