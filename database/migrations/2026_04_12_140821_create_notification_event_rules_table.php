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
        Schema::create('notification_event_rules', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 100);                   // e.g. order.created
            $table->foreignId('channel_id')->constrained('notification_channels')->cascadeOnDelete();
            $table->string('recipient_mode', 20)->default('channel_default');
                                                                // channel_default | role | user
            $table->string('recipient_identifier')->nullable(); // role name OR user_id
            $table->text('message_template')->nullable();       // null = use built-in default
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_event_rules');
    }
};
