<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('code', 100)->unique();
            $table->string('name', 255);

            $table->string('organization_type', 100);
            $table->text('description')->nullable();

            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();

            $table->foreignUuid('province_id')
                ->nullable()
                ->constrained('provinces')
                ->nullOnDelete();

            $table->foreignUuid('regency_id')
                ->nullable()
                ->constrained('regencies')
                ->nullOnDelete();

            $table->foreignUuid('district_id')
                ->nullable()
                ->constrained('districts')
                ->nullOnDelete();

            $table->foreignUuid('village_id')
                ->nullable()
                ->constrained('villages')
                ->nullOnDelete();

            $table->string('postal_code', 10)->nullable();

            $table->string('legal_status', 100)->nullable();
            $table->string('registration_number', 150)->nullable();

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
            $table->softDeletes();

            $table->index(['organization_type', 'is_active']);
            $table->index(['province_id', 'regency_id']);
            $table->index(['name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
