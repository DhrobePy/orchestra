<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->date('transaction_date');
            $table->enum('transaction_type', [
                'opening_balance',
                'purchase',
                'payment',
                'debit_note',
                'credit_note',
                'adjustment',
                'return',
            ]);
            $table->string('reference_type')->nullable()->comment('PurchaseOrder, GoodsReceivedNote, PurchasePayment, etc.');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number')->nullable()->comment('Human-readable reference');
            $table->decimal('debit_amount', 15, 2)->default(0)->comment('Payments out — reduce liability');
            $table->decimal('credit_amount', 15, 2)->default(0)->comment('Purchases in — increase liability');
            $table->decimal('running_balance', 15, 2)->comment('Maintained by ProcurementService');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent(); // No updated_at — immutable ledger
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_ledger_entries');
    }
};
