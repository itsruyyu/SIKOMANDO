<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('grant_program_id')
                ->nullable()
                ->constrained('grant_programs')
                ->nullOnDelete();

            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->string('category', 100)->default('general')->index();

            $table->text('excerpt')->nullable();
            $table->text('content');

            $table->string('status', 50)->default('draft')->index();
            $table->boolean('is_pinned')->default(false)->index();
            $table->timestampTz('published_at')->nullable()->index();

            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignUuid('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['grant_program_id', 'status']);
            $table->index(['category', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};

