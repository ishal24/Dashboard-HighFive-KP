<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'witel_id',
        'account_manager_id',
        'profile_image',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Role checking methods
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAccountManager(): bool
    {
        return $this->role === 'account_manager';
    }

    public function isWitel(): bool
    {
        return $this->role === 'witel';
    }

    // Relationships
    public function witel()
    {
        return $this->belongsTo(Witel::class)->withDefault();
    }

    public function accountManager()
    {
        return $this->belongsTo(AccountManager::class)->withDefault();
    }

    // Display name logic as per specification
    public function getDisplayName(): string
    {
        if ($this->isAccountManager() && $this->accountManager) {
            return $this->accountManager->nama;
        } elseif ($this->isWitel() && $this->witel) {
            return "Witel " . $this->witel->nama;
        }

        return $this->name;
    }

    // Profile image URL with fallback
    public function getProfileImageUrl(): string
    {
        if (empty($this->profile_image) || !file_exists(storage_path('app/public/' . $this->profile_image))) {
            return asset('img/default-profile.png');
        }

        return asset('storage/' . $this->profile_image);
    }
}

