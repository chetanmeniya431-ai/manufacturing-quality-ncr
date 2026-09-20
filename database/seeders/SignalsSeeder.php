<?php

namespace Database\Seeders;

use App\Models\Signal;
use Illuminate\Database\Seeder;

class SignalsSeeder extends Seeder
{
    public function run(): void
    {
        $signals = [
            [
                'name' => 'NCR Investigation Overdue',
                'description' => 'Open NCR not moved to investigation within 3 days.',
                'condition_key' => 'ncr_investigation_overdue',
                'threshold' => null,
                'window_days' => 3,
                'severity' => 'high',
            ],
            [
                'name' => 'Corrective Action Overdue',
                'description' => 'NCR in "Corrective Action Assigned" status for 14+ days.',
                'condition_key' => 'corrective_action_overdue',
                'threshold' => null,
                'window_days' => 14,
                'severity' => 'high',
            ],
            [
                'name' => 'Repeat Defect — Same Category',
                'description' => 'Same defect category appears 5+ times in 30 days.',
                'condition_key' => 'repeat_defect_category',
                'threshold' => 5,
                'window_days' => 30,
                'severity' => 'high',
            ],
            [
                'name' => 'Repeat Defect — Same Product',
                'description' => 'Same product has 3+ NCRs in 30 days.',
                'condition_key' => 'repeat_defect_product',
                'threshold' => 3,
                'window_days' => 30,
                'severity' => 'medium',
            ],
            [
                'name' => 'Supplier Quality Score Critical',
                'description' => 'Supplier score drops below 60.',
                'condition_key' => 'supplier_score_critical',
                'threshold' => 60,
                'window_days' => 90,
                'severity' => 'high',
            ],
            [
                'name' => 'Critical NCR — No Immediate Action',
                'description' => 'Critical severity NCR open for 24+ hours without status change.',
                'condition_key' => 'critical_ncr_no_action',
                'threshold' => null,
                'window_days' => 24,
                'severity' => 'critical',
            ],
            [
                'name' => 'Supplier Repeat Failure',
                'description' => 'Same supplier causes 3+ NCRs in 60 days.',
                'condition_key' => 'supplier_repeat_failure',
                'threshold' => 3,
                'window_days' => 60,
                'severity' => 'high',
            ],
            [
                'name' => 'NCR Verification Overdue',
                'description' => 'NCR in "Verification" status for 7+ days.',
                'condition_key' => 'ncr_verification_overdue',
                'threshold' => null,
                'window_days' => 7,
                'severity' => 'medium',
            ],
            [
                'name' => 'Customer Return NCR',
                'description' => 'Any NCR with "Customer return" as detection point.',
                'condition_key' => 'customer_return_ncr',
                'threshold' => null,
                'window_days' => null,
                'severity' => 'high',
            ],
            [
                'name' => 'Monthly NCR Spike',
                'description' => "This month's NCR count exceeds last month's by 50%+.",
                'condition_key' => 'monthly_ncr_spike',
                'threshold' => 50,
                'window_days' => 30,
                'severity' => 'medium',
            ],
        ];

        foreach ($signals as $signal) {
            Signal::updateOrCreate(
                ['condition_key' => $signal['condition_key']],
                [...$signal, 'active' => true],
            );
        }
    }
}
