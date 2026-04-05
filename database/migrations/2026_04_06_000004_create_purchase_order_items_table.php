<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->enum('item_type', ['raw_material', 'finished_goods', 'packaging', 'service', 'other'])->default('raw_material');
            $table->string('item_description');
            $table->string('item_code')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->decimal('quantity', 15, 2);
            $table->string('unit_of_measure')->default('KG');
            $table->decimal('unit_price', 15, 4);
            $table->decimal('total_value', 15, 2);
            $table->decimal('received_qty', 15, 2)->default(0);
            $table->date('expected_delivery_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
