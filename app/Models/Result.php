<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'vector_id',
        'data',
        'ai_model',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'document_id' => 'integer',
            'vector_id' => 'integer',
            'data' => 'array',
            'ai_model' => 'string',
            'status' => 'string',
        ];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function vector()
    {
        return $this->belongsTo(Vector::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isProcessing(): bool
    {
        return in_array($this->status, ['processing_ocr', 'processing_llm']);
    }
}