<?php

namespace App\Models;

use ESolution\DBEncryption\Traits\EncryptedAttribute;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use EncryptedAttribute, HasFactory, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'ollama_endpoint',
        'last_login',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $encryptable = [
        'name',
        'email',
    ];

    protected static $roles = [
        'admin' => 'Amministratore',
        'user' => 'Utente',
    ];

    protected static $statuses = [
        1 => 'Attivo',
        0 => 'Disattivo',
    ];

    public static function getRoles(): array
    {
        return self::$roles;
    }

    public static function getStatuses(): array
    {
        return self::$statuses;
    }

    public function getRoleName(): string
    {
        return self::$roles[$this->getRoleNames()->first()];
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function vectors()
    {
        return $this->hasMany(Vector::class);
    }

    public function results()
    {
        return $this->hasManyThrough(Result::class, Document::class);
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function () {
        });

        self::created(function () {
        });

        self::updating(function () {
        });

        self::updated(function () {
        });

        self::deleting(function () {
        });

        self::deleted(function () {
        });
    }

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'email' => 'string',
            'password' => 'hashed',
            'role' => 'string',
            'ollama_endpoint' => 'string',
            'last_login' => 'datetime',
            'status' => 'boolean',
        ];
    }
}