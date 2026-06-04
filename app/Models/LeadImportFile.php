<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadImportFile extends Model
{
    protected $fillable = [
        'id',
        'added_by',
        'tenant_id',
        'list_id',
        'file_name',
        'original_name',
        'total_records',
        'imported_records',
        'failed_records',
        'status',
        'uploaded_by'
    ];
}
