<?php

namespace App\Imports;

use App\Models\Lead;
use App\Models\LeadField;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
        Log::info('================ LEAD IMPORT STARTED ================', [
            'import_id' => $this->import->id ?? null,
            'list_id' => $this->listId,
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'rows_received' => $rows->count(),
            'database' => DB::connection()->getDatabaseName(),
            'connection' => DB::connection()->getName(),
        ]);

        try {

            /*
            |--------------------------------------------------------------------------
            | Log Excel Headers
            |--------------------------------------------------------------------------
            */

            if ($rows->isNotEmpty()) {

                Log::info('IMPORT EXCEL HEADERS', [
                    'headers' => $rows->first()->keys()->toArray(),
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Get Dynamic Lead Fields
            |--------------------------------------------------------------------------
            */

            $fieldSlugs = LeadField::where('list_id', $this->listId)
                ->pluck('slug')
                ->toArray();

            Log::info('Lead fields loaded', [
                'list_id' => $this->listId,
                'field_slugs' => $fieldSlugs,
                'field_count' => count($fieldSlugs),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Counters
            |--------------------------------------------------------------------------
            */

            $total = 0;
            $imported = 0;
            $failed = 0;
            $duplicates = 0;

            /*
            |--------------------------------------------------------------------------
            | Process Rows
            |--------------------------------------------------------------------------
            */

            foreach ($rows as $index => $row) {

                $total++;

                /*
                |--------------------------------------------------------------------------
                | Excel Row Number
                |--------------------------------------------------------------------------
                |
                | +2 because:
                | Row 1 = Heading
                | Row 2 = First data row
                |
                */

                $excelRowNumber = $index + 2;

                Log::info('Processing lead import row', [
                    'row_number' => $excelRowNumber,
                    'collection_index' => $index,
                    'raw_row' => $row->toArray(),
                ]);

                try {

                    $rowData = $row->toArray();

                    /*
                    |--------------------------------------------------------------------------
                    | NAME
                    |--------------------------------------------------------------------------
                    */

                    $name = null;

                    if (
                        isset($rowData['name']) &&
                        trim((string) $rowData['name']) !== ''
                    ) {
                        $name = trim((string) $rowData['name']);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | EMAIL
                    |--------------------------------------------------------------------------
                    */

                    $email = null;

                    $emailColumns = [
                        'email',
                        'email_id',
                        'email_address',
                        'mail',
                    ];

                    foreach ($emailColumns as $emailColumn) {

                        if (
                            array_key_exists($emailColumn, $rowData) &&
                            trim((string) $rowData[$emailColumn]) !== ''
                        ) {
                            $email = strtolower(
                                trim((string) $rowData[$emailColumn])
                            );

                            break;
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | PHONE / MOBILE
                    |--------------------------------------------------------------------------
                    |
                    | Supports:
                    |
                    | phone_number
                    | phone
                    | mobile_number
                    | mobile
                    | contact_number
                    | contact_no
                    | mobile_no
                    |
                    */

                    $phone = null;

                    $phoneColumns = [
                        'phone_number',
                        'phone',
                        'mobile_number',
                        'mobile',
                        'mobile_no',
                        'contact_number',
                        'contact_no',
                        'contact',
                    ];

                    foreach ($phoneColumns as $phoneColumn) {

                        if (
                            array_key_exists($phoneColumn, $rowData) &&
                            trim((string) $rowData[$phoneColumn]) !== ''
                        ) {

                            $phone = preg_replace(
                                '/\D/',
                                '',
                                (string) $rowData[$phoneColumn]
                            );

                            if ($phone !== '') {
                                break;
                            }

                            $phone = null;
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Remove Main Fields From Dynamic Data
                    |--------------------------------------------------------------------------
                    */

                    foreach (array_merge(
                        ['name'],
                        $emailColumns,
                        $phoneColumns
                    ) as $column) {

                        unset($rowData[$column]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Log Parsed Basic Fields
                    |--------------------------------------------------------------------------
                    */

                    Log::info('Parsed lead basic fields', [
                        'row_number' => $excelRowNumber,
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Dynamic Lead Data
                    |--------------------------------------------------------------------------
                    */

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

                    // Log::info('Lead dynamic data prepared', [
                    //     'row_number' => $excelRowNumber,
                    //     'lead_data' => $leadData,
                    // ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Check Empty Row
                    |--------------------------------------------------------------------------
                    */

                    if (
                        empty($leadData) &&
                        empty($name) &&
                        empty($email) &&
                        empty($phone)
                    ) {

                        Log::warning(
                            'Lead row skipped because it is completely empty',
                            [
                                'row_number' => $excelRowNumber,
                                'raw_row' => $rowData,
                            ]
                        );

                        $failed++;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Duplicate Detection
                    |--------------------------------------------------------------------------
                    |
                    | IMPORTANT:
                    |
                    | Do NOT create a hash when BOTH email and phone
                    | are empty.
                    |
                    | Otherwise every record with:
                    |
                    | email = null
                    | phone = null
                    |
                    | gets the same hash and becomes a duplicate.
                    |
                    */

                    $duplicateHash = null;
                    $exists = false;

                    if (!empty($email) || !empty($phone)) {

                        $duplicateHash = md5(
                            $this->listId . '|' .
                            strtolower($email ?? '') . '|' .
                            ($phone ?? '')
                        );

                        Log::info('Duplicate hash generated', [
                            'row_number' => $excelRowNumber,
                            'duplicate_hash' => $duplicateHash,
                            'list_id' => $this->listId,
                            'email' => $email,
                            'phone' => $phone,
                        ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Check Duplicate
                        |--------------------------------------------------------------------------
                        */

                        $exists = Lead::where('tenant_id', $this->tenantId)
                            ->where('list_id', $this->listId)
                            ->where('duplicate_hash', $duplicateHash)
                            ->exists();

                        Log::info('Duplicate check completed', [
                            'row_number' => $excelRowNumber,
                            'duplicate_hash' => $duplicateHash,
                            'email' => $email,
                            'phone' => $phone,
                            'exists' => $exists,
                        ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Duplicate Found
                        |--------------------------------------------------------------------------
                        */

                        if ($exists) {

                            $duplicates++;

                            Log::warning('Lead skipped as duplicate', [
                                'row_number' => $excelRowNumber,
                                'duplicate_hash' => $duplicateHash,
                                'email' => $email,
                                'phone' => $phone,
                            ]);

                            continue;
                        }

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | No Email / Phone
                        |--------------------------------------------------------------------------
                        |
                        | Do NOT treat this as duplicate.
                        |
                        */

                        Log::warning(
                            'No email or phone found - duplicate check skipped',
                            [
                                'row_number' => $excelRowNumber,
                                'name' => $name,
                            ]
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Prepare Insert Data
                    |--------------------------------------------------------------------------
                    */

                    $insertData = [
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
                    ];

                    /*
                    |--------------------------------------------------------------------------
                    | Log Before Insert
                    |--------------------------------------------------------------------------
                    */

                    // Log::info('Attempting Lead insert', [
                    //     'row_number' => $excelRowNumber,
                    //     'insert_data' => $insertData,
                    //     'database' => DB::connection()->getDatabaseName(),
                    //     'connection' => DB::connection()->getName(),
                    // ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Create Lead
                    |--------------------------------------------------------------------------
                    */

                    $lead = Lead::create($insertData);

                    /*
                    |--------------------------------------------------------------------------
                    | Check Lead::create()
                    |--------------------------------------------------------------------------
                    */

                    // Log::info('Lead::create() returned successfully', [
                    //     'row_number' => $excelRowNumber,
                    //     'lead_id' => $lead->id ?? null,
                    //     'exists_after_create' => $lead->exists,
                    //     'attributes' => $lead->getAttributes(),
                    // ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Validate Insert ID
                    |--------------------------------------------------------------------------
                    */

                    if (!$lead->exists || !$lead->id) {

                        Log::error(
                            'Lead create returned without valid ID',
                            [
                                'row_number' => $excelRowNumber,
                                'lead_exists' => $lead->exists,
                                'lead_id' => $lead->id ?? null,
                                'attributes' => $lead->getAttributes(),
                            ]
                        );

                        $failed++;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Verify Record Directly From Database
                    |--------------------------------------------------------------------------
                    */

                    $dbLead = Lead::on(
                        $lead->getConnectionName()
                    )
                        ->where('id', $lead->id)
                        ->first();

                    if (!$dbLead) {

                        Log::error(
                            'CRITICAL: Lead ID returned but record was NOT found in database',
                            [
                                'row_number' => $excelRowNumber,
                                'lead_id' => $lead->id,
                                'model_connection' => $lead->getConnectionName(),
                                'database' => DB::connection(
                                    $lead->getConnectionName()
                                )->getDatabaseName(),
                            ]
                        );

                        $failed++;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | SUCCESS
                    |--------------------------------------------------------------------------
                    */

                    // Log::info('Lead successfully inserted and verified', [
                    //     'row_number' => $excelRowNumber,
                    //     'lead_id' => $lead->id,
                    //     'name' => $name,
                    //     'email' => $email,
                    //     'phone' => $phone,
                    //     'database' => DB::connection(
                    //         $lead->getConnectionName()
                    //     )->getDatabaseName(),
                    //     'connection' => $lead->getConnectionName(),
                    // ]);

                    $imported++;

                } catch (\Throwable $e) {

                    /*
                    |--------------------------------------------------------------------------
                    | Row-Level Error
                    |--------------------------------------------------------------------------
                    */

                    Log::error(
                        '!!!!!!!!!!!!!!!! LEAD IMPORT ROW FAILED !!!!!!!!!!!!!!!!',
                        [
                            'row_number' => $excelRowNumber,

                            'list_id' => $this->listId,

                            'tenant_id' => $this->tenantId,

                            'user_id' => $this->userId,

                            'row' => $row->toArray(),

                            'error_class' => get_class($e),

                            'error_message' => $e->getMessage(),

                            'error_code' => $e->getCode(),

                            'file' => $e->getFile(),

                            'line' => $e->getLine(),

                            'trace' => $e->getTraceAsString(),

                            'database' => DB::connection()->getDatabaseName(),

                            'connection' => DB::connection()->getName(),
                        ]
                    );

                    $failed++;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Final Counters
            |--------------------------------------------------------------------------
            */

            // Log::info('Lead import processing completed', [
            //     'import_id' => $this->import->id ?? null,

            //     'total' => $total,

            //     'imported' => $imported,

            //     'duplicates' => $duplicates,

            //     'failed' => $failed,

            //     'database' => DB::connection()->getDatabaseName(),

            //     'connection' => DB::connection()->getName(),
            // ]);

            /*
            |--------------------------------------------------------------------------
            | Update Import Record
            |--------------------------------------------------------------------------
            */

            $updated = $this->import->update([
                'total_records' => $total,

                'imported_records' => $imported,

                'failed_records' => $failed,

                'status' => 'completed',
            ]);

            Log::info('Import record updated', [
                'import_id' => $this->import->id ?? null,

                'update_result' => $updated,

                'total_records' => $total,

                'imported_records' => $imported,

                'failed_records' => $failed,

                'duplicates' => $duplicates,

                'final_status' => $this->import->fresh()->status ?? null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Import Finished
            |--------------------------------------------------------------------------
            */

            Log::info('================ LEAD IMPORT FINISHED ================', [
                'import_id' => $this->import->id ?? null,

                'total' => $total,

                'imported' => $imported,

                'duplicates' => $duplicates,

                'failed' => $failed,
            ]);

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Complete Import Failure
            |--------------------------------------------------------------------------
            */

            Log::critical(
                '!!!!!!!!!!!!!!!! LEAD IMPORT COMPLETELY FAILED !!!!!!!!!!!!!!!!',
                [
                    'import_id' => $this->import->id ?? null,

                    'list_id' => $this->listId,

                    'tenant_id' => $this->tenantId,

                    'user_id' => $this->userId,

                    'error_class' => get_class($e),

                    'error_message' => $e->getMessage(),

                    'error_code' => $e->getCode(),

                    'file' => $e->getFile(),

                    'line' => $e->getLine(),

                    'trace' => $e->getTraceAsString(),

                    'database' => DB::connection()->getDatabaseName(),

                    'connection' => DB::connection()->getName(),
                ]
            );

            throw $e;
        }
    }
}