<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'id',
        'lead_id',
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

    protected static function booted(): void
    {
        static::created(function (Lead $lead) {
            if (blank($lead->lead_id) || $lead->lead_id === '0') {
                $lead->forceFill([
                    'lead_id' => self::formatLeadId($lead->getKey()),
                ])->saveQuietly();
            }
        });
    }

    public static function formatLeadId(int $id): string
    {
        return 'LS' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    public function list()
    {
        return $this->belongsTo(LeadList::class, 'list_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
