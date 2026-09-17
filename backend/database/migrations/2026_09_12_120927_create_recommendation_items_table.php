<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('recommendation_id');

            $table->string('item_code', 100)
                ->nullable()
                ->index();

            $table->string('item_name', 255);

            $table->string('source_type', 100)
                ->nullable();

            $table->uuid('source_id')
                ->nullable();

            $table->string('result', 50)
                ->default('pending')
                ->index();

            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            $table->timestampsTz();

            $table->foreign('recommendation_id')
                ->references('id')
                ->on('recommendations')
                ->cascadeOnDelete();

            $table->index([
                'recommendation_id',
                'result',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_items');
    }
};
