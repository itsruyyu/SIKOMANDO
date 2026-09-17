<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_actions', function (Blueprint $table) {
            $table->dropUnique(['approval_id', 'level']);
            $table->index(['approval_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::table('approval_actions', function (Blueprint $table) {
            $table->dropIndex(['approval_id', 'level']);
            $table->unique(['approval_id', 'level']);
        });
    }
};

