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
        Schema::create('purchase_orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
    $table->string('po_number')->unique();
    $table->enum('status', [
        'draft', 'sent', 'acknowledged',
        'partial', 'received', 'cancelled'
    ])->default('draft');
    $table->date('order_date');
    $table->date('expected_date')->nullable();
    $table->date('received_date')->nullable();
    $table->decimal('subtotal', 15, 2)->default(0);
    $table->decimal('tax', 15, 2)->default(0);
    $table->decimal('total', 15, 2)->default(0);
    $table->string('currency', 3)->default('USD');
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->softDeletes();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
