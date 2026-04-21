<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ApiLog extends Model
{
    protected $fillable = [
        'user_id',
        'method',
        'endpoint',
        'ip_address',
        'status_code',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
