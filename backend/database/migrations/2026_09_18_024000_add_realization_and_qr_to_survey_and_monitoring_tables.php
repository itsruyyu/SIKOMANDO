<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_survey_findings', function (Blueprint $table) {
            $table->foreignUuid('realization_item_id')
                ->nullable()
                ->after('field_survey_item_id')
                ->constrained('realization_items')
                ->nullOnDelete();

            $table->string('scanned_qr_token', 64)
                ->nullable()
                ->after('realization_item_id')
                ->index();
        });

        Schema::table('monitoring_items', function (Blueprint $table) {
            $table->foreignUuid('realization_item_id')
                ->nullable()
                ->after('monitoring_record_id')
                ->constrained('realization_items')
                ->nullOnDelete();

            $table->string('scanned_qr_token', 64)
                ->nullable()
                ->after('realization_item_id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_items', function (Blueprint $table) {
            $table->dropForeign(['realization_item_id']);
            $table->dropColumn(['realization_item_id', 'scanned_qr_token']);
        });

        Schema::table('field_survey_findings', function (Blueprint $table) {
            $table->dropForeign(['realization_item_id']);
            $table->dropColumn(['realization_item_id', 'scanned_qr_token']);
        });
    }
};

