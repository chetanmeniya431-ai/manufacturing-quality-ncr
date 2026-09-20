<?php

namespace App\Services\Signals;

use App\Models\Ncr;
use App\Models\Signal;
use App\Models\Supplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Evaluates a signal's condition and returns the set of entities currently
 * matching it. Each match is a shape the SignalsCheckCommand can use both to
 * open new signal_events and to auto-resolve ones that no longer match:
 * ['ncr_id' => ?int, 'supplier_id' => ?int, 'context' => ?array]
 */
class SignalEvaluator
{
    /**
     * @return Collection<int, array{ncr_id: ?int, supplier_id: ?int, context: ?array}>
     */
    public function evaluate(Signal $signal): Collection
    {
        return match ($signal->condition_key) {
            'ncr_investigation_overdue' => $this->ncrInvestigationOverdue($signal),
            'corrective_action_overdue' => $this->correctiveActionOverdue($signal),
            'repeat_defect_category' => $this->repeatDefectCategory($signal),
            'repeat_defect_product' => $this->repeatDefectProduct($signal),
            'supplier_score_critical' => $this->supplierScoreCritical($signal),
            'critical_ncr_no_action' => $this->criticalNcrNoAction($signal),
            'supplier_repeat_failure' => $this->supplierRepeatFailure($signal),
            'ncr_verification_overdue' => $this->ncrVerificationOverdue($signal),
            'customer_return_ncr' => $this->customerReturnNcr(),
            'monthly_ncr_spike' => $this->monthlyNcrSpike($signal),
            default => collect(),
        };
    }

    protected function match(?int $ncrId = null, ?int $supplierId = null, ?array $context = null): array
    {
        return ['ncr_id' => $ncrId, 'supplier_id' => $supplierId, 'context' => $context];
    }

    /**
     * A stable dedup key for a match (or an existing signal_event's stored
     * values), ignoring volatile counters in context so a fluctuating count
     * doesn't churn events open/closed every run.
     */
    public static function dedupeKey(?int $ncrId, ?int $supplierId, ?array $context): string
    {
        if ($ncrId !== null) {
            return "ncr:{$ncrId}";
        }

        if ($supplierId !== null) {
            return "supplier:{$supplierId}";
        }

        if ($context !== null) {
            $stable = array_intersect_key($context, array_flip(['category', 'product', 'month']));
            ksort($stable);

            return 'context:'.json_encode($stable);
        }

        return 'none';
    }

    protected function ncrInvestigationOverdue(Signal $signal): Collection
    {
        $days = $signal->window_days ?? 3;

        return Ncr::where('status', 'open')
            ->where('created_at', '<=', Carbon::now()->subDays($days))
            ->get(['id'])
            ->map(fn (Ncr $ncr) => $this->match(ncrId: $ncr->id));
    }

    protected function correctiveActionOverdue(Signal $signal): Collection
    {
        $days = $signal->window_days ?? 14;

        return Ncr::where('status', 'corrective_assigned')
            ->where('corrective_action_assigned_at', '<=', Carbon::now()->subDays($days))
            ->get(['id'])
            ->map(fn (Ncr $ncr) => $this->match(ncrId: $ncr->id));
    }

    protected function repeatDefectCategory(Signal $signal): Collection
    {
        $windowDays = $signal->window_days ?? 30;
        $threshold = $signal->threshold ?? 5;
        $since = Carbon::now()->subDays($windowDays);

        return Ncr::where('created_at', '>=', $since)
            ->selectRaw('defect_category, count(*) as total')
            ->groupBy('defect_category')
            ->havingRaw('count(*) >= ?', [$threshold])
            ->get()
            ->map(fn ($row) => $this->match(context: ['category' => $row->defect_category, 'count' => (int) $row->total]));
    }

    protected function repeatDefectProduct(Signal $signal): Collection
    {
        $windowDays = $signal->window_days ?? 30;
        $threshold = $signal->threshold ?? 3;
        $since = Carbon::now()->subDays($windowDays);

        return Ncr::where('created_at', '>=', $since)
            ->selectRaw('product_name, count(*) as total')
            ->groupBy('product_name')
            ->havingRaw('count(*) >= ?', [$threshold])
            ->get()
            ->map(fn ($row) => $this->match(context: ['product' => $row->product_name, 'count' => (int) $row->total]));
    }

    protected function supplierScoreCritical(Signal $signal): Collection
    {
        $cutoff = $signal->threshold ?? 60;

        return Supplier::all()
            ->filter(fn (Supplier $supplier) => $supplier->qualityScore() < $cutoff)
            ->map(fn (Supplier $supplier) => $this->match(supplierId: $supplier->id))
            ->values();
    }

    protected function criticalNcrNoAction(Signal $signal): Collection
    {
        $hours = $signal->window_days ?? 24;

        return Ncr::where('severity', 'critical')
            ->where('status', 'open')
            ->where('created_at', '<=', Carbon::now()->subHours($hours))
            ->get(['id'])
            ->map(fn (Ncr $ncr) => $this->match(ncrId: $ncr->id));
    }

    protected function supplierRepeatFailure(Signal $signal): Collection
    {
        $windowDays = $signal->window_days ?? 60;
        $threshold = $signal->threshold ?? 3;
        $since = Carbon::now()->subDays($windowDays);

        return Ncr::whereNotNull('supplier_id')
            ->where('created_at', '>=', $since)
            ->selectRaw('supplier_id, count(*) as total')
            ->groupBy('supplier_id')
            ->havingRaw('count(*) >= ?', [$threshold])
            ->get()
            ->map(fn ($row) => $this->match(supplierId: $row->supplier_id));
    }

    protected function ncrVerificationOverdue(Signal $signal): Collection
    {
        $days = $signal->window_days ?? 7;

        return Ncr::where('status', 'verification')
            ->where('verification_started_at', '<=', Carbon::now()->subDays($days))
            ->get(['id'])
            ->map(fn (Ncr $ncr) => $this->match(ncrId: $ncr->id));
    }

    protected function customerReturnNcr(): Collection
    {
        return Ncr::where('detected_at', 'customer_return')
            ->where('status', '!=', 'closed')
            ->get(['id'])
            ->map(fn (Ncr $ncr) => $this->match(ncrId: $ncr->id));
    }

    protected function monthlyNcrSpike(Signal $signal): Collection
    {
        $percentThreshold = $signal->threshold ?? 50;

        $thisMonthStart = Carbon::now()->startOfMonth();
        $lastMonthStart = Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = $thisMonthStart->copy()->subSecond();

        $thisMonthCount = Ncr::where('created_at', '>=', $thisMonthStart)->count();
        $lastMonthCount = Ncr::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();

        if ($lastMonthCount > 0 && $thisMonthCount > $lastMonthCount * (1 + $percentThreshold / 100)) {
            return collect([$this->match(context: [
                'month' => $thisMonthStart->format('Y-m'),
                'this_month' => $thisMonthCount,
                'last_month' => $lastMonthCount,
            ])]);
        }

        return collect();
    }
}
