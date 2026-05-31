<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Imports\LeadsImport;
use App\Models\Lead;
use App\Models\LeadField;
use App\Models\LeadImportFile;
use App\Models\Lists;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class LeadController extends Controller
{
    public function fieldList()
    {
        $fields = DB::table('lead_fields')->orderBy('sort_order')->get();
        return view('lms.pages.field-list', compact('fields'));
    }
    public function fieldAddIndex($id = null)
    {
        $fieldsData = $id ? DB::table('lead_fields')->where('id', $id)->get() : null;
        return view('lms.pages.field-add', compact('fieldsData'));
    }

    public function fieldStoreOrUpdate(Request $request)
    {
        try {

            $fields = $request->fields;

            if (!$fields || !is_array($fields)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No fields data received'
                ]);
            }

            DB::beginTransaction();

            foreach ($fields as $field) {

                if (empty($field['name'])) {
                    continue;
                }

                $slug = Str::slug($field['name'], '_');

                $originalSlug = $slug;
                $count = 1;

                while (
                    DB::table('lead_fields')
                        ->where('slug', $slug)
                        ->when(!empty($field['id']), function ($q) use ($field) {
                            $q->where('id', '!=', $field['id']);
                        })
                        ->exists()
                ) {
                    $slug = $originalSlug . '_' . $count;
                    $count++;
                }

                $options = null;

                if (($field['type'] ?? '') === 'select' && !empty($field['options'])) {

                    $optionsArray = array_filter(array_map('trim', explode(',', $field['options'])));

                    $options = json_encode(array_values($optionsArray));
                }

                $data = [
                    'name' => $field['name'],
                    'slug' => $slug,
                    'type' => $field['type'] ?? 'text',
                    'sort_order' => $field['sort_order'] ?? 0,

                    'is_required' => isset($field['is_required']) ? 1 : 0,
                    'is_filterable' => isset($field['is_filterable']) ? 1 : 0,
                    'is_promoted' => isset($field['is_promoted']) ? 1 : 0,

                    'options' => $options,
                    'updated_at' => now(),
                ];

                if (!empty($field['id'])) {

                    DB::table('lead_fields')
                        ->where('id', $field['id'])
                        ->update($data);

                } else {

                    $data['created_at'] = now();

                    DB::table('lead_fields')->insert($data);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fields saved successfully'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error saving fields',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function leadImport()
    {
        $lists = Lists::where('added_by', auth()->id())->get();
        return view('lms.pages.lead-import', compact('lists'));
    }

    public function downloadSample()
    {
        $filename = "sample_leads.csv";

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
        ];

        // Default columns
        $columns = [
            'name',
            'email',
            'phone'
        ];

        // Fetch custom fields
        $leadFields = LeadField::orderBy('sort_order')->get();

        foreach ($leadFields as $field) {
            $columns[] = $field->slug; // IMPORTANT: use slug
        }

        $callback = function () use ($columns, $leadFields) {

            $file = fopen('php://output', 'w');

            // Add header row
            fputcsv($file, $columns);

            // Sample row
            $sampleRow = [
                'John Doe',
                'john@gmail.com',
                '9876543210'
            ];

            foreach ($leadFields as $field) {

                // Generate smart sample data based on field type
                switch ($field->type) {

                    case 'number':
                        $sampleRow[] = '123';
                        break;

                    case 'date':
                        $sampleRow[] = now()->format('Y-m-d');
                        break;

                    case 'select':
                        $options = json_decode($field->options, true);
                        $sampleRow[] = $options[0] ?? 'Option1';
                        break;

                    default:
                        $sampleRow[] = 'Sample ' . $field->name;
                        break;
                }
            }

            fputcsv($file, $sampleRow);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required',
            'list_id' => 'nullable|exists:lists,id',
            'list_name' => 'nullable|string|max:255'
        ]);

        $tenantId = auth()->id();
        $userId = auth()->id();

        // =========================
        // 1. CREATE OR USE LIST
        // =========================
        if ($request->list_id) {

            $list = Lists::findOrFail($request->list_id);

        } else {

            $lastId = Lists::max('id') + 1;
            $listCode = 'LIST' . str_pad($lastId, 4, '0', STR_PAD_LEFT);

            $list = Lists::create([
                'tenant_id' => $tenantId,
                'list_code' => $listCode,
                'name' => $request->list_name ?? 'Imported List ' . $listCode,
                'added_by' => $userId,
            ]);
        }

        // =========================
        // 2. CREATE IMPORT RECORD
        // =========================
        $import = LeadImportFile::create([
            'tenant_id' => $tenantId,
            'list_name' => $list->name,
            'list_code' => $list->list_code,
            'file_name' => $request->file('file')->store('imports'),
            'original_name' => $request->file('file')->getClientOriginalName(),
            'uploaded_by' => $userId,
            'status' => 'processing'
        ]);

        // =========================
        // 3. IMPORT (FIXED)
        // =========================
        Excel::import(
            new LeadsImport($import, $list->id, $tenantId, $userId),
            $request->file('file')
        );

        return response()->json([
            'success' => true,
            'message' => 'Leads Imported Successfully',
            'list_id' => $list->id
        ]);
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