<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('regency_id')
                ->constrained('regencies')
                ->restrictOnDelete();

            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['regency_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
