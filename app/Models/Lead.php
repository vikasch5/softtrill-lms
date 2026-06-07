<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'id',
        'added_by',
        'tenant_id',
        'list_id',
        'name',
        'email',
        'phone_number',
        'assigned_to',
        'status',
        'email_index',
        'phone_index',
        'duplicate_hash',
        'data',
        'last_followup_at',
        'next_followup_at',
        'created_by'
    ];
    protected $casts = [
        'data' => 'array',
    ];

    public function list()
    {
        return $this->belongsTo(LeadList::class, 'list_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
