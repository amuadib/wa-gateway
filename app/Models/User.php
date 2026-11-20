<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'managed_groups',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'managed_groups' => 'array',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(BaileySession::class, 'user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function managedGroupIds(): array
    {
        return is_array($this->managed_groups) ? array_map('intval', $this->managed_groups) : [];
    }
}
