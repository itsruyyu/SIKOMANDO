<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('code', 100)->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();

            $table->string('scope', 50)->default('proposal')->index();
            $table->string('requirement_type', 50)->default('document');

            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();

            $table->index(['scope', 'requirement_type']);
            $table->index(['is_mandatory', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requirements');
    }
};