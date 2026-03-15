<?php

namespace App\Imports\Lms;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class LeadImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        //
    }
}
