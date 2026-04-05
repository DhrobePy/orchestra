<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DynamicModel extends Model
{
    protected $guarded = [];
    
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }
    
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }
}