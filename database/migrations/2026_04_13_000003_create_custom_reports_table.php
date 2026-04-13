<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('data_source'); // credit_orders | customers | etc.
            $table->json('columns');       // [{field, label, type, aggregate}]
            $table->json('filters')->nullable();  // [{field, operator, label, filterable}]
            $table->string('sort_by')->nullable();
            $table->string('sort_dir')->default('desc');
            $table->string('group_by')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_reports');
    }
};
