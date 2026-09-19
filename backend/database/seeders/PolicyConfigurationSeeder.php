<?php

namespace Database\Seeders;

use App\Models\DecisionTemplate;
use App\Models\DecisionTemplateVersion;
use App\Models\NumberingConfiguration;
use App\Models\PolicyConfiguration;
use App\Models\PolicyVersion;
use App\Models\User;
use Illuminate\Database\Seeder;

class PolicyConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@sikomando.test')->first();

        // 1. Policy Configurations & Version
        $policy = PolicyConfiguration::updateOrCreate(
            ['code' => 'POLICY_HIBAH_SULUT_2026'],
            [
                'name' => 'Kebijakan Tata Kelola Hibah Daerah Provinsi Sulawesi Utara TA 2026',
                'description' => 'Pedoman umum pelaksanaan, verifikasi, evaluasi, dan akuntabilitas belanja hibah berbasis QR dan TTE.',
                'scope' => 'global',
                'is_active' => true,
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ]
        );

        PolicyVersion::updateOrCreate(
            [
                'policy_configuration_id' => $policy->id,
                'version_number' => '1.0.0',
            ],
            [
                'configuration_data' => [
                    'general_rules' => [
                        'allow_multi_year' => false,
                        'require_npwp' => true,
                        'require_legality_ahu' => true,
                        'require_surat_keterangan_domisili' => true,
                    ],
                    'thresholds' => [
                        'max_grant_amount' => 500000000,
                        'survey_mandatory_above' => 50000000,
                        'two_phase_disbursement_above' => 100000000,
                    ],
                    'verification' => [
                        'max_revision_attempts' => 3,
                        'revision_deadline_days' => 7,
                    ],
                ],
                'status' => 'approved',
                'effective_from' => now()->startOfYear(),
                'effective_until' => now()->endOfYear(),
                'created_by' => $admin?->id,
                'approved_by' => $admin?->id,
                'approved_at' => now(),
                'approval_notes' => 'Disahkan sesuai Peraturan Gubernur Sulawesi Utara tentang Tata Cara Penganggaran, Pelaksanaan, dan Pertanggungjawaban Belanja Hibah.',
            ]
        );

        // 2. Numbering Configurations
        $numberings = [
            [
                'document_type' => 'PROPOSAL',
                'prefix' => 'PROP-SULUT',
                'format_pattern' => '{PREFIX}/{YEAR}/{MONTH}/{SEQ}',
                'current_sequence' => 14,
                'status' => 'active',
            ],
            [
                'document_type' => 'DECISION_SK',
                'prefix' => 'SK-GUB-SULUT',
                'format_pattern' => '{PREFIX}/{YEAR}/{SEQ}',
                'current_sequence' => 4,
                'status' => 'active',
            ],
            [
                'document_type' => 'DISBURSEMENT_SP2D',
                'prefix' => 'SP2D-BKAD',
                'format_pattern' => '{PREFIX}/{YEAR}/{MONTH}/{SEQ}',
                'current_sequence' => 3,
                'status' => 'active',
            ],
            [
                'document_type' => 'RECEIPT',
                'prefix' => 'KW-HB',
                'format_pattern' => '{PREFIX}/{YEAR}/{SEQ}',
                'current_sequence' => 5,
                'status' => 'active',
            ],
            [
                'document_type' => 'LPJ',
                'prefix' => 'LPJ-HB',
                'format_pattern' => '{PREFIX}/{YEAR}/{SEQ}',
                'current_sequence' => 2,
                'status' => 'active',
            ],
        ];

        foreach ($numberings as $num) {
            NumberingConfiguration::updateOrCreate(
                [
                    'document_type' => $num['document_type'],
                    'grant_program_id' => null,
                ],
                array_merge($num, [
                    'reset_period' => 1,
                    'created_by' => $admin?->id,
                    'updated_by' => $admin?->id,
                ])
            );
        }

        // 3. Decision Template & Version
        $template = DecisionTemplate::updateOrCreate(
            ['code' => 'SK_PENETAPAN_HIBAH_STANDAR'],
            [
                'name' => 'Template Surat Keputusan Penetapan Hibah Daerah',
                'document_type' => 'DECISION_SK',
                'description' => 'Format standar naskah dinas penetapan penerima dan alokasi dana hibah daerah.',
                'is_active' => true,
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ]
        );

        DecisionTemplateVersion::updateOrCreate(
            [
                'decision_template_id' => $template->id,
                'version_number' => '1.0',
            ],
            [
                'template_content' => "KEPUTUSAN GUBERNUR SULAWESI UTARA\nNOMOR: {DECISION_NUMBER}\nTENTANG\nPENETAPAN ORGANISASI PENERIMA DAN BESARAN HIBAH DAERAH TAHUN ANGGARAN {FISCAL_YEAR}\n\nMenimbang: bahwa untuk menunjang pencapaian sasaran program daerah...\nMengingat: ...\nMEMUTUSKAN:\nMenetapkan Penerima Hibah sebagaimana terlampir.",
                'status' => 'approved',
                'effective_from' => now()->startOfYear(),
                'created_by' => $admin?->id,
                'approved_by' => $admin?->id,
                'approved_at' => now(),
            ]
        );

        $this->command?->info('Policy, numbering, and decision templates seeded.');
    }
}

