<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_name',
        'contact_email',
        'product_categories',
        'approved',
    ];

    protected function casts(): array
    {
        return [
            'product_categories' => 'array',
            'approved' => 'boolean',
        ];
    }

    public function ncrs(): HasMany
    {
        return $this->hasMany(Ncr::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Quality score: starts at 100, deducts per NCR severity within the last
     * 90 days, and recovers 1 point per week since the supplier's most recent
     * NCR (rewarding a quiet run even before an old NCR's 90-day deduction
     * window fully expires).
     */
    public function qualityScore(): int
    {
        $windowStart = Carbon::now()->subDays(90);

        $ncrsInWindow = $this->ncrs()
            ->where('detected_date', '>=', $windowStart)
            ->get(['severity', 'detected_date']);

        $deductions = $ncrsInWindow->sum(fn (Ncr $ncr) => match ($ncr->severity) {
            'minor' => 5,
            'major' => 15,
            'critical' => 30,
            default => 0,
        });

        $lastNcrDate = $this->ncrs()->max('detected_date');
        $recovery = 0;

        if ($lastNcrDate) {
            $weeksSince = (int) floor(Carbon::parse($lastNcrDate)->diffInDays(Carbon::now()) / 7);
            $recovery = $weeksSince;
        } else {
            $recovery = $deductions;
        }

        $score = 100 - $deductions + $recovery;

        return max(0, min(100, $score));
    }

    public function scoreColor(): string
    {
        $score = $this->qualityScore();

        return match (true) {
            $score >= 80 => 'green',
            $score >= 60 => 'yellow',
            default => 'red',
        };
    }
}
