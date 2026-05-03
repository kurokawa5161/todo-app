<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationLog extends Model
{
    protected $fillable = [
        'user_id',
        'service',
        'action',
        'payload',
        'response',
        'status',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
