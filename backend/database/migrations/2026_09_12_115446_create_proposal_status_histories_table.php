<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_status_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('proposal_id')
                ->constrained('proposals')
                ->cascadeOnDelete();

            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);

            $table->foreignUuid('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            $table->string('request_id', 100)->nullable();
            $table->timestamp('changed_at');

            $table->timestamps();

            $table->index([
                'proposal_id',
                'changed_at',
            ]);

            $table->index([
                'from_status',
                'to_status',
            ]);

            $table->index('changed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_status_histories');
    }
};
