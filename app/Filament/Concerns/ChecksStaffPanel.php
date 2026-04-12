<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Disables edit and delete actions when the resource is rendered inside
 * the staff panel (/app). Apply to any resource that should be read-only
 * for staff users.
 */
trait ChecksStaffPanel
{
    protected static function isStaffPanel(): bool
    {
        return filament()->getCurrentPanel()?->getId() === 'app';
    }

    public static function canEdit(Model $record): bool
    {
        if (static::isStaffPanel()) {
            return false;
        }

        return parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        if (static::isStaffPanel()) {
            return false;
        }

        return parent::canDelete($record);
    }

    public static function canDeleteAny(): bool
    {
        if (static::isStaffPanel()) {
            return false;
        }

        return parent::canDeleteAny();
    }
}
