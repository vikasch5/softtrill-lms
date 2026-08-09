<?php

namespace App\Helpers;

use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Support\Str;

class LeadHelper
{
    /**
     * Get the display name for a lead.
     */
    public static function getLeadName(Lead $lead): string
    {
        return $lead->data['full_name'] ?? $lead->data['name'] ?? 'Lead #' . $lead->id;
    }

    /**
     * Get the current status of a lead based on feedback or default status.
     */
    public static function getLeadStatus(Lead $lead, $latestLeadFeedback = null): string
    {
        return $latestLeadFeedback?->feedback?->name
            ? ucwords(str_replace('_', ' ', $latestLeadFeedback->feedback->name))
            : ucfirst(str_replace('_', ' ', $lead->status ?? 'new'));
    }

    /**
     * Get the CSS color class for a given lead status.
     */
    public static function getStatusColorClass(string $status): string
    {
        $statusKey = Str::lower($status);
        
        return match (true) {
            Str::contains($statusKey, ['not interested', 'invalid', 'rejected', 'lost', 'closed lost', 'drop']) => 'lv-badge--danger',
            Str::contains($statusKey, ['follow up', 'pending', 'callback', 'no answer', 'busy', 'reschedule']) => 'lv-badge--warning',
            Str::contains($statusKey, ['new', 'open', 'fresh', 'unassigned']) => 'lv-badge--info',
            Str::contains($statusKey, ['qualified', 'interested', 'won', 'enrolled', 'closed won', 'converted']) => 'lv-badge--success',
            default => 'lv-badge--neutral',
        };
    }

    /**
     * Parse next followup date.
     */
    public static function parseNextFollowup(?string $date): ?Carbon
    {
        return $date ? Carbon::parse($date) : null;
    }

    /**
     * Parse and format next followup date.
     */
    public static function formatNextFollowup(?string $date, string $format = 'd M Y h:i A'): ?string
    {
        if (!$date) {
            return null;
        }
        return Carbon::parse($date)->format($format);
    }
    
    /**
     * Format created at date.
     */
    public static function formatCreatedAt($date, string $format = 'd M Y, h:i A'): string
    {
        return $date ? $date->format($format) : '-';
    }

    /**
     * Process activity log display details.
     */
    public static function getActivityDisplayData($activity, $feedbackLookup)
    {
        $activityData = is_array($activity->new_value) ? $activity->new_value : [];
        
        $activityTitle = match ($activity->activity) {
            'lead_assigned' => 'Lead assigned',
            'feedback_added' => 'Feedback added',
            default => ucwords(str_replace('_', ' ', $activity->activity)),
        };

        $activityIcon = match ($activity->activity) {
            'lead_assigned' => 'ri-user-shared-line',
            'feedback_added' => 'ri-chat-4-line',
            default => 'ri-history-line',
        };

        $activityBadge = match ($activity->activity) {
            'lead_assigned' => 'Assignment',
            'feedback_added' => 'Feedback',
            default => 'Activity',
        };

        $activityDetails = [];

        if ($activity->activity === 'lead_assigned') {
            if (!empty($activityData['user_name'])) {
                $activityDetails[] = [
                    'label' => 'Assigned To',
                    'value' => $activityData['user_name'],
                ];
            }
        }

        if ($activity->activity === 'feedback_added') {
            $feedbackName = optional($feedbackLookup->firstWhere('id', $activityData['feedback_id'] ?? null))->name;
            $subFeedbackName = optional($feedbackLookup->firstWhere('id', $activityData['sub_feedback_id'] ?? null))->name;

            if ($feedbackName) {
                $activityDetails[] = [
                    'label' => 'Feedback',
                    'value' => $feedbackName,
                ];
            }

            if ($subFeedbackName) {
                $activityDetails[] = [
                    'label' => 'Sub Feedback',
                    'value' => $subFeedbackName,
                ];
            }

            if (!empty($activityData['remarks'])) {
                $activityDetails[] = [
                    'label' => 'Remarks',
                    'value' => $activityData['remarks'],
                ];
            }
            
            if (!empty($activityData['followup_date'])) {
                $activityDetails[] = [
                    'label' => 'Followup',
                    'value' => Carbon::parse($activityData['followup_date'])->format('d M Y h:i A'),
                ];
            }
        }

        return [
            'title' => $activityTitle,
            'icon' => $activityIcon,
            'badge' => $activityBadge,
            'details' => $activityDetails,
            'timestamp' => $activity->created_at->diffForHumans(),
            'datetime' => $activity->created_at->format('M d, Y h:i A')
        ];
    }
}
