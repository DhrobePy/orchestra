<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('voucher_number')->unique();
            $table->date('payment_date');
            $table->foreignId('purchase_order_id')->constrained('purchase_orders');
            $table->string('po_number'); // cached
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->string('supplier_name'); // cached
            $table->unsignedBigInteger('branch_id')->nullable();

            // Amounts
            $table->decimal('amount_paid', 15, 2);
            $table->enum('payment_type', ['advance', 'regular', 'final', 'adjustment'])->default('regular');

            // Method
            $table->enum('payment_method', ['bank_transfer', 'cash', 'cheque', 'mobile_banking', 'other'])->default('cash');
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('bank_name')->nullable(); // cached
            $table->unsignedBigInteger('cash_account_id')->nullable();
            $table->string('handled_by')->nullable()->comment('Employee name for cash payments');
            $table->string('reference_number')->nullable()->comment('Cheque number / transaction ID');
            $table->date('cheque_date')->nullable();
            $table->enum('cheque_status', ['pending', 'cleared', 'bounced', 'cancelled'])->nullable();

            // Status
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('posted');
            $table->boolean('is_posted')->default(true);

            // Approval (when payment_approval feature is on)
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Accounting
            $table->unsignedBigInteger('journal_entry_id')->nullable();

            // Meta
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
    }
};
