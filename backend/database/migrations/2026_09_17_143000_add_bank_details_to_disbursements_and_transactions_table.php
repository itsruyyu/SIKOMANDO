<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disbursements', function (Blueprint $table) {
            $table->string('bank_name', 100)->nullable()->after('notes');
            $table->string('bank_account_number', 50)->nullable()->after('bank_name');
            $table->string('bank_account_name', 255)->nullable()->after('bank_account_number');
        });

        Schema::table('disbursement_transactions', function (Blueprint $table) {
            $table->string('bank_name', 100)->nullable()->after('notes');
            $table->string('bank_account_number', 50)->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('disbursements', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'bank_account_number',
                'bank_account_name',
            ]);
        });

        Schema::table('disbursement_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'bank_account_number',
            ]);
        });
    }
};
