<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('verification_id');

            $table->uuid('requirement_id')
                ->nullable();

            $table->uuid('document_type_id')
                ->nullable();

            $table->uuid('checked_by')
                ->nullable();

            $table->string('item_code', 100)
                ->nullable()
                ->index();

            $table->string('item_name', 255);

            $table->string('result', 50)
                ->default('pending')
                ->index();

            $table->text('notes')->nullable();

            $table->timestampTz('checked_at')->nullable();

            $table->timestampsTz();

            $table->foreign('verification_id')
                ->references('id')
                ->on('verifications')
                ->cascadeOnDelete();

            $table->foreign('requirement_id')
                ->references('id')
                ->on('requirements')
                ->nullOnDelete();

            $table->foreign('document_type_id')
                ->references('id')
                ->on('document_types')
                ->nullOnDelete();

            $table->foreign('checked_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index([
                'verification_id',
                'result',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_items');
    }
};