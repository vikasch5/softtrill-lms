<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadActivityLog extends Model
{
    protected $table = 'lead_activity_logs';

    protected $fillable = [
        'id',
        'tenant_id',
        'added_by',
        'activity',
        'old_value',
        'new_value',
        'lead_id',
        'user_id',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
