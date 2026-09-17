<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('code', 100)->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();

            $table->string('scope', 100)->default('global')->index();
            $table->boolean('is_active')->default(true)->index();

            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignUuid('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_configurations');
    }
};
