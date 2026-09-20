<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantAnswer extends Model
{
    protected $fillable = [
        'user_id',
        'question',
        'answer',
        'sources',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sources' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
