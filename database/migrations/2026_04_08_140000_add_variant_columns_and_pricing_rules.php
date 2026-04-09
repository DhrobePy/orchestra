<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('grade')->nullable()->after('weight_kg');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete()->after('grade');
            $table->date('effective_date')->nullable()->after('branch_id');
        });

        Schema::create('product_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            // 'manual' = each price set independently
            // 'formula' = derive from base price using branch + weight rules
            $table->string('mechanism')->default('manual');
            $table->foreignId('base_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->decimal('base_weight', 8, 2)->nullable()->comment('Reference weight (e.g. 50)');
            $table->decimal('branch_premium', 10, 2)->default(0)->comment('Added per non-base branch');
            $table->integer('weight_rounding')->default(5)->comment('Round to nearest N BDT');
            $table->decimal('weight_premium', 10, 2)->default(0)->comment('Added after proportional calc');
            $table->timestamps();

            $table->unique('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['grade', 'branch_id', 'effective_date']);
        });

        Schema::dropIfExists('product_pricing_rules');
    }
};
