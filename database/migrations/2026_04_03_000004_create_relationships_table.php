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
        Schema::create('relationships', function (Blueprint $table) {
    $table->id();
    $table->foreignId('entity_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('type'); // hasOne, hasMany, belongsTo, belongsToMany
    $table->foreignId('related_entity_id')->constrained('entities')->cascadeOnDelete();
    $table->string('foreign_key');
    $table->string('local_key')->default('id');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relationships');
    }
};
