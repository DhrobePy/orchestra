<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class RoleEntityAccess extends Model
{
    protected $table = 'role_entity_access';

    protected $fillable = [
        'role_id',
        'entity_id',
        // Boolean overrides (nullable = inherit from module)
        'can_view', 'can_create', 'can_edit', 'can_delete',
        'can_export', 'can_import', 'can_print',
        'can_approve', 'can_reject', 'can_bulk_action',
        // Field-level restrictions
        'hidden_fields',
        'readonly_fields',
        // Scope overrides (nullable = inherit)
        'own_records_only',
        'requires_approval',
    ];

    protected $casts = [
        'can_view'          => 'boolean',
        'can_create'        => 'boolean',
        'can_edit'          => 'boolean',
        'can_delete'        => 'boolean',
        'can_export'        => 'boolean',
        'can_import'        => 'boolean',
        'can_print'         => 'boolean',
        'can_approve'       => 'boolean',
        'can_reject'        => 'boolean',
        'can_bulk_action'   => 'boolean',
        'hidden_fields'     => 'array',
        'readonly_fields'   => 'array',
        'own_records_only'  => 'boolean',
        'requires_approval' => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }
}
