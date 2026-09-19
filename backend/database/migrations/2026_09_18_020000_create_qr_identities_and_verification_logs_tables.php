<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_identities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('token', 64)->unique();
            $table->string('qr_type', 50)->index();

            // Polymorphic relation to qrable entity
            $table->string('qrable_type', 150);
            $table->uuid('qrable_id');

            $table->string('qr_code_path')->nullable();
            $table->string('verification_url', 500)->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->uuid('superseded_by_id')->nullable()->index();
            $table->unsignedInteger('scan_count')->default(0);
            $table->timestampTz('last_scanned_at')->nullable();
            $table->timestampTz('expires_at')->nullable()->index();

            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();

            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();

            $table->index(['qrable_type', 'qrable_id']);
            $table->index(['qrable_type', 'qrable_id', 'status']);
            $table->index(['token', 'status']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('qr_verification_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('qr_identity_id')->nullable()->constrained('qr_identities')->nullOnDelete();
            $table->string('token', 120)->nullable()->index();
            $table->foreignUuid('verifier_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('verification_status', 30)->index();
            $table->string('scan_context', 50)->default('public_web')->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('scanned_at')->useCurrent()->index();

            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();

            $table->index(['qr_identity_id', 'scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_verification_logs');
        Schema::dropIfExists('qr_identities');
    }
};
