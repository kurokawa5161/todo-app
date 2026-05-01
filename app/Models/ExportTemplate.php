<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExportTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'format',
        'fields',
        'order',
        'filters',
    ];

    protected $casts = [
        'fields' => 'array',
        'order' => 'array',
        'filters' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
