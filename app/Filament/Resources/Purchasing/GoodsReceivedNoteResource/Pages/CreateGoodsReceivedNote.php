<?php

namespace App\Filament\Resources\Purchasing\GoodsReceivedNoteResource\Pages;

use App\Filament\Resources\Purchasing\GoodsReceivedNoteResource;
use App\Services\ProcurementService;
use Filament\Resources\Pages\CreateRecord;

class CreateGoodsReceivedNote extends CreateRecord
{
    protected static string $resource = GoodsReceivedNoteResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return app(ProcurementService::class)->recordGoodsReceived($data);
    }
}
