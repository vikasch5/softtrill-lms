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

class LeadController extends Controller
{

    public function leadsList(Request $request)
    {
        $query = Lead::query()
            ->with(['list', 'assignedTo'])
            ->where('added_by', auth()->id());

        if ($request->filled('list_id')) {
            $query->where('list_id', $request->list_id);
        }

        $leads = $query
            ->latest()
            ->paginate(20);

        $managers = User::role('Manager')
            ->orderBy('name')
            ->get(['id', 'name']);

        $supervisors = User::role('TeamLeader')
            ->with('details')
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = User::role('Agent')
            ->with('details')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view(
            'lms.pages.leads-list',
            compact('leads', 'managers', 'supervisors', 'users')
        );
    }
    public function fieldList()
    {
        $lists = DB::table('lead_lists as l')
            ->leftJoin('lead_fields as lf', 'lf.list_id', '=', 'l.id')
            ->select(
                'l.id',
                'l.name',
                'l.is_active',
                'l.created_at',
                DB::raw('COUNT(lf.id) as total_fields')
            )
            ->groupBy(
                'l.id',
                'l.name',
                'l.is_active',
                'l.created_at'
            )
            ->orderBy('l.id', 'desc')
            ->get();

        return view(
            'lms.pages.field-list',
            compact('lists')
        );
    }
    public function fieldAddIndex($listId = null)
    {
        $list = null;

        $fieldsData = collect();

        if ($listId) {

            $list = DB::table('lead_lists')
                ->where('id', $listId)
                ->first();

            abort_if(!$list, 404);

            $fieldsData = DB::table('lead_fields')
                ->where('list_id', $listId)
                ->orderBy('sort_order')
                ->get();
        }

        if ($fieldsData->isEmpty()) {

            $fieldsData = collect([
                (object) [
                    'id' => null,
                    'name' => '',
                    'type' => 'text',
                    'options' => null,
                    'sort_order' => 0,
                    'is_required' => 0,
                    'is_filterable' => 0,
                    'is_searchable' => 0,
                    'is_unique' => 0,
                ]
            ]);
        }

        return view(
            'lms.pages.field-add',
            compact('list', 'fieldsData')
        );
    }

    public function fieldStoreOrUpdate(Request $request)
    {
        $request->validate([
            // 'list_id' => 'required|exists:lead_lists,id',
            'fields' => 'required|array|min:1',
        ]);

        try {

            DB::beginTransaction();

            $tenantId = auth()->id();
            $listId = '1';

            foreach ($request->fields as $field) {

                if (empty(trim($field['name'] ?? ''))) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Generate Slug
                |--------------------------------------------------------------------------
                */

                $slug = Str::slug($field['name'], '_');

                $originalSlug = $slug;
                $counter = 1;

                while (
                    LeadField::where('list_id', $listId)
                        ->where('slug', $slug)
                        ->when(
                            !empty($field['id']),
                            fn($q) => $q->where('id', '!=', $field['id'])
                        )
                        ->exists()
                ) {
                    $slug = $originalSlug . '_' . $counter;
                    $counter++;
                }

                /*
                |--------------------------------------------------------------------------
                | Options
                |--------------------------------------------------------------------------
                */

                $options = null;

                if (
                    in_array(
                        $field['type'] ?? '',
                        ['select', 'radio', 'checkbox']
                    )
                ) {

                    $options = collect(
                        explode(',', $field['options'] ?? '')
                    )
                        ->map(fn($item) => trim($item))
                        ->filter()
                        ->values()
                        ->toArray();

                    $options = empty($options)
                        ? null
                        : json_encode($options);
                }

                /*
                |--------------------------------------------------------------------------
                | Save
                |--------------------------------------------------------------------------
                */

                LeadField::updateOrCreate(
                    [
                        'id' => $field['id'] ?? null,
                    ],
                    [
                        'added_by' => auth()->id(),
                        'tenant_id' => $tenantId,
                        'list_id' => $listId,

                        'name' => trim($field['name']),
                        'slug' => $slug,

                        'type' => $field['type'] ?? 'text',

                        'is_required' => (bool) ($field['is_required'] ?? 0),

                        'is_filterable' => (bool) ($field['is_filterable'] ?? 0),

                        'is_searchable' => (bool) ($field['is_searchable'] ?? 0),

                        'is_unique' => (bool) ($field['is_unique'] ?? 0),

                        'options' => $options,

                        'sort_order' => (int) ($field['sort_order'] ?? 0),
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fields saved successfully'
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function leadImport()
    {
        $lists = LeadList::where('added_by', auth()->id())->get();
        return view('lms.pages.lead-import', compact('lists'));
    }

    public function downloadSample($listId)
    {
        $leadFields = LeadField::where('list_id', $listId)
            ->orderBy('sort_order')
            ->get();

        if ($leadFields->isEmpty()) {
            abort(404, 'No fields found for this list.');
        }

        $filename = 'sample_leads_' . $listId . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate',
            'Expires' => '0',
        ];

        $callback = function () use ($leadFields) {

            $file = fopen('php://output', 'w');

            // Default Columns
            $columns = [
                'name',
                'email',
                'phone_number'
            ];

            // Dynamic Fields
            $columns = array_merge(
                $columns,
                $leadFields->pluck('slug')->toArray()
            );

            fputcsv($file, $columns);

            // Default Sample Data
            $sampleRow = [
                'John Doe',
                'john@example.com',
                '9876543210'
            ];

            // Dynamic Sample Data
            foreach ($leadFields as $field) {

                switch ($field->type) {

                    case 'email':
                        $sampleRow[] = 'john@example.com';
                        break;

                    case 'phone':
                        $sampleRow[] = '9876543210';
                        break;

                    case 'number':
                        $sampleRow[] = '100';
                        break;

                    case 'decimal':
                        $sampleRow[] = '1000.50';
                        break;

                    case 'date':
                        $sampleRow[] = now()->format('Y-m-d');
                        break;

                    case 'datetime':
                        $sampleRow[] = now()->format('Y-m-d H:i:s');
                        break;

                    case 'boolean':
                        $sampleRow[] = '1';
                        break;

                    case 'select':
                    case 'radio':
                    case 'checkbox':

                        $options = json_decode($field->options, true);

                        $sampleRow[] = $options[0] ?? 'Option1';
                        break;

                    case 'textarea':
                        $sampleRow[] = 'Sample description';
                        break;

                    default:
                        $sampleRow[] = 'Sample ' . $field->name;
                        break;
                }
            }

            fputcsv($file, $sampleRow);

            fclose($file);
        };

        return response()->stream(
            $callback,
            200,
            $headers
        );
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'list_id' => 'nullable|exists:lead_lists,id',
        ]);

        DB::beginTransaction();

        try {

            $tenantId = auth()->id();
            $userId = auth()->id();

            /*
            |--------------------------------------------------------------------------
            | Existing List
            |--------------------------------------------------------------------------
            */

            if ($request->filled('list_id')) {

                $list = LeadList::findOrFail($request->list_id);

            } else {

                /*
                |--------------------------------------------------------------------------
                | Auto Create List
                |--------------------------------------------------------------------------
                */

                $file = $request->file('file');

                $rows = Excel::toArray([], $file);

                if (empty($rows[0]) || empty($rows[0][0])) {

                    throw new \Exception(
                        'File does not contain header row.'
                    );
                }

                $headers = array_filter(
                    array_map('trim', $rows[0][0])
                );

                $list = LeadList::create([
                    'added_by' => auth()->id(),
                    'tenant_id' => $tenantId,
                    'name' => 'Imported List ' . now()->format('YmdHis'),
                    'description' => 'Auto generated from import file',
                    'is_active' => 1,
                    'created_by' => $userId,
                ]);


                /*
                |--------------------------------------------------------------------------
                | Auto Create Fields
                |--------------------------------------------------------------------------
                */

                $sortOrder = 1;

                foreach ($headers as $header) {

                    LeadField::create([

                        'added_by' => auth()->id(),
                        'tenant_id' => $tenantId,
                        'list_id' => $list->id,

                        'name' => ucwords(
                            str_replace('_', ' ', $header)
                        ),

                        'slug' => Str::slug(
                            $header,
                            '_'
                        ),

                        'type' => 'text',

                        'is_required' => 0,
                        'is_filterable' => 1,
                        'is_searchable' => 1,
                        'is_unique' => 0,

                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Import Log
            |--------------------------------------------------------------------------
            */

            $import = LeadImportFile::create([
                'tenant_id' => $tenantId,

                'list_id' => $list->id,

                'file_name' => $request
                    ->file('file')
                    ->store('lead-imports'),

                'original_name' => $request
                    ->file('file')
                    ->getClientOriginalName(),

                'status' => 'processing',

                'uploaded_by' => $userId,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Import Leads
            |--------------------------------------------------------------------------
            */

            Excel::import(
                new LeadsImport(
                    $import,
                    $list->id,
                    $tenantId,
                    $userId
                ),
                $request->file('file')
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leads imported successfully.',
                'list_id' => $list->id,
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function leadAdd()
    {
        $lists = LeadImportFile::where('tenant_id', auth()->user()->tenant_id)->get();
        $leadFields = LeadField::orderBy('sort_order')->get();
        return view('lms.pages.lead-add', compact('leadFields', 'lists'));
    }

    public function storeOrUpdate(Request $request, $id = null)
    {
        DB::beginTransaction();

        try {

            $tenantId = auth()->user()->tenant_id ?? null;
            $userId = auth()->id();
            $listId = $request->lead_import_file_id; // your list

            // =========================
            // VALIDATION
            // =========================
            $request->validate([
                'phone' => 'required|digits:10',
                'email' => 'nullable|email',
            ]);

            // =========================
            // FIND OR CREATE
            // =========================
            $lead = $id
                ? Lead::where('tenant_id', $tenantId)->findOrFail($id)
                : new Lead();

            // =========================
            // DUPLICATE CHECK (LIST BASED)
            // =========================
            $duplicate = Lead::where('phone', $request->phone)
                ->where('tenant_id', $tenantId)
                ->where('lead_import_file_id', $listId)
                ->when($id, fn($q) => $q->where('id', '!=', $id))
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead already exists in this list'
                ]);
            }

            // =========================
            // BASIC FIELDS
            // =========================
            $lead->tenant_id = $tenantId;
            $lead->lead_import_file_id = $listId;

            $lead->name = $request->name;
            $lead->phone = $request->phone;
            $lead->email = $request->email;
            $lead->city = $request->city;
            // $lead->status = $request->status ?? 'new';

            $lead->assigned_to = $lead->assigned_to ?? $userId;
            $lead->created_by = $lead->created_by ?? $userId;

            // =========================
            // DYNAMIC FIELDS
            // =========================
            $customFields = [];
            $filterableInsert = [];

            // fetch all fields once (optimized)
            $leadFields = LeadField::select('id', 'slug', 'is_filterable')->get()->keyBy('slug');

            foreach ($request->custom ?? [] as $slug => $value) {

                // store in JSON (ALL fields)
                $customFields[$slug] = $value;

                // store in table only if filterable
                if (isset($leadFields[$slug]) && $leadFields[$slug]->is_filterable) {

                    $filterableInsert[] = [
                        'field_id' => $leadFields[$slug]->id,
                        'value' => is_array($value) ? json_encode($value) : $value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // save lead first
            $lead->custom_fields = json_encode($customFields);
            $lead->save();

            // =========================
            // FILTERABLE FIELDS TABLE
            // =========================

            // delete old values (for update)
            DB::table('lead_field_values')
                ->where('lead_id', $lead->id)
                ->delete();

            // attach lead_id
            foreach ($filterableInsert as &$row) {
                $row['lead_id'] = $lead->id;
            }

            // bulk insert
            if (!empty($filterableInsert)) {
                DB::table('lead_field_values')->insert($filterableInsert);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $id ? 'Lead Updated Successfully' : 'Lead Created Successfully',
                'lead_id' => $lead->id
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
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

        $lead->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,

            'data' => $leadData,

            'email_index' =>
                $leadData['email']
                ?? null,

            'phone_index' =>
                $leadData['phone']
                ?? null,

        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lead updated successfully.'
        ]);
    }

    public function leadsView($id)
    {
        $lead = Lead::findOrFail($id);

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
            ->latest()
            ->get();

        $followups = LeadFollowup::where(
            'lead_id',
            $lead->id
        )
            ->latest()
            ->get();

        $activities = LeadActivityLog::where(
            'lead_id',
            $lead->id
        )
            ->latest()
            ->limit(50)
            ->get();

        $users = User::select(
            'id',
            'name'
        )
            ->orderBy('name')
            ->get();

        $feedbacks = Feedback::where('added_by', auth()->id())->where('parent_id', null)
            ->orderBy('name')
            ->get();
        return view(
            'lms.pages.lead-view',
            compact(
                'lead',
                'fields',
                'leadFeedback',
                'followups',
                'activities',
                'users',
                'feedbacks'
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
                    $query->where('added_by', auth()->id())
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
            'manager_id' => 'nullable|exists:users,id',
            'supervisor_id' => 'nullable|exists:users,id',
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            $user = auth()->user();
            $tenantId = $user->tenant_id ?? null;

            $leadQuery = Lead::whereIn('id', $request->lead_ids);

            if ($tenantId) {
                $leadQuery->where('tenant_id', $tenantId);
            } else {
                $leadQuery->where('added_by', auth()->id());
            }

            $leadIds = $leadQuery->pluck('id');

            if ($leadIds->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid leads found for assignment.'
                ], 404);
            }

            $assignedUser = User::findOrFail($request->user_id);

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
                        'manager_id' => $request->manager_id,
                        'supervisor_id' => $request->supervisor_id,
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

    public function feedbackAdd($id = null)
    {
        $feedback = null;

        if ($id) {
            $feedback = Feedback::findOrFail($id);
        }

        $parents = Feedback::whereNull('parent_id')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        return view('lms.pages.feedback-add', compact(
            'feedback',
            'parents'
        ));
    }

    public function feedbackStoreOrUpdate(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255',
            'parent_id' => 'nullable|exists:feedbacks,id',
            'status' => 'required|boolean',
        ]);

        Feedback::updateOrCreate(
            [
                'id' => $request->feedback_id
            ],
            [
                'tenant_id' => auth()->user()->tenant_id,
                'parent_id' => $request->parent_id,
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'status' => $request->status,
            ]
        );

        $msg = $request->feedback_id
            ? 'Feedback updated successfully'
            : 'Feedback created successfully';
        return response()->json([
            'success' => true,
            'message' => $msg
        ]);
    }

    public function feedbackList()
    {
        $feedbacks = Feedback::with('parent')
            ->latest()
            ->paginate(20);

        return view('lms.pages.feedback-list', compact('feedbacks'));
    }

    public function feedbackDelete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:feedbacks,id',
        ]);

        $feedback = Feedback::findOrFail($request->id);

        $feedback->delete();

        return response()->json([
            'status' => true,
            'message' => 'Feedback deleted successfully'
        ]);

    }

    public function subFeedbacks($feedbackId)
    {
        $subFeedbacks = Feedback::where('parent_id', $feedbackId)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($subFeedbacks);
    }

    public function quickUpdate(Request $request)
    {
        $request->validate([
            'feedback_id' => 'required|exists:feedbacks,id',
            'sub_feedback_id' => 'nullable|exists:feedbacks,id',
            'next_followup_at' => 'nullable|date',
            'remarks' => 'nullable|string|max:2000',
        ]);

        try {
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

            // dd($request->all());

            if ($request->filled('next_followup_at')) {
                Lead::where('id', $request->lead_id)
                    ->update([
                        'next_followup_at' => $request->next_followup_at,
                    ]);
            }

            DB::beginTransaction();
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

}
