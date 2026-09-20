<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

class DocumentChunk extends Model
{
    use HasNeighbors;

    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'chunk_index',
        'chunk_text',
        'page_estimate',
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => Vector::class,
            'created_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(QualityDocument::class, 'document_id');
    }
}
