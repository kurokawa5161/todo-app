<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    protected $fillable  = [
        'user_id',
        'widget_type',
        'position',
        'size',
        'is_visible',
        'settings'
    ];

    protected $casts = [
        'settings' => 'array',
        'is_visible' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
