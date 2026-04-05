<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained();
    $table->string('product_name');
    $table->string('sku');
    $table->decimal('quantity', 15, 2);
    $table->decimal('unit_cost', 15, 2);
    $table->decimal('total_cost', 15, 2);
    $table->decimal('received_quantity', 15, 2)->default(0);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
