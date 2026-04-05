<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('grn_number')->unique();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->string('po_number'); // cached
            $table->date('grn_date');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->string('supplier_name'); // cached
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('receiving_branch')->nullable();

            // Transport
            $table->string('truck_number')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('delivery_note_number')->nullable();

            // Quantities (single-item mode)
            $table->decimal('expected_quantity', 15, 2)->nullable();
            $table->decimal('received_quantity', 15, 2);
            $table->decimal('accepted_quantity', 15, 2)->comment('After quality check');
            $table->decimal('rejected_quantity', 15, 2)->default(0);
            $table->string('unit_of_measure')->default('KG');
            $table->decimal('unit_price', 15, 4);
            $table->decimal('total_value', 15, 2)->comment('received_quantity * unit_price');

            // Variance
            $table->decimal('weight_variance', 15, 2)->nullable()->comment('received - expected');
            $table->decimal('variance_percentage', 8, 4)->nullable();
            $table->enum('variance_type', ['loss', 'gain', 'normal'])->nullable();
            $table->string('variance_remarks')->nullable();

            // Override payment basis for this GRN
            $table->enum('payment_basis_override', ['received_qty', 'expected_qty'])->nullable();

            // Status
            $table->enum('grn_status', ['draft', 'verified', 'posted', 'cancelled'])->default('draft');

            // Approval
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();

            // Accounting
            $table->unsignedBigInteger('journal_entry_id')->nullable();

            // Meta
            $table->text('condition_notes')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('updated_by')->nullable();

            // No softDeletes — GRNs are immutable financial records
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_received_notes');
    }
};
