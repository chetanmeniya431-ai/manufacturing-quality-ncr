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

    /**
     * True once a pending question has been waiting long enough that it's
     * very unlikely to still be a normal in-progress request — covers cases
     * where the queue worker isn't running, or Ollama is unreachable, that
     * the job's own retry/backoff cycle hasn't surfaced as "failed" yet.
     * Without this, the UI shows unlabeled "Thinking..." forever with no
     * way out.
     */
    public function isStale(): bool
    {
        return $this->status === 'pending' && $this->created_at->lt(now()->subMinutes(3));
    }
}
