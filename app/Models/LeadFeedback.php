<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadFeedback extends Model
{
    protected $table = 'lead_feedbacks';
    protected $fillable = [
        'added_by',
        'lead_id',
        'assigned_to',
        'tenant_id',
        'feedback_id',
        'sub_feedback_id',
        'followup_date',
        'status',
        'remarks',
    ];

    public function feedback()
    {
        return $this->belongsTo(Feedback::class, 'feedback_id');
    }

    public function subFeedback()
    {
        return $this->belongsTo(Feedback::class, 'sub_feedback_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
