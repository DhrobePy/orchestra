<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fields', function (Blueprint $table) {
    $table->id();
    $table->foreignId('entity_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('label');
    $table->string('type'); // text, number, date, boolean, select, json, media
    $table->json('validation_rules')->nullable();
    $table->json('options')->nullable();
    $table->boolean('is_listed')->default(true);
    $table->boolean('is_editable')->default(true);
    $table->boolean('is_required')->default(false);
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fields');
    }
};
