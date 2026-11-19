<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ESolution\DBEncryption\Traits\EncryptedAttribute;

class ApiKey extends Model
{
    use HasFactory, EncryptedAttribute;

    protected $fillable = [
        'user_id',
        'name',
        'provider',
        'key',
    ];

    protected $encryptable = [
        'key',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'name' => 'string',
            'provider' => 'string',
            'key' => 'encrypted',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}