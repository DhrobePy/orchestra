<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('bank_account_id')->nullable()->after('branch_id');
        });

        // Add opening_balance entries support to customer_ledger reference_type
        // (no schema change needed — reference_type is varchar, any value is fine)
    }

    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropColumn('bank_account_id');
        });
    }
};
