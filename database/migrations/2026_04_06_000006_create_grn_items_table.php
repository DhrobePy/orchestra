<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grn_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('goods_received_notes')->cascadeOnDelete();
            $table->unsignedBigInteger('purchase_order_item_id')->nullable();
            $table->enum('item_type', ['raw_material', 'finished_goods', 'packaging', 'service', 'other'])->default('raw_material');
            $table->string('item_description');
            $table->string('item_code')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->decimal('ordered_quantity', 15, 2);
            $table->decimal('expected_quantity', 15, 2)->nullable();
            $table->decimal('received_quantity', 15, 2);
            $table->decimal('accepted_quantity', 15, 2);
            $table->decimal('rejected_quantity', 15, 2)->default(0);
            $table->string('unit_of_measure');
            $table->decimal('unit_price', 15, 4);
            $table->decimal('line_total', 15, 2);
            $table->decimal('weight_variance', 15, 2)->nullable();
            $table->decimal('variance_percentage', 8, 4)->nullable();
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('storage_location')->nullable();
            $table->enum('condition_status', ['good', 'damaged', 'expired', 'other'])->default('good');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_items');
    }
};
