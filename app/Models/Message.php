<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_group' => 'boolean',
        'sent' => 'integer',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(Token::class, 'token', 'token');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(BaileySession::class, 'sessionId', 'id');
    }

    /**
     * Determine whether the target is a WhatsApp group.
     */
    public function getIsGroupTargetAttribute(): bool
    {
        if ($this->is_group) {
            return true;
        }

        $num = (string) $this->number;
        return str_ends_with($num, '@g.us') || str_contains($num, '-');
    }

    /**
     * Get clean formatted display target for UI.
     */
    public function getFormattedTargetAttribute(): string
    {
        $num = trim((string) $this->number);
        if ($num === '') {
            return '-';
        }

        if ($this->is_group_target) {
            return $this->name ? "{$this->name} ({$num})" : $num;
        }

        if ($num[0] === '+') {
            return $num;
        }

        return '+62' . $num;
    }
}
