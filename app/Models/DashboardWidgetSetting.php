<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardWidgetSetting extends Model
{
    protected $fillable = ['role_name', 'widget_key', 'is_enabled', 'sort_order'];

    protected $casts = ['is_enabled' => 'boolean', 'sort_order' => 'integer'];
}
