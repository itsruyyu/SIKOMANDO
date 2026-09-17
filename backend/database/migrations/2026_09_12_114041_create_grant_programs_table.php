<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grant_programs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('code', 100)->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();

            $table->string('fiscal_year', 4);
            $table->string('status', 30)->default('draft')->index();

            $table->date('registration_start_at')->nullable();
            $table->date('registration_end_at')->nullable();

            $table->decimal('minimum_amount', 18, 2)->nullable();
            $table->decimal('maximum_amount', 18, 2)->nullable();
            $table->decimal('total_budget', 18, 2)->nullable();

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

            $table->index(['fiscal_year', 'status']);
            $table->index(['is_active', 'registration_start_at', 'registration_end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grant_programs');
    }
};
