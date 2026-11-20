<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaileySession extends Model
{
    protected $table = 'bailey_sessions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    public function grup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
