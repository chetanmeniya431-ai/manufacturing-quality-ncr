<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualityDocument extends Model
{
    public const TYPE_LABELS = [
        'quality_manual' => 'Quality Manual',
        'product_spec' => 'Product Specification',
        'work_instruction' => 'Work Instruction',
        'supplier_req' => 'Supplier Quality Requirements',
        'standard' => 'ISO Standard Summary',
    ];

    protected $fillable = [
        'name',
        'file_path',
        'original_filename',
        'document_type',
        'status',
        'error_message',
        'chunk_count',
        'embedded_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'embedded_at' => 'datetime',
        ];
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class, 'document_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
