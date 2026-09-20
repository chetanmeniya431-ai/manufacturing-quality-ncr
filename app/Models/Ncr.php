<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ncr extends Model
{
    use HasFactory;

    public const STATUS_ORDER = [
        'open',
        'investigating',
        'corrective_assigned',
        'verification',
        'closed',
    ];

    public const STATUS_LABELS = [
        'open' => 'Open',
        'investigating' => 'Under Investigation',
        'corrective_assigned' => 'Corrective Action Assigned',
        'verification' => 'Verification',
        'closed' => 'Closed',
    ];

    public const SEVERITY_LABELS = [
        'minor' => 'Minor',
        'major' => 'Major',
        'critical' => 'Critical',
    ];

    public const DEFECT_CATEGORY_LABELS = [
        'dimension_out_of_spec' => 'Dimension out of spec',
        'surface_defect' => 'Surface defect',
        'material_non_conformance' => 'Material non-conformance',
        'process_deviation' => 'Process deviation',
        'supplier_defect' => 'Supplier defect',
        'documentation_error' => 'Documentation error',
    ];

    public const DETECTED_AT_LABELS = [
        'incoming' => 'Incoming inspection',
        'in_process' => 'In-process',
        'final' => 'Final inspection',
        'customer_return' => 'Customer return',
    ];

    protected $fillable = [
        'ncr_number',
        'product_name',
        'defect_category',
        'description',
        'severity',
        'detected_at',
        'detected_by',
        'detected_date',
        'supplier_id',
        'attachments',
        'status',
        'root_cause',
        'corrective_action',
        'corrective_action_assigned_to',
        'corrective_action_due',
        'verification_notes',
        'closure_notes',
        'closed_by',
        'closed_at',
        'ai_similar_ncrs',
        'ai_root_cause_suggestion',
        'ai_suggestion_status',
        'investigation_started_at',
        'corrective_action_assigned_at',
        'verification_started_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'ai_similar_ncrs' => 'array',
            'detected_date' => 'date',
            'corrective_action_due' => 'date',
            'closed_at' => 'datetime',
            'investigation_started_at' => 'datetime',
            'corrective_action_assigned_at' => 'datetime',
            'verification_started_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function detectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'detected_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function correctiveActionAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrective_action_assigned_to');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(NcrStatusHistory::class)->orderByDesc('created_at');
    }

    public function embedding(): HasOne
    {
        return $this->hasOne(NcrEmbedding::class);
    }

    public function nextStatus(): ?string
    {
        $index = array_search($this->status, self::STATUS_ORDER, true);

        return self::STATUS_ORDER[$index + 1] ?? null;
    }

    public function severityBadgeColor(): string
    {
        return match ($this->severity) {
            'critical' => 'red',
            'major' => 'orange',
            'minor' => 'yellow',
            default => 'gray',
        };
    }

    public function statusBadgeColor(): string
    {
        return match ($this->status) {
            'open' => 'gray',
            'investigating' => 'blue',
            'corrective_assigned' => 'amber',
            'verification' => 'purple',
            'closed' => 'green',
            default => 'gray',
        };
    }
}
