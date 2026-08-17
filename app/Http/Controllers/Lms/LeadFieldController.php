<?php

namespace App\Http\Controllers\Lms;

use App\Models\LeadField;
use App\Models\LeadList;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeadFieldController extends Controller
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
            'list_id' => 'nullable|exists:lead_lists,id',
            'list_name' => 'nullable|string|max:255',
            'fields' => 'required|array|min:1',
        ]);

        try {
            DB::beginTransaction();

            $tenantId = auth()->id();
            $userId = auth()->id();

            if ($request->filled('list_id')) {
                $list = LeadList::findOrFail($request->list_id);
            } else {
                $listName = $request->filled('list_name')
                    ? trim($request->list_name)
                    : 'Field List ' . now()->format('YmdHis');

                $list = LeadList::create([
                    'added_by' => $userId,
                    'tenant_id' => $tenantId,
                    'name' => $listName,
                    'description' => 'Auto-generated list',
                    'is_active' => 1,
                    'created_by' => $userId,
                ]);
            }

            $listId = $list->id;

            $submittedFieldIds = [];

            foreach ($request->fields as $field) {
                if (empty(trim($field['name'] ?? ''))) {
                    continue;
                }

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

                $savedField = LeadField::updateOrCreate(
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

                $submittedFieldIds[] = $savedField->id;
            }

            // Remove any fields that were deleted from the UI
            if (!empty($submittedFieldIds)) {
                LeadField::where('list_id', $listId)
                    ->whereNotIn('id', $submittedFieldIds)
                    ->delete();
            } else {
                // If all fields were removed
                LeadField::where('list_id', $listId)->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fields saved successfully',
                'list_id' => $list->id,
                'list_name' => $list->name,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        // Placeholder as it was mapped but missing in original LeadController
        $request->validate(['id' => 'required|exists:lead_fields,id']);
        LeadField::destroy($request->id);
        return response()->json(['success' => true, 'message' => 'Field deleted successfully.']);
    }
}
