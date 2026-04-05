<?php

namespace App\Filament\Resources\DynamicRecords\Pages;

use App\Filament\Resources\DynamicRecords\DynamicRecordResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDynamicRecord extends CreateRecord
{
    protected static string $resource = DynamicRecordResource::class;
}
