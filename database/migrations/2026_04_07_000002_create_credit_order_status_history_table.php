<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_order_status_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('credit_order_id');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('credit_order_id')
                ->references('id')->on('credit_orders')
                ->onDelete('cascade');

            $table->foreign('changed_by')
                ->references('id')->on('users')
                ->onDelete('set null');

            $table->index('credit_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_order_status_history');
    }
};
