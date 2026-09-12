<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disbursement_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('disbursement_id');
            $table->uuid('recorded_by');

            $table->string('transaction_number', 150)
                ->nullable()
                ->unique();

            $table->string('transaction_type', 100)
                ->default('transfer')
                ->index();

            $table->decimal('amount', 18, 2);

            $table->string('status', 50)
                ->default('recorded')
                ->index();

            $table->date('transaction_date');

            $table->string('bank_reference', 150)
                ->nullable();

            $table->string('recipient_name', 255)
                ->nullable();

            $table->text('notes')->nullable();

            $table->timestampsTz();

            $table->foreign('disbursement_id')
                ->references('id')
                ->on('disbursements')
                ->restrictOnDelete();

            $table->foreign('recorded_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->index([
                'disbursement_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disbursement_transactions');
    }
};