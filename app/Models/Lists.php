<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lists extends Model
{
    protected $fillable = [
        'id',
        'tenant_id',
        'list_code',
        'name',
        'description',
        'is_active',
        'type',
        'total_leads',
        'added_by',
        'last_import_id',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}
