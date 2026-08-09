<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = [
        'tenant_id',
        'added_by',
        'heading',
        'description',
        'image',
        'url',
        'status',
        'created_by',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}