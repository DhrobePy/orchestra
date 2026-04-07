<?php

namespace BinaryBuilds\CommandRunner\Resources\CommandRuns\Pages;

use BinaryBuilds\CommandRunner\Actions\KillCommandAction;
use BinaryBuilds\CommandRunner\CommandRunnerPlugin;
use BinaryBuilds\CommandRunner\Models\CommandRun;
use BinaryBuilds\CommandRunner\Resources\CommandRuns\CommandRunResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCommandRun extends ViewRecord
{
    protected static string $resource = CommandRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->visible(function (CommandRun $command) {
                    return is_null($command->completed_at);
                })
                ->action(function () {
                    $this->record->refresh();
                })
                ->successNotificationTitle('Page refreshed.'),
            KillCommandAction::make()
                ->visible(function (CommandRun $command) {
                    return is_null($command->completed_at);
                }),
            DeleteAction::make()
                ->visible(CommandRunnerPlugin::get()->getCanDeleteHistory()),
        ];
    }
}
