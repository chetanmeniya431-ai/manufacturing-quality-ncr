<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NcrStatusHistory extends Model
{
    protected $table = 'ncr_status_history';

    public $timestamps = false;

    protected $fillable = [
        'ncr_id',
        'old_status',
        'new_status',
        'changed_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function ncr(): BelongsTo
    {
        return $this->belongsTo(Ncr::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
