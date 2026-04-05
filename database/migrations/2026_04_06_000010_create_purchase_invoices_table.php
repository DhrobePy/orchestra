<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invoice_number')->unique();
            $table->string('supplier_invoice_number')->nullable();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('grn_id')->nullable()->constrained('goods_received_notes')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->enum('invoice_type', ['goods', 'services', 'both'])->default('goods');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('other_charges', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2);
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'overdue'])->default('unpaid');
            $table->enum('status', ['draft', 'posted', 'paid', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
