<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('approval_id');
            $table->uuid('actor_id');

            $table->unsignedInteger('level');

            $table->string('action', 50)
                ->index();

            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestampTz('acted_at');

            $table->timestampsTz();

            $table->foreign('approval_id')
                ->references('id')
                ->on('approvals')
                ->cascadeOnDelete();

            $table->foreign('actor_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->unique([
                'approval_id',
                'level',
            ]);

            $table->index([
                'approval_id',
                'action',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_actions');
    }
};
