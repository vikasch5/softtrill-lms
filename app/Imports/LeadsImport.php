<?php

namespace App\Imports;

use App\Models\Lead;
use App\Models\LeadField;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LeadsImport implements ToCollection, WithHeadingRow
{
    protected $import;
    protected $listId;
    protected $tenantId;
    protected $userId;

    public function __construct(
        $import,
        $listId,
        $tenantId,
        $userId
    ) {
        $this->import = $import;
        $this->listId = $listId;
        $this->tenantId = $tenantId;
        $this->userId = $userId;
    }

    public function collection(Collection $rows)
    {
        $fieldSlugs = LeadField::where('list_id', $this->listId)
            ->pluck('slug')
            ->toArray();

        $total = 0;
        $imported = 0;
        $failed = 0;
        $duplicates = 0;

        foreach ($rows as $row) {

            $total++;

            try {

                $rowData = $row->toArray();

                $leadData = [];

                foreach ($fieldSlugs as $slug) {

                    if (!array_key_exists($slug, $rowData)) {
                        continue;
                    }

                    $value = trim((string) $rowData[$slug]);

                    if ($value === '') {
                        continue;
                    }

                    $leadData[$slug] = $value;
                }

                if (empty($leadData)) {

                    $failed++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Email & Phone Detection
                |--------------------------------------------------------------------------
                */

                $email = null;
                $phone = null;

                foreach ($leadData as $key => $value) {

                    if (
                        !$email &&
                        filter_var($value, FILTER_VALIDATE_EMAIL)
                    ) {
                        $email = strtolower($value);
                    }

                    if (
                        !$phone &&
                        preg_match('/^[0-9\-\+\s\(\)]{8,20}$/', $value)
                    ) {
                        $phone = preg_replace('/\D/', '', $value);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Duplicate Hash
                |--------------------------------------------------------------------------
                */

                $duplicateHash = md5(
                    $this->listId . '|' .
                    strtolower($email ?? '') . '|' .
                    ($phone ?? '')
                );

                $exists = Lead::where(
                    'tenant_id',
                    $this->tenantId
                )
                    ->where(
                        'list_id',
                        $this->listId
                    )
                    ->where(
                        'duplicate_hash',
                        $duplicateHash
                    )
                    ->exists();

                if ($exists) {

                    $duplicates++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Create Lead
                |--------------------------------------------------------------------------
                */

                Lead::create([

                    'tenant_id' => $this->tenantId,

                    'list_id' => $this->listId,

                    'assigned_to' => $this->userId,

                    'status' => 'new',

                    'email_index' => $email,

                    'phone_index' => $phone,

                    'duplicate_hash' => $duplicateHash,

                    'data' => $leadData,

                    'created_by' => $this->userId,

                ]);

                $imported++;

            } catch (\Throwable $e) {

                Log::error('Lead Import Failed', [

                    'list_id' => $this->listId,

                    'row' => $row->toArray(),

                    'error' => $e->getMessage(),

                ]);

                $failed++;
            }
        }

        $this->import->update([

            'total_records' => $total,

            'imported_records' => $imported,

            'failed_records' => $failed,

            'status' => 'completed',

        ]);
    }
}