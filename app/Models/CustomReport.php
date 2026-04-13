<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomReport extends Model
{
    protected $table = 'custom_reports';

    protected $fillable = [
        'name', 'description', 'data_source',
        'columns', 'filters', 'sort_by', 'sort_dir', 'group_by', 'created_by',
    ];

    protected $casts = [
        'columns' => 'array',
        'filters' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
