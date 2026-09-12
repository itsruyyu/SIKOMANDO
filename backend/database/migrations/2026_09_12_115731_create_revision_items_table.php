<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revision_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('revision_id');

            $table->string('item_code', 100)
                ->nullable()
                ->index();

            $table->string('field_name', 150)
                ->nullable();

            $table->string('description', 255);

            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();

            $table->string('status', 50)
                ->default('open')
                ->index();

            $table->text('notes')->nullable();

            $table->timestampTz('resolved_at')->nullable();

            $table->timestampsTz();

            $table->foreign('revision_id')
                ->references('id')
                ->on('revisions')
                ->cascadeOnDelete();

            $table->index([
                'revision_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revision_items');
    }
};