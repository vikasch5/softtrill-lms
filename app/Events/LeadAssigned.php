<?php

namespace App\Events;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lead;
    public $user;
    public $assignedBy;
    public $isReassignment;

    /**
     * Create a new event instance.
     */
    public function __construct(Lead $lead, User $user, ?User $assignedBy = null, bool $isReassignment = false)
    {
        $this->lead = $lead;
        $this->user = $user;
        $this->assignedBy = $assignedBy;
        $this->isReassignment = $isReassignment;
    }
}
