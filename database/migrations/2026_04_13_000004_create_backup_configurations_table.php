<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_configurations', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('frequency')->default('daily');   // hourly|daily|weekly|custom
            $table->string('run_at', 5)->default('02:00');   // HH:MM
            $table->string('day_of_week')->nullable();       // for weekly
            $table->string('cron_expression')->nullable();   // for custom
            $table->json('tables');                          // ['all'] | ['customers', ...]
            $table->text('google_credentials')->nullable();  // encrypted service-account JSON
            $table->string('google_folder_id')->nullable();
            $table->string('google_folder_name')->nullable();
            $table->integer('retention_days')->default(30);
            $table->timestamp('last_backup_at')->nullable();
            $table->string('last_backup_status')->nullable();// success|failed
            $table->text('last_backup_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_configurations');
    }
};
