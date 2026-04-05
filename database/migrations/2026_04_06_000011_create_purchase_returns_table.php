<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('return_number')->unique();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('grn_id')->nullable()->constrained('goods_received_notes')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->date('return_date');
            $table->enum('return_reason', [
                'damaged',
                'defective',
                'wrong_quantity',
                'quality_issue',
                'wrong_item',
                'expired',
                'other',
            ]);
            $table->text('description')->nullable();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->enum('refund_method', ['cash_refund', 'credit_note', 'replacement', 'adjustment'])->default('credit_note');
            $table->enum('refund_status', ['pending', 'processed', 'completed'])->default('pending');
            $table->enum('status', ['draft', 'approved', 'completed', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
