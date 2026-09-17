<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_survey_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('field_survey_id');

            $table->string('item_code', 100)
                ->nullable()
                ->index();

            $table->string('item_name', 255);

            $table->text('description')->nullable();

            $table->string('result', 50)
                ->default('pending')
                ->index();

            $table->text('notes')->nullable();

            $table->uuid('checked_by')
                ->nullable();

            $table->timestampTz('checked_at')->nullable();

            $table->timestampsTz();

            $table->foreign('field_survey_id')
                ->references('id')
                ->on('field_surveys')
                ->cascadeOnDelete();

            $table->foreign('checked_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index([
                'field_survey_id',
                'result',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_survey_items');
    }
};
