<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add payment columns to expense_vouchers
        Schema::table('expense_vouchers', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('reference');
            $table->unsignedBigInteger('bank_account_id')->nullable()->after('payment_method');
        });

        // Add field metadata so the dynamic form knows about these two new fields
        $entity = DB::table('entities')->where('table_name', 'expense_vouchers')->first();
        if (! $entity) return;

        $maxSort = DB::table('fields')
            ->where('entity_id', $entity->id)
            ->max('sort_order') ?? 0;

        $paymentOptions = json_encode([
            'cash'          => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'cheque'        => 'Cheque',
            'mobile'        => 'Mobile Banking',
        ]);

        $now = now();

        DB::table('fields')->insert([
            [
                'entity_id'   => $entity->id,
                'name'        => 'payment_method',
                'label'       => 'Payment Method',
                'type'        => 'select',
                'options'     => $paymentOptions,
                'is_required' => false,
                'is_listed'   => true,
                'is_editable' => true,
                'sort_order'  => $maxSort + 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'entity_id'   => $entity->id,
                'name'        => 'bank_account_id',
                'label'       => 'Bank Account',
                'type'        => 'integer',
                'options'     => null,
                'is_required' => false,
                'is_listed'   => false,
                'is_editable' => true,
                'sort_order'  => $maxSort + 2,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('expense_vouchers', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'bank_account_id']);
        });

        $entity = DB::table('entities')->where('table_name', 'expense_vouchers')->first();
        if ($entity) {
            DB::table('fields')
                ->where('entity_id', $entity->id)
                ->whereIn('name', ['payment_method', 'bank_account_id'])
                ->delete();
        }
    }
};
