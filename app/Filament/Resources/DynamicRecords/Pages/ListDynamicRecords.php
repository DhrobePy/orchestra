<?php

namespace App\Filament\Resources\DynamicRecords\Pages;

use App\Filament\Resources\DynamicRecords\DynamicRecordResource;
use App\Services\RolePermissionService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDynamicRecords extends ListRecords
{
    protected static string $resource = DynamicRecordResource::class;

    protected function getHeaderActions(): array
    {
        $table = request()->route('table')
            ?? static::getResource()::getCurrentEntity()?->table_name
            ?? '';

        $entity = static::getResource()::getCurrentEntity();

        // Only show Create button if user has can_create on this module/entity
        if ($entity) {
            $user = auth()->user();
            $rbac = app(RolePermissionService::class);
            if ($user && !$rbac->canDo($user, 'can_create', $entity->module_id, $entity->id)) {
                return [];
            }
        }

        return [
            CreateAction::make()
                ->url(static::getResource()::getUrl('create', ['table' => $table])),
        ];
    }
}
