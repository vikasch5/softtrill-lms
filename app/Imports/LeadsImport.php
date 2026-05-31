<?php

namespace App\Imports;

use App\Models\Lead;
use App\Models\LeadField;
use App\Models\LeadFieldValue;
use App\Models\ListLead;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LeadsImport implements ToCollection, WithHeadingRow
{
    protected $listId;
    protected $import;
    protected $tenantId;
    protected $userId;

    public function __construct($import, $listId, $tenantId, $userId)
    {
        $this->import = $import;
        $this->listId = $listId;
        $this->tenantId = $tenantId;
        $this->userId = $userId;
    }

    public function collection(Collection $rows)
    {
        $fields = LeadField::get()->keyBy('slug');

        $total = 0;
        $imported = 0;
        $failed = 0;

        foreach ($rows as $row) {

            $total++;

            try {

                DB::beginTransaction();

                // =========================
                // 1. VALIDATE PHONE
                // =========================
                $phone = preg_replace('/\D/', '', $row['phone'] ?? '');

                if (!$phone) {
                    $failed++;
                    DB::rollBack();
                    continue;
                }

                // =========================
                // 2. CREATE / UPDATE MASTER LEAD
                // =========================
                $lead = Lead::updateOrCreate(
                    [
                        'phone' => $phone,
                        'tenant_id' => $this->tenantId
                    ],
                    [
                        'lead_import_file_id' => $this->import->id,
                        'created_by' => $this->userId,

                        'name' => $row['name'] ?? null,
                        'email' => $row['email'] ?? null,
                        'address' => $row['address'] ?? null,
                        'city' => $row['city'] ?? null,

                        'custom_fields' => json_encode($row)
                    ]
                );

                // =========================
                // 3. ATTACH TO LIST (CORE)
                // =========================
                ListLead::updateOrCreate(
                    [
                        'list_id' => $this->listId,
                        'lead_id' => $lead->id,
                    ],
                    [
                        'tenant_id' => $this->tenantId,
                        'status' => 'new',
                        'assigned_to' => $this->userId,
                        'custom_data' => json_encode($row),
                    ]
                );

                // =========================
                // 4. FILTERABLE FIELDS
                // =========================
                foreach ($fields as $slug => $field) {

                    if (!isset($row[$slug])) continue;

                    $value = $row[$slug];
                    if ($value === null || $value === '') continue;

                    $data = [
                        'lead_id' => $lead->id,
                        'field_id' => $field->id,
                        'value_string' => null,
                        'value_number' => null,
                        'value_date' => null,
                        'value_boolean' => null,
                    ];

                    switch ($field->type) {

                        case 'number':
                            $data['value_number'] = (int)$value;
                            break;

                        case 'date':
                            $data['value_date'] = strtotime($value)
                                ? date('Y-m-d H:i:s', strtotime($value))
                                : null;
                            break;

                        case 'checkbox':
                            $data['value_boolean'] = in_array(strtolower($value), ['1','yes','true']);
                            break;

                        default:
                            $data['value_string'] = $value;
                            break;
                    }

                    LeadFieldValue::updateOrCreate(
                        [
                            'lead_id' => $lead->id,
                            'field_id' => $field->id,
                        ],
                        $data
                    );
                }

                DB::commit();
                $imported++;

            } catch (\Throwable $e) {

                DB::rollBack();

                Log::error('Lead Import Error', [
                    'row' => $row,
                    'error' => $e->getMessage()
                ]);

                $failed++;
            }
        }

        // =========================
        // 5. UPDATE IMPORT STATS
        // =========================
        $this->import->update([
            'total_records' => $total,
            'imported_records' => $imported,
            'failed_records' => $failed,
            'status' => 'completed'
        ]);
    }
}