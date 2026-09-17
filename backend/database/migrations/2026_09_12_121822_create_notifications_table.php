<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('user_id');

            $table->string('type', 100)
                ->index();

            $table->string('title', 255);
            $table->text('message');

            $table->string('entity_type', 150)
                ->nullable();

            $table->uuid('entity_id')
                ->nullable();

            $table->jsonb('data')->nullable();

            $table->timestampTz('read_at')->nullable();
            $table->timestampTz('sent_at')->nullable();

            $table->timestampsTz();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->index([
                'user_id',
                'read_at',
            ]);

            $table->index([
                'entity_type',
                'entity_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
