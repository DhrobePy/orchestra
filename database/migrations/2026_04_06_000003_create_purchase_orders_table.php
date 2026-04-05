<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('po_number')->unique();
            $table->date('po_date');
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('supplier_name'); // cached
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('branch_name')->nullable();

            // Commodity (single-item mode)
            $table->string('commodity_description')->nullable();
            $table->string('origin')->nullable()->comment('Wheat origin or any commodity origin');
            $table->decimal('quantity', 15, 2)->nullable();
            $table->string('unit_of_measure')->default('KG');
            $table->decimal('unit_price', 15, 4)->nullable();
            $table->decimal('total_order_value', 15, 2)->nullable();
            $table->date('expected_delivery_date')->nullable();

            // Payment configuration
            $table->enum('payment_basis', ['received_qty', 'expected_qty'])->default('received_qty');
            $table->enum('payment_terms', ['cod', 'credit', 'advance', 'partial_advance'])->default('cod');
            $table->unsignedInteger('credit_days')->nullable();
            $table->decimal('advance_percentage', 5, 2)->nullable();

            // Auto-calculated totals (maintained by ProcurementService)
            $table->decimal('total_received_qty', 15, 2)->default(0);
            $table->decimal('total_received_value', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('advance_paid', 15, 2)->default(0);
            $table->decimal('balance_payable', 15, 2)->default(0)->comment('payable_basis_amount - total_paid');
            $table->decimal('qty_yet_to_receive', 15, 2)->default(0)->comment('quantity - total_received_qty');

            // Status
            $table->enum('po_status', ['draft', 'submitted', 'approved', 'partial', 'completed', 'cancelled'])->default('draft');
            $table->enum('delivery_status', ['pending', 'partial', 'completed', 'over_received', 'closed'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'overpaid'])->default('unpaid');

            // Approval
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Meta
            $table->text('terms_conditions')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('currency')->default('BDT');
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
