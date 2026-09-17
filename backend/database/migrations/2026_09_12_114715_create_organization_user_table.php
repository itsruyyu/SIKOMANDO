<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_user', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();

            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('membership_role', 100)
                ->default('member');

            $table->boolean('is_primary_contact')
                ->default(false);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();

            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
            $table->index(['user_id', 'is_active']);
            $table->index(['organization_id', 'membership_role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_user');
    }
};
