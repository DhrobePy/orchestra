<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Relationship extends Model
{
    protected $fillable = [
        'entity_id',
        'name',
        'type',
        'related_entity_id',
        'foreign_key',
        'local_key',
    ];

    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }

    public function relatedEntity()
    {
        return $this->belongsTo(Entity::class, 'related_entity_id');
    }
}