<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('monitoring_record_id');

            $table->string('indicator_code', 100)
                ->nullable()
                ->index();

            $table->string('indicator_name', 255);

            $table->text('description')->nullable();

            $table->decimal('target_value', 18, 4)
                ->nullable();

            $table->decimal('actual_value', 18, 4)
                ->nullable();

            $table->string('unit', 100)
                ->nullable();

            $table->string('status', 50)
                ->default('reported')
                ->index();

            $table->text('notes')->nullable();

            $table->timestampsTz();

            $table->foreign('monitoring_record_id')
                ->references('id')
                ->on('monitoring_records')
                ->cascadeOnDelete();

            $table->index([
                'monitoring_record_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_items');
    }
};
