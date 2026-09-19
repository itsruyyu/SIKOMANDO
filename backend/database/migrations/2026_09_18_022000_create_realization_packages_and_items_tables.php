<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('realization_packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('proposal_id')->constrained('proposals')->restrictOnDelete();

            $table->string('package_number', 100)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();

            $table->string('status', 50)->default('draft')->index();
            $table->date('package_date')->nullable();
            $table->decimal('total_amount', 18, 2)->default(0);

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('verified_at')->nullable();

            $table->timestampsTz();

            $table->index(['proposal_id', 'status']);
        });

        Schema::create('realization_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('realization_package_id')->constrained('realization_packages')->cascadeOnDelete();

            // Traceability to proposal budget item (RAB)
            $table->foreignUuid('proposal_budget_item_id')->nullable()->constrained('proposal_budget_items')->nullOnDelete();

            $table->string('item_code', 100)->unique();
            $table->string('name', 255);
            $table->string('category', 100)->default('equipment');

            $table->string('serial_number', 150)->nullable()->index();
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->text('specification')->nullable();

            $table->decimal('quantity', 18, 4)->default(1);
            $table->string('unit', 50)->default('Unit');
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);

            $table->date('purchase_date')->nullable();
            $table->string('location_name', 255)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->string('condition', 30)->default('good');
            $table->string('status', 50)->default('created')->index();

            $table->string('responsible_person', 255)->nullable();
            $table->text('notes')->nullable();

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->index(['realization_package_id', 'status']);
            $table->index(['proposal_budget_item_id', 'status']);
        });

        Schema::create('realization_item_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('realization_item_id')->constrained('realization_items')->cascadeOnDelete();

            $table->string('action', 100);
            $table->string('status', 50);

            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->jsonb('metadata')->nullable();

            $table->timestampsTz();

            $table->index(['realization_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realization_item_histories');
        Schema::dropIfExists('realization_items');
        Schema::dropIfExists('realization_packages');
    }
};

