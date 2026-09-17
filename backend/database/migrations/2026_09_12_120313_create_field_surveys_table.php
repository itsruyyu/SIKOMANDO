<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_surveys', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('proposal_id');
            $table->uuid('surveyor_id');

            $table->string('survey_number', 100)
                ->nullable()
                ->unique();

            $table->string('status', 50)
                ->default('assigned')
                ->index();

            $table->string('result', 50)
                ->nullable()
                ->index();

            $table->date('scheduled_date')->nullable();

            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();

            $table->string('location_name', 255)->nullable();
            $table->text('location_address')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->text('summary')->nullable();
            $table->text('recommendation')->nullable();
            $table->text('notes')->nullable();

            $table->timestampsTz();

            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
                ->restrictOnDelete();

            $table->foreign('surveyor_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->index([
                'proposal_id',
                'status',
            ]);

            $table->index([
                'surveyor_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_surveys');
    }
};
