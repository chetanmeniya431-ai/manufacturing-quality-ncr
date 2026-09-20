<?php

namespace Database\Seeders;

use App\Models\Ncr;
use App\Models\NcrStatusHistory;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Ncr\NcrNumberGenerator;
use App\Services\Ncr\NcrSimilarityService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class NcrsSeeder extends Seeder
{
    protected array $rootCauses = [
        'dimension_out_of_spec' => 'Worn cutting tool insert allowed to run past its replacement interval, causing gradual dimensional drift on the finishing pass.',
        'surface_defect' => 'Coolant concentration was below the specified range, causing tool chatter and visible surface marks.',
        'supplier_defect' => 'Incoming batch from the supplier was outside the agreed material tolerance; supplier changed sub-tier source without notifying us.',
        'process_deviation' => 'Operator used an outdated work instruction revision that omitted an inspection hold point.',
        'material_non_conformance' => 'Material certificate did not match the actual hardness of the delivered batch; heat treatment furnace was mis-calibrated at source.',
        'documentation_error' => 'Traveler was completed using the previous drawing revision, which had since been superseded.',
    ];

    protected array $correctiveActions = [
        'dimension_out_of_spec' => 'Reduced tool change interval and added an in-process SPC check on the finishing pass.',
        'surface_defect' => 'Corrected coolant mixing procedure and added a daily concentration check to the shift checklist.',
        'supplier_defect' => 'Issued a SCAR to the supplier requiring notification of any sub-tier or process change, with re-qualification before further shipments accepted.',
        'process_deviation' => 'Reissued the current work instruction revision to all shifts and added a revision check to the pre-run checklist.',
        'material_non_conformance' => 'Required supplier to provide batch-level hardness test reports and added incoming hardness spot-checks.',
        'documentation_error' => 'Added a drawing revision check as a mandatory field on the traveler before work can start.',
    ];

    public function run(): void
    {
        $manager = User::role('quality_manager')->first();
        $inspector = User::role('quality_inspector')->first();
        $productionManager = User::role('production_manager')->first();
        $numberGenerator = app(NcrNumberGenerator::class);

        $steelcore = Supplier::where('name', 'Steelcore Components Ltd')->value('id');
        $primaRubber = Supplier::where('name', 'Prima Rubber Seals')->value('id');
        $apex = Supplier::where('name', 'Apex Fasteners Pvt Ltd')->value('id');
        $metalform = Supplier::where('name', 'Metalform Industries')->value('id');

        $p = [
            1 => 'Precision Shaft Type A',
            2 => 'Gearbox Housing Type B',
            3 => 'Flange Coupling Type C',
            4 => 'Bearing Sleeve Type D',
            5 => 'Drive Pinion Type E',
        ];

        // [product, category, severity, detected_at, status, created_days_ago, extra]
        $rows = [
            [$p[1], 'dimension_out_of_spec', 'major', 'final', 'closed', 120, []],
            [$p[2], 'surface_defect', 'minor', 'final', 'closed', 100, []],
            [$p[3], 'supplier_defect', 'minor', 'incoming', 'closed', 95, ['supplier_id' => $steelcore]],
            [$p[1], 'process_deviation', 'major', 'in_process', 'closed', 80, []],
            [$p[4], 'material_non_conformance', 'minor', 'incoming', 'closed', 70, []],
            [$p[2], 'dimension_out_of_spec', 'minor', 'final', 'closed', 60, []],
            [$p[5], 'surface_defect', 'major', 'final', 'closed', 55, []],
            [$p[3], 'supplier_defect', 'minor', 'incoming', 'closed', 50, ['supplier_id' => $primaRubber]],
            [$p[1], 'documentation_error', 'minor', 'final', 'closed', 45, []],
            [$p[4], 'dimension_out_of_spec', 'major', 'final', 'closed', 40, []],
            [$p[2], 'material_non_conformance', 'minor', 'incoming', 'closed', 38, []],
            [$p[5], 'process_deviation', 'minor', 'in_process', 'closed', 36, []],

            [$p[2], 'process_deviation', 'critical', 'final', 'open', 6, []],
            [$p[5], 'material_non_conformance', 'major', 'incoming', 'open', 5, []],
            [$p[1], 'surface_defect', 'minor', 'in_process', 'open', 1, []],

            [$p[3], 'dimension_out_of_spec', 'minor', 'final', 'verification', 10, ['verification_started_days_ago' => 8]],
            [$p[4], 'surface_defect', 'major', 'final', 'verification', 15, ['verification_started_days_ago' => 3]],

            [$p[2], 'dimension_out_of_spec', 'major', 'incoming', 'investigating', 20, ['investigation_started_days_ago' => 18]],
            [$p[1], 'dimension_out_of_spec', 'minor', 'final', 'investigating', 18, ['investigation_started_days_ago' => 16]],
            [$p[5], 'supplier_defect', 'critical', 'incoming', 'investigating', 14, ['investigation_started_days_ago' => 12, 'supplier_id' => $apex]],
            [$p[3], 'surface_defect', 'minor', 'in_process', 'investigating', 12, ['investigation_started_days_ago' => 10]],
            [$p[1], 'process_deviation', 'major', 'final', 'investigating', 9, ['investigation_started_days_ago' => 7]],

            [$p[4], 'dimension_out_of_spec', 'major', 'final', 'corrective_assigned', 25, ['corrective_action_assigned_days_ago' => 20]],
            [$p[1], 'dimension_out_of_spec', 'minor', 'customer_return', 'corrective_assigned', 22, ['corrective_action_assigned_days_ago' => 5]],
            [$p[2], 'supplier_defect', 'major', 'final', 'corrective_assigned', 20, ['corrective_action_assigned_days_ago' => 6, 'supplier_id' => $apex]],
            [$p[3], 'supplier_defect', 'major', 'incoming', 'corrective_assigned', 18, ['corrective_action_assigned_days_ago' => 4, 'supplier_id' => $apex]],
            [$p[4], 'supplier_defect', 'minor', 'incoming', 'corrective_assigned', 16, ['corrective_action_assigned_days_ago' => 2, 'supplier_id' => $metalform]],
            [$p[5], 'process_deviation', 'critical', 'final', 'corrective_assigned', 14, ['corrective_action_assigned_days_ago' => 1]],
            [$p[2], 'surface_defect', 'minor', 'in_process', 'corrective_assigned', 11, ['corrective_action_assigned_days_ago' => 3]],
            [$p[1], 'surface_defect', 'major', 'final', 'corrective_assigned', 8, ['corrective_action_assigned_days_ago' => 2]],
        ];

        foreach ($rows as $row) {
            [$product, $category, $severity, $detectedAt, $status, $createdDaysAgo, $extra] = $row;
            $this->createNcr(
                $numberGenerator, $manager, $inspector, $productionManager,
                $product, $category, $severity, $detectedAt, $status, $createdDaysAgo, $extra
            );
        }

        // Embed every seeded NCR exactly the way a live save does, so the
        // similar-NCR / AI root-cause feature works immediately after seeding.
        $similarity = app(NcrSimilarityService::class);
        foreach (Ncr::all() as $ncr) {
            $similarity->embed($ncr);
        }

        // Fire real signal_events from the real evaluator against this seeded
        // data, rather than hand-inserting fixture rows.
        Artisan::call('signals:check');
    }

    protected function createNcr(
        NcrNumberGenerator $numberGenerator,
        User $manager,
        User $inspector,
        User $productionManager,
        string $product,
        string $category,
        string $severity,
        string $detectedAt,
        string $status,
        int $createdDaysAgo,
        array $extra,
    ): void {
        $createdAt = Carbon::now()->subDays($createdDaysAgo);
        $detectedDate = $createdAt->copy();

        $ncr = Ncr::create([
            'ncr_number' => $numberGenerator->next(),
            'product_name' => $product,
            'defect_category' => $category,
            'description' => $this->describe($product, $category, $severity),
            'severity' => $severity,
            'detected_at' => $detectedAt,
            'detected_by' => $inspector->id,
            'detected_date' => $detectedDate->toDateString(),
            'supplier_id' => $extra['supplier_id'] ?? null,
            'status' => 'open',
            'created_by' => $inspector->id,
        ]);

        DB::table('ncrs')->where('id', $ncr->id)->update([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        NcrStatusHistory::create([
            'ncr_id' => $ncr->id,
            'old_status' => null,
            'new_status' => 'open',
            'changed_by' => $inspector->id,
            'note' => null,
        ]);
        DB::table('ncr_status_history')->where('ncr_id', $ncr->id)->where('new_status', 'open')
            ->update(['created_at' => $createdAt]);

        if ($status === 'open') {
            return;
        }

        $investigationAt = isset($extra['investigation_started_days_ago'])
            ? Carbon::now()->subDays($extra['investigation_started_days_ago'])
            : $createdAt->copy()->addDay();

        $this->advanceTo($ncr, $inspector, 'investigating', $investigationAt);

        if ($status === 'investigating') {
            return;
        }

        $correctiveAt = isset($extra['corrective_action_assigned_days_ago'])
            ? Carbon::now()->subDays($extra['corrective_action_assigned_days_ago'])
            : $investigationAt->copy()->addDays(3);

        $ncr->root_cause = $this->rootCauses[$category];
        $ncr->corrective_action = $this->correctiveActions[$category];
        $ncr->corrective_action_assigned_to = $productionManager->id;
        $ncr->corrective_action_due = $correctiveAt->copy()->addDays(14)->toDateString();
        $this->advanceTo($ncr, $manager, 'corrective_assigned', $correctiveAt);

        if ($status === 'corrective_assigned') {
            return;
        }

        $verificationAt = isset($extra['verification_started_days_ago'])
            ? Carbon::now()->subDays($extra['verification_started_days_ago'])
            : $correctiveAt->copy()->addDays(5);

        $this->advanceTo($ncr, $productionManager, 'verification', $verificationAt);

        if ($status === 'verification') {
            return;
        }

        $closedAt = $verificationAt->copy()->addDays(4);
        $ncr->verification_notes = 'Re-measured 10 consecutive parts after the corrective action; all within specification. Process capability confirmed stable over two shifts.';
        $ncr->closure_notes = 'Corrective action verified effective. No recurrence observed in the following production run.';
        $ncr->closed_by = $manager->id;
        $ncr->closed_at = $closedAt;
        $this->advanceTo($ncr, $manager, 'closed', $closedAt);
    }

    protected function advanceTo(Ncr $ncr, User $user, string $status, Carbon $at): void
    {
        $oldStatus = $ncr->status;
        $ncr->status = $status;

        match ($status) {
            'investigating' => $ncr->investigation_started_at = $at,
            'corrective_assigned' => $ncr->corrective_action_assigned_at = $at,
            'verification' => $ncr->verification_started_at = $at,
            default => null,
        };

        $ncr->save();

        DB::table('ncrs')->where('id', $ncr->id)->update(['updated_at' => $at]);

        $history = NcrStatusHistory::create([
            'ncr_id' => $ncr->id,
            'old_status' => $oldStatus,
            'new_status' => $status,
            'changed_by' => $user->id,
            'note' => null,
        ]);
        DB::table('ncr_status_history')->where('id', $history->id)->update(['created_at' => $at]);
    }

    protected function describe(string $product, string $category, string $severity): string
    {
        $templates = [
            'dimension_out_of_spec' => "{$product} measured outside the drawing tolerance during inspection. Deviation is consistent with tool wear on the finishing operation.",
            'surface_defect' => "Visible surface finish defect found on {$product} — tool marks and chatter inconsistent with the specified Ra requirement.",
            'supplier_defect' => "Incoming batch of material for {$product} does not conform to the agreed supplier specification.",
            'process_deviation' => "Production of {$product} deviated from the approved work instruction, skipping a required inspection hold point.",
            'material_non_conformance' => "Material used for {$product} does not match the certified hardness/composition for this batch.",
            'documentation_error' => "Traveler for {$product} referenced an outdated drawing revision, creating a documentation non-conformance.",
        ];

        $severityNote = match ($severity) {
            'critical' => ' This is a critical non-conformance requiring immediate containment.',
            'major' => ' This affects fit, form, or function and is classified as major.',
            default => ' Impact is isolated and classified as minor.',
        };

        return $templates[$category].$severityNote;
    }
}
