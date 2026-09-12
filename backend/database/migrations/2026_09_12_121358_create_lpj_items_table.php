<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lpj_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('lpj_submission_id');

            $table->string('category', 150);
            $table->string('item_name', 255);

            $table->text('description')->nullable();

            $table->decimal('quantity', 14, 4)
                ->default(1);

            $table->string('unit', 50)
                ->default('unit');

            $table->decimal('unit_price', 18, 2);
            $table->decimal('subtotal', 18, 2);

            $table->string('status', 50)
                ->default('reported')
                ->index();

            $table->text('notes')->nullable();

            $table->timestampsTz();

            $table->foreign('lpj_submission_id')
                ->references('id')
                ->on('lpj_submissions')
                ->cascadeOnDelete();

            $table->index([
                'lpj_submission_id',
                'category',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lpj_items');
    }
};