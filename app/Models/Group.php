<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function devices(): HasMany
    {
        return $this->hasMany(BaileySession::class, 'group_id');
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class, 'group_id');
    }
}
