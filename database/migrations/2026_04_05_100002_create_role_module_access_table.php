<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_module_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();

            // ── Boolean action toggles (secure-by-default = false) ───────────
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->boolean('can_export')->default(false);
            $table->boolean('can_import')->default(false);
            $table->boolean('can_print')->default(false);
            $table->boolean('can_approve')->default(false);
            $table->boolean('can_reject')->default(false);
            $table->boolean('can_bulk_action')->default(false);

            // ── Numeric limits (null = no limit enforced) ────────────────────
            $table->decimal('approval_limit', 15, 2)->nullable();       // max value user can approve
            $table->decimal('discount_limit_pct', 5, 2)->nullable();    // max discount % they can give
            $table->decimal('max_order_value', 15, 2)->nullable();      // max single order amount
            $table->unsignedInteger('daily_create_limit')->nullable();  // records per day
            $table->unsignedInteger('max_edit_age_days')->nullable();   // can't edit records older than N days
            $table->unsignedInteger('max_void_age_days')->nullable();   // can't void records older than N days
            $table->decimal('max_refund_pct', 5, 2)->nullable();        // max refund as % of original
            $table->decimal('credit_limit_override', 15, 2)->nullable(); // override customer credit cap
            $table->unsignedInteger('max_items_per_order')->nullable(); // line items per order

            // ── Scope restriction flags ──────────────────────────────────────
            $table->boolean('own_records_only')->default(false);         // can only see/edit own records
            $table->boolean('branch_records_only')->default(false);      // restricted to own branch
            $table->boolean('requires_approval')->default(false);        // all creates/edits go to approval queue
            $table->boolean('can_override_price')->default(false);       // can sell below floor price

            $table->timestamps();

            $table->unique(['role_id', 'module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_module_access');
    }
};
