<?php

namespace App\Filament\Resources\DynamicRecords\Pages;

use App\Filament\Resources\DynamicRecords\DynamicRecordResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateDynamicRecord extends CreateRecord
{
    protected static string $resource = DynamicRecordResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $entity = static::getResource()::getCurrentEntity();

        if ($entity?->table_name === 'expense_vouchers') {
            $year   = date('Y');
            $last   = DB::table('expense_vouchers')
                ->where('voucher_number', 'like', "EXP-{$year}-%")
                ->orderByDesc('id')
                ->value('voucher_number');

            $next = 1;
            if ($last) {
                $parts = explode('-', $last);
                $next  = ((int) end($parts)) + 1;
            }

            $data['voucher_number'] = sprintf('EXP-%s-%05d', $year, $next);
        }

        return $data;
    }
}
