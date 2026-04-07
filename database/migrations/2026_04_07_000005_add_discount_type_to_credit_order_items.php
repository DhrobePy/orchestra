<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_order_items', function (Blueprint $table) {
            // 'per_unit' = flat ৳ discount per unit, 'percent' = % off line total, 'flat' = flat ৳ off line total
            $table->string('discount_type')->default('flat')->after('discount');
        });
    }

    public function down(): void
    {
        Schema::table('credit_order_items', function (Blueprint $table) {
            $table->dropColumn('discount_type');
        });
    }
};
