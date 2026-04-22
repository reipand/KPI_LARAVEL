<?php

namespace Database\Seeders;

use App\Models\KpiTemplate;
use App\Models\KpiTemplateIndicator;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Seeds demo KPI templates for the default BASS tenant.
 */
class KpiTemplateDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::withoutGlobalScopes()->where('tenant_code', 'BASS-001')->first();

        if (! $tenant) {
            $this->command->warn('BASS-001 tenant not found, skipping demo templates.');
            return;
        }

        $templates = [
            [
                'template_name' => 'Marketing & Sales Monthly KPI',
                'period_type'   => 'monthly',
                'indicators'    => [
                    [
                        'indicator_name' => 'Sales Revenue',
                        'weight'         => 70.00,
                        'target_type'    => 'number',
                        'target_value'   => 3500000000,
                        'scoring_method' => 'higher_is_better',
                        'max_cap'        => 120.00,
                        'notes'          => 'Monthly revenue target in IDR',
                    ],
                    [
                        'indicator_name' => 'Customer Satisfaction Index',
                        'weight'         => 20.00,
                        'target_type'    => 'rating',
                        'target_value'   => 3.5,
                        'scoring_method' => 'higher_is_better',
                        'max_cap'        => 110.00,
                        'notes'          => 'Scale 1–5, manual score input',
                    ],
                    [
                        'indicator_name' => 'Lead Time Proposal (days)',
                        'weight'         => 10.00,
                        'target_type'    => 'number',
                        'target_value'   => 1,
                        'scoring_method' => 'lower_is_better',
                        'max_cap'        => 120.00,
                        'notes'          => 'Max 1 business day after request',
                    ],
                ],
            ],
            [
                'template_name' => 'Finance Monthly KPI',
                'period_type'   => 'monthly',
                'indicators'    => [
                    [
                        'indicator_name' => 'Invoice Accuracy Rate (%)',
                        'weight'         => 60.00,
                        'target_type'    => 'percent',
                        'target_value'   => 100,
                        'scoring_method' => 'higher_is_better',
                        'max_cap'        => 100.00,
                    ],
                    [
                        'indicator_name' => 'Zero Document Errors',
                        'weight'         => 40.00,
                        'target_type'    => 'boolean',
                        'target_value'   => 0,
                        'scoring_method' => 'exact_match',
                        'max_cap'        => 100.00,
                        'notes'          => 'Number of errors; target 0',
                    ],
                ],
            ],
            [
                'template_name' => 'HR & GA Quarterly KPI',
                'period_type'   => 'quarterly',
                'indicators'    => [
                    [
                        'indicator_name' => 'Employee Satisfaction Score',
                        'weight'         => 40.00,
                        'target_type'    => 'rating',
                        'target_value'   => 4.0,
                        'scoring_method' => 'higher_is_better',
                        'max_cap'        => 115.00,
                    ],
                    [
                        'indicator_name' => 'Recruitment TAT (days)',
                        'weight'         => 30.00,
                        'target_type'    => 'number',
                        'target_value'   => 30,
                        'scoring_method' => 'lower_is_better',
                        'max_cap'        => 120.00,
                    ],
                    [
                        'indicator_name' => 'Training Completion Rate (%)',
                        'weight'         => 30.00,
                        'target_type'    => 'percent',
                        'target_value'   => 90,
                        'scoring_method' => 'higher_is_better',
                        'max_cap'        => 110.00,
                    ],
                ],
            ],
        ];

        foreach ($templates as $idx => $tpl) {
            $template = KpiTemplate::updateOrCreate(
                [
                    'tenant_id'     => $tenant->id,
                    'template_name' => $tpl['template_name'],
                ],
                [
                    'tenant_id'   => $tenant->id,
                    'period_type' => $tpl['period_type'],
                    'is_active'   => true,
                ]
            );

            foreach ($tpl['indicators'] as $order => $ind) {
                KpiTemplateIndicator::updateOrCreate(
                    [
                        'kpi_template_id' => $template->id,
                        'indicator_name'  => $ind['indicator_name'],
                    ],
                    [
                        'tenant_id'      => $tenant->id,
                        'weight'         => $ind['weight'],
                        'target_type'    => $ind['target_type'],
                        'target_value'   => $ind['target_value'],
                        'scoring_method' => $ind['scoring_method'],
                        'max_cap'        => $ind['max_cap'] ?? 120.00,
                        'notes'          => $ind['notes'] ?? null,
                        'sort_order'     => $order,
                    ]
                );
            }

            $this->command->info("Seeded template: {$tpl['template_name']}");
        }
    }
}
