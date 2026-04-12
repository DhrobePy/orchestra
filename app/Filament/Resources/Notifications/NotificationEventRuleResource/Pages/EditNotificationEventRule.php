<?php

namespace App\Filament\Resources\Notifications\NotificationEventRuleResource\Pages;

use App\Filament\Resources\Notifications\NotificationEventRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNotificationEventRule extends EditRecord
{
    protected static string $resource = NotificationEventRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
