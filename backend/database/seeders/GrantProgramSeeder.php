<?php

namespace Database\Seeders;

use App\Enums\QrType;
use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use App\Models\EvaluationCriteria;
use App\Models\EvaluationWeightConfiguration;
use App\Models\GrantProgram;
use App\Models\PolicyVersion;
use App\Models\RankingRuleConfiguration;
use App\Models\Requirement;
use App\Models\User;
use App\Services\QrService;
use Illuminate\Database\Seeder;

class GrantProgramSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@sikomando.test')->first();
        $policyVersion = PolicyVersion::first();
        $qrService = app(QrService::class);

        $docTypeProposal = DocumentType::where('code', 'PROPOSAL_DOCUMENT')->first();
        $docTypeBudget = DocumentType::where('code', 'BUDGET_DOCUMENT')->first();
        $reqNarrative = Requirement::where('code', 'PROPOSAL_NARRATIVE')->first();
        $reqBudget = Requirement::where('code', 'PROPOSAL_BUDGET')->first();
        $criteria = EvaluationCriteria::all();

        $programsData = [
            [
                'code' => 'GP-SULUT-2026-01',
                'name' => 'Program Hibah Pemberdayaan Komunitas & Ormas 2026',
                'description' => 'Bantuan dana hibah untuk organisasi kemasyarakatan, kepemudaan, dan pemberdayaan sosial di Provinsi Sulawesi Utara.',
                'fiscal_year' => '2026',
                'status' => 'active',
                'registration_start_at' => now()->startOfYear()->addDays(14),
                'registration_end_at' => now()->endOfYear()->subDays(30),
                'minimum_amount' => 25000000,
                'maximum_amount' => 200000000,
                'total_budget' => 15000000000,
                'is_active' => true,
            ],
            [
                'code' => 'GP-SULUT-2026-02',
                'name' => 'Program Hibah Sarana Prasarana Keagamaan & Pendidikan 2026',
                'description' => 'Hibah fasilitasi perbaikan sarana ibadah dan penunjang operasional pendidikan keagamaan swasta di Sulawesi Utara.',
                'fiscal_year' => '2026',
                'status' => 'active',
                'registration_start_at' => now()->startOfYear()->addDays(20),
                'registration_end_at' => now()->endOfYear()->subDays(15),
                'minimum_amount' => 50000000,
                'maximum_amount' => 500000000,
                'total_budget' => 25000000000,
                'is_active' => true,
            ],
            [
                'code' => 'GP-SULUT-2025-01',
                'name' => 'Program Hibah Pelestarian Seni Budaya Daerah 2025',
                'description' => 'Hibah pelestarian seni tradisional musik kolintang dan kearifan lokal Minahasa, Bolaang Mongondow, Sangihe, Talaud.',
                'fiscal_year' => '2025',
                'status' => 'finalized',
                'registration_start_at' => now()->subYear()->startOfYear()->addDays(10),
                'registration_end_at' => now()->subYear()->endOfYear()->subDays(90),
                'minimum_amount' => 20000000,
                'maximum_amount' => 150000000,
                'total_budget' => 10000000000,
                'is_active' => true,
            ],
        ];

        foreach ($programsData as $pData) {
            $program = GrantProgram::updateOrCreate(
                ['code' => $pData['code']],
                array_merge($pData, [
                    'created_by' => $admin?->id,
                    'updated_by' => $admin?->id,
                ])
            );

            // Generate QR for Program
            try {
                $qrService->generateFor($program, QrType::GRANT_PROGRAM, $admin, null, [
                    'program_name' => $program->name,
                    'fiscal_year' => $program->fiscal_year,
                ]);
            } catch (\Throwable $e) {
                // Ignore duplicate
            }

            // 1. Document Requirements
            if ($reqNarrative && $docTypeProposal) {
                DocumentRequirement::updateOrCreate(
                    [
                        'grant_program_id' => $program->id,
                        'requirement_id' => $reqNarrative->id,
                    ],
                    [
                        'document_type_id' => $docTypeProposal->id,
                        'scope' => 'proposal',
                        'is_mandatory' => true,
                        'maximum_files' => 2,
                        'validation_rules' => ['mimes' => ['pdf'], 'max' => 10240],
                        'sort_order' => 1,
                        'is_active' => true,
                    ]
                );
            }

            if ($reqBudget && $docTypeBudget) {
                DocumentRequirement::updateOrCreate(
                    [
                        'grant_program_id' => $program->id,
                        'requirement_id' => $reqBudget->id,
                    ],
                    [
                        'document_type_id' => $docTypeBudget->id,
                        'scope' => 'proposal',
                        'is_mandatory' => true,
                        'maximum_files' => 1,
                        'validation_rules' => ['mimes' => ['pdf', 'xlsx'], 'max' => 5120],
                        'sort_order' => 2,
                        'is_active' => true,
                    ]
                );
            }

            // 2. Evaluation Weight Configurations
            foreach ($criteria as $index => $crit) {
                EvaluationWeightConfiguration::updateOrCreate(
                    [
                        'grant_program_id' => $program->id,
                        'evaluation_criteria_id' => $crit->id,
                        'policy_version_id' => $policyVersion?->id,
                    ],
                    [
                        'weight' => 25.0000,
                        'minimum_score' => 60.00,
                        'maximum_score' => 100.00,
                        'sort_order' => $index + 1,
                        'is_active' => true,
                    ]
                );
            }

            // 3. Ranking Rule Configuration
            RankingRuleConfiguration::updateOrCreate(
                [
                    'grant_program_id' => $program->id,
                    'rule_code' => 'RANK-RULE-'.$program->code,
                ],
                [
                    'policy_version_id' => $policyVersion?->id,
                    'rule_name' => 'Aturan Pemeringkatan '.$program->name,
                    'rule_definition' => [
                        'weights' => [
                            'evaluation' => 0.40,
                            'survey' => 0.40,
                            'priority' => 0.20,
                        ],
                        'tie_breaker' => ['survey_score', 'evaluation_score', 'submitted_at'],
                    ],
                    'priority' => 1,
                    'status' => 'active',
                    'created_by' => $admin?->id,
                    'approved_by' => $admin?->id,
                    'approved_at' => now(),
                ]
            );
        }

        $this->command?->info('Grant programs, requirements, weights, and ranking rules seeded.');
    }
}

