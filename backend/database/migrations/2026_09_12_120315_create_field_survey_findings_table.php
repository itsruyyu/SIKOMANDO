<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_survey_findings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('field_survey_id');

            $table->string('finding_code', 100)
                ->nullable()
                ->index();

            $table->string('finding_type', 100)
                ->nullable()
                ->index();

            $table->string('title', 255);

            $table->text('description');

            $table->string('severity', 50)
                ->default('medium')
                ->index();

            $table->text('recommended_action')->nullable();

            $table->string('status', 50)
                ->default('open')
                ->index();

            $table->timestampTz('resolved_at')->nullable();

            $table->timestampsTz();

            $table->foreign('field_survey_id')
                ->references('id')
                ->on('field_surveys')
                ->cascadeOnDelete();

            $table->index([
                'field_survey_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_survey_findings');
    }
};