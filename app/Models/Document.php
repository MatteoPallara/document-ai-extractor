<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_name',
        'path',
        'mime_type',
        'size_in_kb',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'original_name' => 'string',
            'path' => 'string',
            'mime_type' => 'string',
            'size_in_kb' => 'integer',
            'content' => 'string',
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

    public function hasOcrCache(): bool
    {
        return !is_null($this->content) && $this->content !== '';
    }
}