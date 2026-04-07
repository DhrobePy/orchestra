<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_orders', function (Blueprint $table) {
            // Priority: 1=urgent, 2=normal, 3=low
            $table->tinyInteger('priority')->default(2)->after('status');
            $table->string('payment_status')->default('unpaid')->after('priority'); // unpaid|partially_paid|paid
            $table->text('delivery_address')->nullable()->after('notes');

            // Branch assignment (by admin on approval of escalated)
            $table->unsignedBigInteger('assigned_branch_id')->nullable()->after('branch_id');

            // Approval tracking
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Escalation tracking
            $table->unsignedBigInteger('escalated_by')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->text('escalation_notes')->nullable();

            // Cancellation request tracking
            $table->unsignedBigInteger('cancellation_requested_by')->nullable();
            $table->timestamp('cancellation_requested_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            // Production tracking
            $table->unsignedBigInteger('production_started_by')->nullable();
            $table->timestamp('production_started_at')->nullable();
            $table->text('qc_notes')->nullable();
            $table->timestamp('qc_passed_at')->nullable();

            // Logistics tracking
            $table->unsignedBigInteger('trip_id')->nullable(); // FK to trip_assignments
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('credit_orders', function (Blueprint $table) {
            $table->dropColumn([
                'priority', 'payment_status', 'delivery_address',
                'assigned_branch_id', 'approved_by', 'approved_at',
                'escalated_by', 'escalated_at', 'escalation_notes',
                'cancellation_requested_by', 'cancellation_requested_at', 'cancellation_reason',
                'production_started_by', 'production_started_at', 'qc_notes', 'qc_passed_at',
                'trip_id', 'shipped_at', 'delivered_at',
            ]);
        });
    }
};
