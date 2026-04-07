<?php

namespace App\Filament\Resources\Purchasing\GoodsReceivedNoteResource\Pages;

use App\Filament\Resources\Purchasing\GoodsReceivedNoteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGoodsReceivedNote extends EditRecord
{
    protected static string $resource = GoodsReceivedNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
