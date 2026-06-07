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

                $name = isset($rowData['name'])
                    ? trim($rowData['name'])
                    : null;

                $email = isset($rowData['email'])
                    ? strtolower(trim($rowData['email']))
                    : null;

                $phone = isset($rowData['phone_number'])
                    ? preg_replace('/\D/', '', $rowData['phone_number'])
                    : null;
                unset(
                    $rowData['name'],
                    $rowData['email'],
                    $rowData['phone_number']
                );

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

                if (empty($leadData) && empty($name) && empty($email) && empty($phone)) {
                    $failed++;
                    continue;
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

                Log::info('Creating Lead', [
                    'list_id' => $this->listId,
                    'email' => $email,
                    'phone' => $phone,
                ]);
                Lead::create([

                    'name' => $name,
                    'email' => $email,
                    'phone_number' => $phone,

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