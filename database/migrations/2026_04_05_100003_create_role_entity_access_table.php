<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_entity_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('entity_id')->constrained('entities')->cascadeOnDelete();

            // ── Boolean overrides (null = inherit from role_module_access) ───
            $table->boolean('can_view')->nullable();
            $table->boolean('can_create')->nullable();
            $table->boolean('can_edit')->nullable();
            $table->boolean('can_delete')->nullable();
            $table->boolean('can_export')->nullable();
            $table->boolean('can_import')->nullable();
            $table->boolean('can_print')->nullable();
            $table->boolean('can_approve')->nullable();
            $table->boolean('can_reject')->nullable();
            $table->boolean('can_bulk_action')->nullable();

            // ── Field-level restrictions ──────────────────────────────────────
            $table->json('hidden_fields')->nullable();    // ['field_name', ...] — hide from form & table
            $table->json('readonly_fields')->nullable();  // ['field_name', ...] — disabled in form

            // ── Scope overrides (null = inherit) ──────────────────────────────
            $table->boolean('own_records_only')->nullable();
            $table->boolean('requires_approval')->nullable();

            $table->timestamps();

            $table->unique(['role_id', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_entity_access');
    }
};
