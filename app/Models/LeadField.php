<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadField extends Model
{
    protected $fillable = [
        'added_by',
        'tenant_id',
        'list_id',
        'name',
        'slug',
        'type',
        'is_required',
        'is_filterable',
        'is_searchable',
        'is_unique',
        'options',
        'sort_order'
    ];

    // public function leadList()
    // {
    //     return $this->belongsTo(LeadList::class, 'list_id');
    // }
}
