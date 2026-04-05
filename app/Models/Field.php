<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Field extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'entity_id',
        'name',
        'label',
        'type',
        'validation_rules',
        'options',
        'is_listed',
        'is_editable',
        'is_required',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'validation_rules' => 'array',
        'options'          => 'array',
        'is_listed'        => 'boolean',
        'is_editable'      => 'boolean',
        'is_required'      => 'boolean',
    ];

    /**
     * Relationship with Entity
     */
    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }
}