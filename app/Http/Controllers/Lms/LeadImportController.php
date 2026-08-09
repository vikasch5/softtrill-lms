<?php

namespace App\Http\Controllers\Lms;

use App\Imports\LeadsImport;
use App\Models\LeadField;
use App\Models\LeadImportFile;
use App\Models\LeadList;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class LeadImportController extends Controller
{
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

            if ($request->filled('list_id')) {
                $list = LeadList::findOrFail($request->list_id);
            } else {
                $file = $request->file('file');
                $rows = Excel::toArray([], $file);

                if (empty($rows[0]) || empty($rows[0][0])) {
                    throw new \Exception('File does not contain header row.');
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

                $sortOrder = 1;

                foreach ($headers as $header) {
                    LeadField::create([
                        'added_by' => auth()->id(),
                        'tenant_id' => $tenantId,
                        'list_id' => $list->id,
                        'name' => ucwords(str_replace('_', ' ', $header)),
                        'slug' => Str::slug($header, '_'),
                        'type' => 'text',
                        'is_required' => 0,
                        'is_filterable' => 1,
                        'is_searchable' => 1,
                        'is_unique' => 0,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            $import = LeadImportFile::create([
                'tenant_id' => $tenantId,
                'list_id' => $list->id,
                'file_name' => $request->file('file')->store('lead-imports'),
                'original_name' => $request->file('file')->getClientOriginalName(),
                'status' => 'processing',
                'uploaded_by' => $userId,
            ]);

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
}
