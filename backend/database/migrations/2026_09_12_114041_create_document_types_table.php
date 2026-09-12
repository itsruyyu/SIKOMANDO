<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('code', 100)->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();

            $table->string('scope', 50)->default('proposal')->index();
            $table->string('allowed_mime_types', 500)->nullable();
            $table->unsignedInteger('max_size_kb')->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['scope', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};