<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

class NcrEmbedding extends Model
{
    use HasNeighbors;

    protected $fillable = [
        'ncr_id',
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => Vector::class,
        ];
    }

    public function ncr(): BelongsTo
    {
        return $this->belongsTo(Ncr::class);
    }
}
