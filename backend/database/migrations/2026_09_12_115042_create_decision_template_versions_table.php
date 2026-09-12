<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decision_template_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('decision_template_id')
                ->constrained('decision_templates')
                ->cascadeOnDelete();

            $table->string('version_number', 50);
            $table->text('template_content');

            $table->string('status', 50)->default('draft')->index();

            $table->dateTime('effective_from')->nullable();
            $table->dateTime('effective_until')->nullable();

            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignUuid('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->unique([
                'decision_template_id',
                'version_number',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decision_template_versions');
    }
};