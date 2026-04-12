<?php

namespace App\Filament\Resources\Notifications\NotificationEventRuleResource\Pages;

use App\Filament\Resources\Notifications\NotificationEventRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNotificationEventRules extends ListRecords
{
    protected static string $resource = NotificationEventRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
