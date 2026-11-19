<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vector extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'format',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'name' => 'string',
            'format' => 'string',
            'data' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }

    public function getSchemaKeys(): array
    {
        return array_keys($this->data ?? []);
    }

    public function getSchema(): array
    {
        return $this->data ?? [];
    }
}