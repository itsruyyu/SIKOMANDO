<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signature_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('name', 255);
            $table->string('position', 255);
            $table->string('nip', 50)->nullable()->index();
            $table->string('signature_image_path', 500)->nullable();
            $table->string('disk', 50)->default('private');

            $table->string('status', 30)->default('active')->index();
            $table->string('authority_level', 50)->default('officer');
            $table->date('effective_start_date')->nullable();
            $table->date('effective_end_date')->nullable();

            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['user_id', 'status']);
        });

        Schema::create('digital_signatures', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Polymorphic relation to signable entity (e.g. DecisionDocument, Handover, Receipt, etc.)
            $table->string('signable_type', 150);
            $table->uuid('signable_id');

            $table->uuid('document_version_id')->nullable()->index();
            $table->foreignUuid('signer_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('signature_profile_id')->nullable()->constrained('signature_profiles')->nullOnDelete();

            $table->string('signature_type', 50)->default('internal');
            $table->string('status', 50)->default('pending_signature')->index();

            $table->string('document_hash', 128)->nullable()->index(); // SHA-256

            $table->timestampTz('signed_at')->nullable();
            $table->timestampTz('rejected_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();

            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->jsonb('metadata')->nullable();

            $table->timestampsTz();

            $table->index(['signable_type', 'signable_id']);
            $table->index(['signable_type', 'signable_id', 'status']);
            $table->index(['signer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_signatures');
        Schema::dropIfExists('signature_profiles');
    }
};

