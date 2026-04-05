<?php

namespace App\Filament\Resources\DynamicRecords\Pages;

use App\Filament\Resources\DynamicRecords\DynamicRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDynamicRecord extends EditRecord
{
    protected static string $resource = DynamicRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
