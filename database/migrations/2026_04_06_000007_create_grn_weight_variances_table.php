<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grn_weight_variances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('goods_received_notes')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders');
            $table->date('variance_date');
            $table->decimal('expected_quantity', 15, 2);
            $table->decimal('received_quantity', 15, 2);
            $table->decimal('variance_quantity', 15, 2)->comment('Can be negative (loss)');
            $table->decimal('variance_percentage', 8, 4);
            $table->enum('variance_type', ['loss', 'gain', 'normal']);
            $table->decimal('threshold_percentage', 5, 2)->comment('Config value at time of recording');
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent(); // No updated_at — immutable log
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_weight_variances');
    }
};
