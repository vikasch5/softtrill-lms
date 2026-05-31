<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'id',
        'lead_id',
        'tenant_id',
        'created_by',
        'name',
        'email',
        'phone',
        'company',
        'position',
        'lead_import_file_id',
    ];
}
