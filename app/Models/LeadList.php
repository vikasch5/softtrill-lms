<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadList extends Model
{
    protected $table = 'lead_lists';

    protected $fillable = [
        'added_by',
        'tenant_id',
        'description',
        'name',
        'is_active',
        'created_by',
        'updated_by',
    ];
}
