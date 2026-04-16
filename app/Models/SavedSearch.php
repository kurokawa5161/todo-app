<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedSearch extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'conditions'
    ];

    protected $casts = [
        'conditions' => 'json'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
