<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Signal extends Model
{
    protected $fillable = [
        'name',
        'description',
        'condition_key',
        'threshold',
        'window_days',
        'severity',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(SignalEvent::class);
    }

    public function openEvents(): HasMany
    {
        return $this->events()->whereNull('resolved_at');
    }

    public function severityBadgeColor(): string
    {
        return match ($this->severity) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'amber',
            'low' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Which numeric knobs this signal exposes for editing, with the label,
     * unit and sane bounds shown in the UI. The condition logic itself stays
     * fixed code (see SignalEvaluator) — this only lets an admin retune the
     * threshold/window a condition already reads from the database.
     *
     * @return array<string, array{label: string, unit: string, min: int, max: int}>
     */
    public function configurableFields(): array
    {
        return match ($this->condition_key) {
            'ncr_investigation_overdue', 'corrective_action_overdue', 'ncr_verification_overdue' => [
                'window_days' => ['label' => 'Days before overdue', 'unit' => 'days', 'min' => 1, 'max' => 365],
            ],
            'critical_ncr_no_action' => [
                'window_days' => ['label' => 'Hours before overdue', 'unit' => 'hours', 'min' => 1, 'max' => 168],
            ],
            'repeat_defect_category', 'repeat_defect_product', 'supplier_repeat_failure' => [
                'threshold' => ['label' => 'Occurrences', 'unit' => '', 'min' => 2, 'max' => 100],
                'window_days' => ['label' => 'Within days', 'unit' => 'days', 'min' => 1, 'max' => 365],
            ],
            'supplier_score_critical' => [
                'threshold' => ['label' => 'Score below', 'unit' => '', 'min' => 1, 'max' => 99],
            ],
            'monthly_ncr_spike' => [
                'threshold' => ['label' => 'Increase vs last month', 'unit' => '%', 'min' => 10, 'max' => 500],
            ],
            default => [],
        };
    }
}
