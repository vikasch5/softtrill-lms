<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDetails extends Model
{

    protected $fillable = [
        'user_id',
        'employee_id',
        'phone',
        'profile_photo',
        'cluster_id',
        'manager_id',
        'teamleader_id',
        'designation',
        'department',
        'joining_date',
        'status',
        'is_online',
        'last_login',
        'last_logout'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cluster()
    {
        return $this->belongsTo(User::class, 'cluster_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function teamleader()
    {
        return $this->belongsTo(User::class, 'teamleader_id');
    }

}