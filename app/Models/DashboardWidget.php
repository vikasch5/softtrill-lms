<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    protected $fillable = [
        'id',
        'added_by',
        'tenant_id',
        'list_id',
        'title',
        'field_id',
        'chart_type',
        'aggregate',
        'group_by',
        'width',
        'height',
        'filters',
        'is_default',
        'is_active',
        'sort_order',
        'created_at',
        'updated_at'
    ];
}
