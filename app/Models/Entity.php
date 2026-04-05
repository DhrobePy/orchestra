<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Model
{
    use SoftDeletes;

    // Fixed: Removed the stray { that was here
    protected $fillable = [
        'module_id', 
        'name', 
        'table_name', 
        'title_field', 
        'description', 
        'options'
    ];

    protected $casts = [
        'options' => 'array'
    ];

    public function module()
    {
        // Ensure you have a Module model or use the Nwidart \Module class if applicable
        return $this->belongsTo(Module::class);
    }

    public function fields()
    {
        return $this->hasMany(Field::class)->orderBy('sort_order');
    }

    public function relationships()
    {
        return $this->hasMany(Relationship::class);
    }
} // Fixed: Added missing closing brace