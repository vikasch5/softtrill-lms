<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListLead extends Model
{
    protected $fillable = [
        'list_id',
        'lead_id',
        'tenant_id',
        'assigned_to',
        'call_attempts',
        'last_call_time',
        'last_call_attempt',
        'status',
        'score',
        'custom_data',
    ];
}
