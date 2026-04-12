<?php

namespace App\Filament\Resources\Notifications\NotificationChannelResource\Pages;

use App\Filament\Resources\Notifications\NotificationChannelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNotificationChannel extends EditRecord
{
    protected static string $resource = NotificationChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
