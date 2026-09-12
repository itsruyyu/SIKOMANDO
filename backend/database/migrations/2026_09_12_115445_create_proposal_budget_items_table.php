<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_budget_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('proposal_id')
                ->constrained('proposals')
                ->cascadeOnDelete();

            $table->string('category', 150);
            $table->string('item_name', 255);
            $table->text('description')->nullable();

            $table->decimal('quantity', 18, 4);
            $table->string('unit', 50);

            $table->decimal('unit_price', 18, 2);
            $table->decimal('subtotal', 18, 2);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index([
                'proposal_id',
                'category',
            ]);

            $table->index([
                'proposal_id',
                'sort_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_budget_items');
    }
};