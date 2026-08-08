<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
        'last_followup_at' => 'datetime',
        'next_followup_at' => 'datetime',
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

    public function leadFeedback()
    {
        return $this->hasOne(LeadFeedback::class, 'lead_id')->latestOfMany('id');
    }

    protected function nextFollowupFormatted(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->next_followup_at?->format('d M Y h:i A') ?? 'N/A'
        );
    }
}
