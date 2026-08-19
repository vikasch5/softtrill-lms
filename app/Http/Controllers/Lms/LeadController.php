<?php

namespace App\Http\Controllers\Lms;

use App\Imports\LeadsImport;
use App\Models\Feedback;
use App\Models\Lead;
use App\Models\LeadActivityLog;
use App\Models\LeadFeedback;
use App\Models\LeadField;
use App\Models\LeadFollowup;
use App\Models\LeadImportFile;
use App\Models\LeadList;
use App\Models\LeadNote;
use App\Models\Lists;
use App\Models\User;
use App\Models\UserDetails;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Log;

class LeadController extends Controller
{
    public function leadsList(Request $request)
    {
        $user = auth()->user();
        $visibleUserIds = $this->getVisibleUserIds($user);
        $dynamicFilters = $request->input('filters', []);

        $query = Lead::query()
            ->select([
                'id',
                'lead_id',
                'list_id',
                'assigned_to',
                'status',
                'name',
                'phone_number',
                'email',
                'next_followup_at',
                'created_at',
                'added_by',
                'updated_at',
            ])
            ->with(['list', 'assignedTo', 'leadFeedback.feedback']);

        // Admin sees everything, others see leads within their hierarchy
        if (!$user->hasRole('Admin')) {
            $query->where(function ($q) use ($visibleUserIds) {
                $q
                    ->whereIn('assigned_to', $visibleUserIds)
                    ->orWhereIn('added_by', $visibleUserIds);
            });
        }

        if ($request->filled('list_id')) {
            $query->where('list_id', $request->list_id);
        }

        if ($request->filled('feedback_id')) {
            $query->whereHas('leadFeedback', function ($q) use ($request) {
                $q->where('feedback_id', $request->feedback_id);
            });
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . trim($request->name) . '%');
        }

        if ($request->filled('phone_number')) {
            $query->where('phone_number', 'like', '%' . trim($request->phone_number) . '%');
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . trim($request->email) . '%');
        }

        if ($request->filled('followup_status')) {
            $today = \Carbon\Carbon::today();
            if ($request->followup_status === 'today') {
                $query->whereDate('next_followup_at', $today);
            } elseif ($request->followup_status === 'pending') {
                $query->where('next_followup_at', '<', $today->copy()->startOfDay());
            } elseif ($request->followup_status === 'upcoming') {
                $query->where('next_followup_at', '>', $today->copy()->endOfDay());
            }
        }

        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        if ($request->filled('followup_from')) {
            $query->whereDate('next_followup_at', '>=', $request->followup_from);
        }

        if ($request->filled('followup_to')) {
            $query->whereDate('next_followup_at', '<=', $request->followup_to);
        }

        $filterableFields = LeadField::query()
            ->when(
                $request->filled('list_id'),
                fn($fieldQuery) => $fieldQuery->where('list_id', $request->list_id)
            )
            ->where('is_filterable', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'list_id',
                'name',
                'slug',
                'type',
                'options',
                'is_filterable',
            ]);

        $filterService = app(\App\Services\LeadFilterService::class);
        $query = $filterService->applyDynamicFilters($query, $dynamicFilters, $filterableFields);

        $leads = $query
            ->latest('id')
            ->simplePaginate(20)
            ->withQueryString();

        $lists = LeadList::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $feedbacks = \App\Models\Feedback::whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $assignmentRoles = $this->getAssignableRoles($user);

        $privacyService = app(\App\Services\PrivacyService::class);
        $privacySettings = $privacyService->getAll();

        return view(
            'lms.pages.leads-list',
            compact('leads', 'lists', 'feedbacks', 'filterableFields', 'assignmentRoles', 'privacyService', 'privacySettings')
        );
    }

    private function getAssignableRoles(User $user): array
    {
        if ($user->hasRole('Admin') || $user->hasRole('Cluster')) {
            return ['Manager', 'TeamLeader', 'Agent'];
        }

        if ($user->hasRole('Manager')) {
            return ['TeamLeader', 'Agent'];
        }

        if ($user->hasRole('TeamLeader')) {
            return ['Agent'];
        }

        return [];
    }

    private function assignableUsersQuery(User $user, string $role)
    {
        $query = User::role($role)->select('users.id', 'users.name');

        if ($user->hasRole('Admin')) {
            return $query;
        }

        if ($user->hasRole('Cluster')) {
            return $query->whereHas(
                'details',
                fn($details) =>
                    $details->where('cluster_id', $user->id)
            );
        }

        if ($user->hasRole('Manager')) {
            return $query->whereHas(
                'details',
                fn($details) =>
                    $details->where('manager_id', $user->id)
            );
        }

        if ($user->hasRole('TeamLeader')) {
            return $query->whereHas(
                'details',
                fn($details) =>
                    $details->where('teamleader_id', $user->id)
            );
        }

        return $query->whereRaw('1 = 0');
    }

    public function downloadLeadList(Request $request)
    {
        $request->validate([
            'list_id' => 'required|integer|exists:lead_lists,id',
        ]);

        $user = auth()->user();
        $list = LeadList::findOrFail($request->integer('list_id'));

        $exportType = $request->get('export_type', 'full');

        if ($exportType === 'dialer') {
            $fields = collect();
        } else {
            $fields = LeadField::where('list_id', $list->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['name', 'slug']);
        }

        $query = Lead::query()
            ->where('list_id', $list->id)
            ->select([
                'id',
                'lead_id',
                'name',
                'email',
                'phone_number',
                'assigned_to',
                'data',
                'created_at',
            ]);

        if (!$user->hasRole('Admin')) {
            $visibleUserIds = $this->getVisibleUserIds($user);

            $query->where(function ($leadQuery) use ($visibleUserIds) {
                $leadQuery
                    ->whereIn('assigned_to', $visibleUserIds)
                    ->orWhereIn('added_by', $visibleUserIds);
            });
        }

        if ($request->filled('feedback_id')) {
            $query->whereHas('leadFeedback', function ($q) use ($request) {
                $q->where('feedback_id', $request->feedback_id);
            });
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . trim($request->name) . '%');
        }

        if ($request->filled('phone_number')) {
            $query->where('phone_number', 'like', '%' . trim($request->phone_number) . '%');
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . trim($request->email) . '%');
        }

        if ($request->filled('followup_status')) {
            $today = \Carbon\Carbon::today();
            if ($request->followup_status === 'today') {
                $query->whereDate('next_followup_at', $today);
            } elseif ($request->followup_status === 'pending') {
                $query->where('next_followup_at', '<', $today->copy()->startOfDay());
            } elseif ($request->followup_status === 'upcoming') {
                $query->where('next_followup_at', '>', $today->copy()->endOfDay());
            }
        }

        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        if ($request->filled('followup_from')) {
            $query->whereDate('next_followup_at', '>=', $request->followup_from);
        }

        if ($request->filled('followup_to')) {
            $query->whereDate('next_followup_at', '<=', $request->followup_to);
        }

        if ($exportType === 'dialer') {
            $query->where(function ($q) {
                $q->whereNull('next_followup_at')
                  ->orWhereDate('next_followup_at', \Carbon\Carbon::today());
            });
        }

        $dynamicFilters = $request->input('filters', []);
        if (!empty($dynamicFilters)) {
            $filterableFields = LeadField::where('list_id', $list->id)
                ->where('is_filterable', true)
                ->get();
            $filterService = app(\App\Services\LeadFilterService::class);
            $query = $filterService->applyDynamicFilters($query, $dynamicFilters, $filterableFields);
        }

        $exportLabel = $exportType === 'dialer' ? 'dialer' : 'full';
        $filename = Str::slug($list->name ?: 'lead-list') . '-' . $exportLabel . '-' . now()->format('YmdHis') . '.csv';

        return response()->streamDownload(function () use ($query, $fields, $exportType) {
            $file = fopen('php://output', 'w');

            if ($exportType === 'dialer') {
                fputcsv($file, [
                    'lead_id',
                    'name',
                    'phone_number',
                ]);
            } else {
                fputcsv($file, array_merge([
                    'lead_id',
                    'name',
                    'email',
                    'phone_number',
                    'assigned_to',
                    'created_at',
                    'feedback',
                    'sub_feedback',
                    'feedback_remarks',
                    'followup_date',
                ], $fields->pluck('slug')->all()));
            }

            $query->orderBy('id')->chunkById(1000, function ($leads) use ($file, $fields, $exportType) {
                $leads->load(['leadFeedback.feedback', 'leadFeedback.subFeedback', 'assignedTo.details']);

                foreach ($leads as $lead) {
                    if ($exportType === 'dialer') {
                        $row = [
                            $lead->lead_id,
                            $lead->name,
                            $lead->phone_number,
                        ];
                    } else {
                        $data = $lead->data ?? [];
                        $leadFeedback = $lead->leadFeedback;

                        $row = [
                            $lead->lead_id,
                            $lead->name,
                            $lead->email,
                            $lead->phone_number,
                            $lead->assignedTo?->details?->employee_id ?? $lead->assigned_to,
                            optional($lead->created_at)->format('Y-m-d H:i:s'),
                            optional($leadFeedback?->feedback)->name,
                            optional($leadFeedback?->subFeedback)->name,
                            $leadFeedback?->remarks,
                            optional($leadFeedback?->followup_date instanceof \Carbon\Carbon
                                ? $leadFeedback->followup_date
                                : ($leadFeedback?->followup_date ? \Carbon\Carbon::parse($leadFeedback->followup_date) : null))->format('Y-m-d H:i:s'),
                        ];

                        foreach ($fields as $field) {
                            $value = $data[$field->slug] ?? null;
                            $row[] = is_array($value) ? implode('|', $value) : $value;
                        }
                    }

                    fputcsv($file, $row);
                }
            }, 'id');

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Get all user IDs visible to the given user based on the org hierarchy.
     *
     * Admin     → all users
     * Manager   → self + TeamLeaders under them + Agents under those TeamLeaders
     * Cluster   → self + users with matching cluster_id
     * TeamLeader→ self + Agents under them
     * Agent     → self only
     */
    private function getVisibleUserIds($user): array
    {
        $userId = $user->id;

        // Admin can see everything (handled in caller, but return empty as fallback)
        if ($user->hasRole('Admin')) {
            return [];
        }

        $visibleIds = [$userId];

        if ($user->hasRole('Manager')) {
            // TeamLeaders whose manager_id = this manager
            $teamLeaderIds = UserDetails::where('manager_id', $userId)
                ->pluck('user_id')
                ->toArray();

            $visibleIds = array_merge($visibleIds, $teamLeaderIds);

            // Agents whose teamleader_id is one of those TeamLeaders
            if (!empty($teamLeaderIds)) {
                $agentIds = UserDetails::whereIn('teamleader_id', $teamLeaderIds)
                    ->pluck('user_id')
                    ->toArray();

                $visibleIds = array_merge($visibleIds, $agentIds);
            }

            // Also include agents directly under this manager
            $directAgentIds = UserDetails::where('manager_id', $userId)
                ->pluck('user_id')
                ->toArray();

            $visibleIds = array_merge($visibleIds, $directAgentIds);
        } elseif ($user->hasRole('Cluster')) {
            // Users in the same cluster
            $clusterUserIds = UserDetails::where('cluster_id', $userId)
                ->pluck('user_id')
                ->toArray();

            $visibleIds = array_merge($visibleIds, $clusterUserIds);
        } elseif ($user->hasRole('TeamLeader')) {
            // Agents whose teamleader_id = this team leader
            $agentIds = UserDetails::where('teamleader_id', $userId)
                ->pluck('user_id')
                ->toArray();

            $visibleIds = array_merge($visibleIds, $agentIds);
        }

        // Agent or any other role → only self (already in $visibleIds)

        return array_values(array_unique($visibleIds));
    }

    public function leadAdd()
    {
        $lists = LeadList::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();
        $leadFields = LeadField::orderBy('sort_order')->get();
        return view('lms.pages.lead-add', compact('leadFields', 'lists'));
    }

    public function storeOrUpdate(Request $request, $id = null)
    {
        try {
            DB::beginTransaction();

            $tenantId = auth()->id();
            $userId = auth()->id();

            // =========================
            // VALIDATION
            // =========================
            $request->validate([
                'name' => 'nullable|string|max:255',
                'phone' => 'required|digits:10',
                'email' => 'nullable|email|max:255',
                'list_id' => 'nullable|integer',
                'list_name' => 'nullable|string|max:255',
                'custom' => 'nullable|array',
            ]);

            // Use the selected list, or create one when no list was selected.
            // lead_import_file_id is retained as a fallback for older clients.
            $requestedListId = $request->input('list_id', $request->lead_import_file_id);

            if (!blank($requestedListId)) {
                $list = LeadList::where('tenant_id', $tenantId)
                    ->findOrFail($requestedListId);
            } else {
                $list = LeadList::create([
                    'added_by' => $userId,
                    'tenant_id' => $tenantId,
                    'name' => $request->filled('list_name')
                        ? trim($request->list_name)
                        : 'Lead List ' . now()->format('YmdHis'),
                    'description' => 'Auto-generated while creating a lead',
                    'is_active' => 1,
                    'created_by' => $userId,
                ]);
            }

            $listId = $list->id;

            $name = $request->filled('name') ? trim($request->name) : null;
            $email = $request->filled('email')
                ? strtolower(trim($request->email))
                : null;
            $phone = preg_replace('/\D/', '', (string) $request->phone);

            // Keep manual creation consistent with LeadsImport.
            $duplicateHash = md5(
                $listId . '|' . strtolower($email ?? '') . '|' . $phone
            );

            // =========================
            // FIND OR CREATE
            // =========================
            $lead = $id
                ? Lead::where('tenant_id', $tenantId)->findOrFail($id)
                : new Lead();

            // =========================
            // DUPLICATE CHECK (LIST BASED)
            // =========================
            $duplicate = Lead::where('duplicate_hash', $duplicateHash)
                ->where('tenant_id', $tenantId)
                ->where('list_id', $listId)
                ->when($id, fn($q) => $q->where('id', '!=', $id))
                ->exists();

            if ($duplicate) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Lead already exists in this list'
                ]);
            }

            // =========================
            // BASIC FIELDS
            // =========================
            $lead->tenant_id = $tenantId;
            $lead->list_id = $listId;
            $lead->name = $name;
            $lead->phone_number = $phone;
            $lead->email = $email;
            $lead->email_index = $email;
            $lead->phone_index = $phone;
            $lead->duplicate_hash = $duplicateHash;
            $lead->status = $lead->status ?: 'new';
            $lead->added_by = $lead->added_by ?? $userId;
            $lead->assigned_to = $lead->assigned_to ?? $userId;
            $lead->created_by = $lead->created_by ?? $userId;

            // =========================
            // DYNAMIC FIELDS
            // =========================
            $customFields = [];

            // Import only accepts fields belonging to the selected list.
            $fieldSlugs = LeadField::where('tenant_id', $tenantId)
                ->where('list_id', $listId)
                ->pluck('slug')
                ->flip();

            foreach ($request->custom ?? [] as $slug => $value) {
                if ($fieldSlugs->has($slug) && !blank($value)) {
                    $customFields[$slug] = $value;
                }
            }

            // Lead casts data to array, matching LeadsImport::create().
            $lead->data = $customFields;
            $lead->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $id ? 'Lead Updated Successfully' : 'Lead Created Successfully',
                'lead_id' => $lead->id,
                'list_id' => $listId
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function leadsEdit($id)
    {
        $lead = Lead::findOrFail($id);

        $fields = LeadField::where(
            'list_id',
            $lead->list_id
        )
            ->orderBy('sort_order')
            ->get();

        return view(
            'lms.pages.lead-edit',
            compact(
                'lead',
                'fields'
            )
        );
    }

    public function updateLead(Request $request)
    {
        $id = $request->lead_id;
        $lead = Lead::findOrFail($id);

        $leadData = $request->input('data', []);
        $dataToUpdate = [
            'name' => $request->name,
            'data' => $leadData,
        ];

        if ($request->has('email')) {
            $email = $request->filled('email') ? strtolower(trim($request->email)) : null;
            $dataToUpdate['email'] = $email;
            $dataToUpdate['email_index'] = $email;
        }

        if ($request->has('phone_number')) {
            $phone = preg_replace('/\D/', '', (string) $request->phone_number);
            $dataToUpdate['phone_number'] = $phone;
            $dataToUpdate['phone_index'] = $phone;
        }

        $lead->update($dataToUpdate);
        $lead->touch();

        $redirectUrl = route(
            'lms.lead.view',
            $lead->lead_id ?: $lead->id
        );

        if ($request->source === 'dialer') {
            $redirectUrl .= '?source=dialer';
        }

        return response()->json([
            'success' => true,
            'message' => 'Lead updated successfully.',
            'redirect_url' => $redirectUrl,
        ]);
    }

    //     public function leadsView(Request $request, $id)
    // {
    //     $user = auth()->user();

    //     $lead = Lead::where('lead_id', $id)->firstOrFail();

    //     if ($request->source === 'dialer' && (empty($lead->assigned_to) ||$lead->assigned_to == '1') ) {
    //         $lead->update([
    //             'assigned_to'     => $user->id
    //         ]);
    //         $lead->refresh();
    //     }
    //     /*
    //     |--------------------------------------------------------------------------
    //     | DIALER REQUEST
    //     |--------------------------------------------------------------------------
    //     |
    //     | Temporarily disabled.
    //     | Will be enabled after dialer changes.
    //     |
    //     */

    //     /*
    //     $isDialerRequest = $request->filled('dialer_lead_id');

    //     if ($isDialerRequest) {

    //         $dialerLeadId = (string) $request->query('dialer_lead_id');

    //         if (empty($lead->dialer_lead_id)) {

    //             $lead->update([
    //                 'dialer_lead_id'  => $dialerLeadId,
    //                 'assigned_to'     => $user->id,
    //                 'assigned_at'     => now(),
    //                 'assignment_type' => 'dialer',
    //             ]);

    //             $lead->refresh();
    //         }

    //         elseif ((string) $lead->dialer_lead_id !== $dialerLeadId) {

    //             abort(403, 'Invalid dialer lead.');
    //         }
    //     }
    //     */

    //     /*
    //     |--------------------------------------------------------------------------
    //     | NORMAL LMS REQUEST
    //     |--------------------------------------------------------------------------
    //     */

    //     // if ($user->hasRole('Agent')) {

    //     //     /*
    //     //      * Agent can only view his own assigned leads.
    //     //      */
    //     //     if ((int) $lead->assigned_to !== (int) $user->id) {
    //     //         abort(403, 'You are not allowed to view this lead.');
    //     //     }
    //     // }

    //     // elseif ($user->hasRole('Team Leader')) {

    //     //     /*
    //     //      * TL can view:
    //     //      * 1. His own leads
    //     //      * 2. Leads assigned to his agents
    //     //      */

    //     //     $allowedAgentIds = User::role('Agent')
    //     //         ->where('team_leader_id', $user->id)
    //     //         ->pluck('id');

    //     //     $allowedAgentIds->push($user->id);

    //     //     if (!$allowedAgentIds->contains((int) $lead->assigned_to)) {
    //     //         abort(403, 'You are not allowed to view this lead.');
    //     //     }
    //     // }

    //     // elseif ($user->hasRole('Manager')) {

    //     //     /*
    //     //      * Manager can view leads assigned to:
    //     //      * 1. Himself
    //     //      * 2. His Team Leaders
    //     //      * 3. Their Agents
    //     //      */

    //     //     $teamLeaderIds = User::role('Team Leader')
    //     //         ->where('manager_id', $user->id)
    //     //         ->pluck('id');

    //     //     $agentIds = User::role('Agent')
    //     //         ->whereIn('team_leader_id', $teamLeaderIds)
    //     //         ->pluck('id');

    //     //     $allowedUserIds = $teamLeaderIds
    //     //         ->merge($agentIds)
    //     //         ->push($user->id);

    //     //     if (!$allowedUserIds->contains((int) $lead->assigned_to)) {
    //     //         abort(403, 'You are not allowed to view this lead.');
    //     //     }
    //     // }

    //     // elseif ($user->hasRole('Cluster')) {

    //     //     /*
    //     //      * Cluster can view leads belonging to its cluster.
    //     //      */
    //     //     if ((int) $lead->cluster_id !== (int) $user->cluster_id) {
    //     //         abort(403, 'You are not allowed to view this lead.');
    //     //     }
    //     // }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | LOAD LEAD DATA
    //     |--------------------------------------------------------------------------
    //     */

    //     $fields = LeadField::where('list_id', $lead->list_id)
    //         ->orderBy('sort_order')
    //         ->get();

    //     $leadFeedback = LeadFeedback::with([
    //         'feedback',
    //         'subFeedback',
    //         'user'
    //     ])
    //         ->where('lead_id', $lead->id)
    //         ->latest('id')
    //         ->get();

    //     $activities = LeadActivityLog::with('user:id,name')
    //         ->where('lead_id', $lead->id)
    //         ->latest('id')
    //         ->limit(50)
    //         ->get();

    //     $users = User::select('id', 'name')
    //         ->orderBy('name')
    //         ->get();

    //     $feedbacks = Feedback::whereNull('parent_id')
    //         ->orderBy('name')
    //         ->get();

    //     $feedbackLookup = Feedback::where('added_by', $user->id)
    //         ->get([
    //             'id',
    //             'name'
    //         ]);

    //     $privacyService = app(\App\Services\PrivacyService::class);
    //     $privacySettings = $privacyService->getAll();

    //     return view(
    //         'lms.pages.lead-view',
    //         compact(
    //             'lead',
    //             'fields',
    //             'leadFeedback',
    //             'activities',
    //             'users',
    //             'feedbacks',
    //             'feedbackLookup',
    //             'privacyService',
    //             'privacySettings'
    //         )
    //     );
    // }

    public function leadsView(Request $request, $id)
    {
        $user = auth()->user();

        $lead = Lead::where('lead_id', $id)->firstOrFail();

        $isDialerRequest = $request->filled('dialer_lead_id');

        /*
         * |--------------------------------------------------------------------------
         * | DIALER REQUEST
         * |--------------------------------------------------------------------------
         */

        if ($isDialerRequest) {
            $dialerLeadId = (string) $request->query('dialer_lead_id');

            /*
             * |--------------------------------------------------------------------------
             * | Fresh lead coming from dialer
             * |--------------------------------------------------------------------------
             */

            if (empty($lead->dialer_lead_id)) {
                $lead->update([
                    'dialer_lead_id' => $dialerLeadId,
                    'assigned_to' => $user->id,
                    'assigned_at' => now(),
                    'assignment_type' => 'dialer',
                ]);

                $lead->refresh();
            }
            /*
             * |--------------------------------------------------------------------------
             * | Existing dialer lead
             * |--------------------------------------------------------------------------
             */ elseif ((string) $lead->dialer_lead_id !== $dialerLeadId) {
                abort(403, 'Invalid dialer lead.');
            }

            /*
             * |--------------------------------------------------------------------------
             * | Existing assigned lead
             * |--------------------------------------------------------------------------
             * |
             * | IMPORTANT:
             * | Do NOT change assigned_to here.
             * |
             */
        }
        /*
         * |--------------------------------------------------------------------------
         * | NORMAL LMS REQUEST
         * |--------------------------------------------------------------------------
         * |
         * | Example:
         * | /lead-view/LS001007
         * |
         * | This is NOT from dialer.
         * |
         */ else {
            if ($user->hasRole('Agent')) {
                /*
                 * Agent can only view his own assigned leads.
                 */
                if ((int) $lead->assigned_to !== (int) $user->id) {
                    abort(403, 'You are not allowed to view this lead.');
                }
            } elseif ($user->hasRole('Team Leader')) {
                /*
                 * TL can view:
                 * 1. His own leads
                 * 2. Leads assigned to his agents
                 */

                $allowedAgentIds = User::role('Agent')
                    ->where('team_leader_id', $user->id)
                    ->pluck('id');

                $allowedAgentIds->push($user->id);

                if (!$allowedAgentIds->contains($lead->assigned_to)) {
                    abort(403, 'You are not allowed to view this lead.');
                }
            } elseif ($user->hasRole('Manager')) {
                /*
                 * Manager can view leads assigned to:
                 * Manager
                 * His Team Leaders
                 * Their Agents
                 */

                $teamLeaderIds = User::role('Team Leader')
                    ->where('manager_id', $user->id)
                    ->pluck('id');

                $agentIds = User::role('Agent')
                    ->whereIn('team_leader_id', $teamLeaderIds)
                    ->pluck('id');

                $allowedUserIds = $teamLeaderIds
                    ->merge($agentIds)
                    ->push($user->id);

                if (!$allowedUserIds->contains($lead->assigned_to)) {
                    abort(403, 'You are not allowed to view this lead.');
                }
            } elseif ($user->hasRole('Cluster')) {
                /*
                 * Cluster can view leads belonging to its cluster.
                 *
                 * Prefer checking cluster_id directly on the lead.
                 */

                if ((int) $lead->cluster_id !== (int) $user->cluster_id) {
                    abort(403, 'You are not allowed to view this lead.');
                }
            }
        }

        /*
         * |--------------------------------------------------------------------------
         * | LOAD LEAD DATA
         * |--------------------------------------------------------------------------
         */

        $fields = LeadField::where(
            'list_id',
            $lead->list_id
        )
            ->orderBy('sort_order')
            ->get();

        $leadFeedback = LeadFeedback::with([
            'feedback',
            'subFeedback',
            'user'
        ])
            ->where('lead_id', $lead->id)
            ->latest('id')
            ->get();

        $activities = LeadActivityLog::with('user:id,name')
            ->where('lead_id', $lead->id)
            ->latest('id')
            ->limit(50)
            ->get();

        $users = User::select(
            'id',
            'name'
        )
            ->orderBy('name')
            ->get();

        $feedbacks = Feedback::whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $feedbackLookup = Feedback::where(
            'added_by',
            $user->id
        )->get([
            'id',
            'name'
        ]);

        $privacyService = app(\App\Services\PrivacyService::class);
        $privacySettings = $privacyService->getAll();

        return view(
            'lms.pages.lead-view',
            compact(
                'lead',
                'fields',
                'leadFeedback',
                'activities',
                'users',
                'feedbacks',
                'feedbackLookup',
                'privacyService',
                'privacySettings'
            )
        );
    }

    public function leadDelete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:leads,id',
        ]);

        try {
            DB::beginTransaction();

            $user = auth()->user();
            $tenantId = $user->tenant_id ?? null;

            $leadQuery = Lead::where('id', $request->id);

            if ($tenantId) {
                $leadQuery->where('tenant_id', $tenantId);
            } else {
                $leadQuery->where(function ($query) {
                    $query
                        ->where('added_by', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('assigned_to', auth()->id());
                });
            }

            $lead = $leadQuery->first();

            if (!$lead) {
                return response()->json([
                    'status' => false,
                    'message' => 'Lead not found or access denied.'
                ], 404);
            }

            LeadFeedback::where('lead_id', $lead->id)->delete();
            LeadFollowup::where('lead_id', $lead->id)->delete();
            LeadActivityLog::where('lead_id', $lead->id)->delete();
            LeadNote::where('lead_id', $lead->id)->delete();
            DB::table('lead_field_values')
                ->where('lead_id', $lead->id)
                ->delete();

            $lead->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Lead deleted successfully.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function assignLeads(Request $request)
    {
        $request->validate([
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'required|integer|exists:leads,id',
            'target_role' => 'required|string',
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            $user = auth()->user();
            $tenantId = $user->tenant_id ?? null;

            if (!in_array($request->target_role, $this->getAssignableRoles($user), true)) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'You cannot assign leads to the selected role.',
                ], 403);
            }

            // dd($request->lead_ids);
            $leadQuery = Lead::whereIn('id', $request->lead_ids);

            if (!$user->hasRole('Admin')) {
                $visibleUserIds = $this->getVisibleUserIds($user);
                $leadQuery->where(function ($query) use ($visibleUserIds) {
                    $query
                        ->whereIn('assigned_to', $visibleUserIds)
                        ->orWhereIn('added_by', $visibleUserIds);
                });
            }

            // if ($tenantId) {
            //     $leadQuery->where('tenant_id', $tenantId);
            // } else {
            // $leadQuery->where('added_by', auth()->id());
            // }
            // dd($leadQuery->get());

            $leadIds = $leadQuery->pluck('id');

            if ($leadIds->isEmpty()) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'No valid leads found for assignment.'
                ], 404);
            }

            $assignedUser = $this
                ->assignableUsersQuery($user, $request->target_role)
                ->find($request->user_id);

            if (!$assignedUser) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'The selected user is outside your assignment hierarchy.',
                ], 403);
            }

            Lead::whereIn('id', $leadIds)->update([
                'assigned_to' => $assignedUser->id,
            ]);

            foreach ($leadIds as $leadId) {
                LeadActivityLog::create([
                    'tenant_id' => $tenantId ?? auth()->id(),
                    'lead_id' => $leadId,
                    'added_by' => auth()->id(),
                    'activity' => 'lead_assigned',
                    'old_value' => null,
                    'user_id' => auth()->id(),
                    'new_value' => json_encode([
                        'target_role' => $request->target_role,
                        'user_id' => $assignedUser->id,
                        'user_name' => $assignedUser->name,
                    ]),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lead assigned successfully.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function quickUpdate(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|integer|exists:leads,id',
            'feedback_id' => 'required|exists:feedbacks,id',
            'sub_feedback_id' => 'nullable|exists:feedbacks,id',
            'next_followup_at' => 'nullable|date',
            'remarks' => 'nullable|string|max:2000',
            'source' => 'nullable|in:dialer',
        ]);

        try {
            DB::beginTransaction();

            $lead = Lead::findOrFail($request->lead_id);

            LeadFeedback::create([
                'tenant_id' => auth()->id(),
                'lead_id' => $request->lead_id,
                'added_by' => auth()->id(),
                'feedback_id' => $request->feedback_id,
                'sub_feedback_id' => $request->sub_feedback_id,
                'followup_date' => $request->next_followup_at,
                'status' => 'completed',
                'remarks' => $request->remarks,
            ]);

            $leadUpdates = [];
            if ($request->filled('next_followup_at')) {
                $leadUpdates['next_followup_at'] = $request->next_followup_at;
            }

            // if ($request->source === 'dialer') {
            //     $leadUpdates['assigned_to'] = auth()->id();
            // }

            if (!empty($leadUpdates)) {
                $lead->update($leadUpdates);
            }
            
            $lead->touch();

            LeadActivityLog::create([
                'tenant_id' => auth()->id(),
                'lead_id' => $request->lead_id,
                'added_by' => auth()->id(),
                'activity' => 'feedback_added',
                'old_value' => null,
                'user_id' => auth()->id(),
                'new_value' => json_encode([
                    'feedback_id' => $request->feedback_id,
                    'sub_feedback_id' => $request->sub_feedback_id,
                    'followup_date' => $request->next_followup_at,
                    'remarks' => $request->remarks,
                    'source' => $request->source,
                    'assigned_to' => $request->source === 'dialer' ? auth()->id() : null,
                ]),
            ]);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Feedback saved successfully.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Return only users the authenticated user may receive leads under the
     * selected hierarchy level.
     */
    public function getAssignableUsers(Request $request)
    {
        $request->validate([
            'role' => 'required|string',
            'parent_id' => 'nullable|integer|exists:users,id',
        ]);

        $user = auth()->user();

        abort_unless(
            in_array($request->role, $this->getAssignableRoles($user), true),
            403,
            'You cannot assign leads to the selected role.'
        );

        $query = $this->assignableUsersQuery($user, $request->role);

        if ($request->filled('parent_id') && $request->role === 'TeamLeader') {
            $managerAllowed = $user->hasRole('Manager')
                ? $request->integer('parent_id') === $user->id
                : $this
                    ->assignableUsersQuery($user, 'Manager')
                    ->whereKey($request->integer('parent_id'))
                    ->exists();

            abort_unless($managerAllowed, 403, 'The selected manager is outside your hierarchy.');

            $query->whereHas(
                'details',
                fn($details) =>
                    $details->where('manager_id', $request->integer('parent_id'))
            );
        }

        if ($request->filled('parent_id') && $request->role === 'Agent') {
            $teamLeaderAllowed = $user->hasRole('TeamLeader')
                ? $request->integer('parent_id') === $user->id
                : $this
                    ->assignableUsersQuery($user, 'TeamLeader')
                    ->whereKey($request->integer('parent_id'))
                    ->exists();

            abort_unless($teamLeaderAllowed, 403, 'The selected Team Leader is outside your hierarchy.');

            $query->whereHas(
                'details',
                fn($details) =>
                    $details->where('teamleader_id', $request->integer('parent_id'))
            );
        }

        return response()->json($query->orderBy('name')->get());
    }

    /**
     * Get supervisors (TeamLeaders) by manager ID.
     * Returns all supervisors if no manager_id is provided.
     */
    public function getSupervisorsByManager(Request $request)
    {
        $query = User::role('TeamLeader')
            ->select('users.id', 'users.name');

        if ($request->filled('manager_id')) {
            $query->whereHas('details', function ($q) use ($request) {
                $q->where('manager_id', $request->manager_id);
            });
        }

        $supervisors = $query->orderBy('name')->get();

        return response()->json($supervisors);
    }

    /**
     * Get users (Agents) by supervisor (TeamLeader) ID.
     * Optionally also filters by manager_id.
     */
    public function getUsersBySupervisor(Request $request)
    {
        $query = User::role('Agent')
            ->select('users.id', 'users.name');

        if ($request->filled('supervisor_id')) {
            $query->whereHas('details', function ($q) use ($request) {
                $q->where('teamleader_id', $request->supervisor_id);
            });
        } elseif ($request->filled('manager_id')) {
            $query->whereHas('details', function ($q) use ($request) {
                $q->where('manager_id', $request->manager_id);
            });
        }

        $users = $query->orderBy('name')->get();

        return response()->json($users);
    }
}
