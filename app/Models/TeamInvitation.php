<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TeamInvitation extends Model
{
    protected $fillable = [
        'team_id',
        'email',
        'token',
        'role',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    //有効期限切れかチェック
    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    //受諾済みかチェック
    public function isAccepted()
    {
        return $this->accepted_at !== null;
    }

    //UUID生成（静的メソッド）
    public static function generateToken()
    {
        return Str::uuid()->toString();
    }
}
