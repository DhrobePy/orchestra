<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;

class RoleConfiguration extends Model
{
    protected $fillable = [
        'role_id',
        'description',
        'color',
        'is_active',
        'max_users',
        'bypass_all_restrictions',
        'dashboard_widgets',
    ];

    protected $casts = [
        'is_active'               => 'boolean',
        'bypass_all_restrictions' => 'boolean',
        'dashboard_widgets'       => 'array',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function moduleAccess(): HasMany
    {
        return $this->hasMany(RoleModuleAccess::class, 'role_id', 'role_id');
    }

    public function entityAccess(): HasMany
    {
        return $this->hasMany(RoleEntityAccess::class, 'role_id', 'role_id');
    }
}
