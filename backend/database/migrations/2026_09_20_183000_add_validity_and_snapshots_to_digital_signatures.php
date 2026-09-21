<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_signatures', function (Blueprint $table) {
            $table->timestampTz('valid_from')->nullable()->after('signed_at');
            $table->timestampTz('valid_until')->nullable()->after('valid_from')->index();

            $table->string('document_disk', 50)->nullable()->after('document_hash');
            $table->string('document_path', 500)->nullable()->after('document_disk');

            $table->string('signer_name_snapshot', 255)->nullable()->after('signature_profile_id');
            $table->string('signer_position_snapshot', 255)->nullable()->after('signer_name_snapshot');
            $table->string('signer_nip_snapshot', 50)->nullable()->after('signer_position_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('digital_signatures', function (Blueprint $table) {
            $table->dropColumn([
                'valid_from',
                'valid_until',
                'document_disk',
                'document_path',
                'signer_name_snapshot',
                'signer_position_snapshot',
                'signer_nip_snapshot',
            ]);
        });
    }
};

