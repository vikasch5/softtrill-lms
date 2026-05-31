<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadFieldValue extends Model
{
    protected $fillable = [
        'id',
        'lead_id',
        'field_id',
        'value_string',
        'value_number',
        'value_date',
        'value_boolean',
    ];
}
