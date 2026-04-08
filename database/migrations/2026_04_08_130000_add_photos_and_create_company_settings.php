<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('address');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('notes');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('image')->nullable()->after('description');
        });

        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('Orchestra ERP');
            $table->string('tagline')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('logo')->nullable();
            $table->timestamps();
        });

        // Insert default row so there's always one record
        \DB::table('company_settings')->insert([
            'company_name' => 'Orchestra ERP',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('photo');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('photo');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('image');
        });

        Schema::dropIfExists('company_settings');
    }
};
