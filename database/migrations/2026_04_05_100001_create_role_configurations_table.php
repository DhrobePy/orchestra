<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('description')->nullable();
            $table->string('color', 7)->default('#6b7280'); // Tailwind gray-500 hex
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('max_users')->nullable();
            $table->boolean('bypass_all_restrictions')->default(false); // super-admin flag
            $table->json('dashboard_widgets')->nullable();               // widget class names
            $table->timestamps();

            $table->unique('role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_configurations');
    }
};
