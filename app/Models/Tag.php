<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'user_id'
    ];

    public function todos()
    {
        return $this->belongsToMany(Todo::class, 'todo_tag');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
