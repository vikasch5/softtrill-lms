<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Imports\LeadsImport;
use App\Models\Lead;
use App\Models\LeadField;
use App\Models\LeadImportFile;
use App\Models\LeadList;
use App\Models\Lists;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class LeadController extends Controller
{
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

            // CSV Headers
            $columns = $leadFields
                ->pluck('slug')
                ->toArray();

            fputcsv($file, $columns);

            // Sample Data Row
            $sampleRow = [];

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

                // $sortOrder = 1;

                // foreach ($headers as $header) {

                //     LeadField::create([
                //         'tenant_id' => $tenantId,
                //         'list_id' => $list->id,

                //         'name' => ucwords(
                //             str_replace('_', ' ', $header)
                //         ),

                //         'slug' => Str::slug(
                //             $header,
                //             '_'
                //         ),

                //         'type' => 'text',

                //         'is_required' => 0,
                //         'is_filterable' => 1,
                //         'is_searchable' => 1,
                //         'is_unique' => 0,

                //         'sort_order' => $sortOrder++,
                //     ]);
                // }
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

}