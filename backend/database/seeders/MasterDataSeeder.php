<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\EvaluationCriteria;
use App\Models\Requirement;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $documentTypes = [
            [
                'code' => 'LEGALITY_DOCUMENT',
                'name' => 'Dokumen Legalitas Organisasi',
                'scope' => 'organization',
                'is_active' => true,
            ],
            [
                'code' => 'PROPOSAL_DOCUMENT',
                'name' => 'Dokumen Proposal',
                'scope' => 'proposal',
                'is_active' => true,
            ],
            [
                'code' => 'BUDGET_DOCUMENT',
                'name' => 'Dokumen Rencana Anggaran Biaya',
                'scope' => 'proposal',
                'is_active' => true,
            ],
            [
                'code' => 'LPJ_DOCUMENT',
                'name' => 'Dokumen Laporan Pertanggungjawaban',
                'scope' => 'lpj',
                'is_active' => true,
            ],
        ];

        foreach ($documentTypes as $documentType) {
            DocumentType::updateOrCreate(
                ['code' => $documentType['code']],
                $documentType
            );
        }

        $requirements = [
            [
                'code' => 'ORG_LEGALITY',
                'name' => 'Legalitas Organisasi',
                'scope' => 'organization',
                'is_mandatory' => true,
                'is_active' => true,
            ],
            [
                'code' => 'PROPOSAL_NARRATIVE',
                'name' => 'Narasi Proposal',
                'scope' => 'proposal',
                'is_mandatory' => true,
                'is_active' => true,
            ],
            [
                'code' => 'PROPOSAL_BUDGET',
                'name' => 'Rencana Anggaran Biaya',
                'scope' => 'proposal',
                'is_mandatory' => true,
                'is_active' => true,
            ],
        ];

        foreach ($requirements as $requirement) {
            Requirement::updateOrCreate(
                ['code' => $requirement['code']],
                $requirement
            );
        }

        $criteria = [
            [
                'code' => 'PROGRAM_RELEVANCE',
                'name' => 'Relevansi dengan Program',
                'description' => 'Kesesuaian proposal dengan tujuan program.',
                'default_weight' => 25,
                'minimum_score' => 0,
                'maximum_score' => 100,
                'is_active' => true,
            ],
            [
                'code' => 'ORGANIZATION_CAPACITY',
                'name' => 'Kapasitas Organisasi',
                'description' => 'Kemampuan organisasi melaksanakan kegiatan.',
                'default_weight' => 25,
                'minimum_score' => 0,
                'maximum_score' => 100,
                'is_active' => true,
            ],
            [
                'code' => 'ACTIVITY_FEASIBILITY',
                'name' => 'Kelayakan Kegiatan',
                'description' => 'Kelayakan rencana pelaksanaan kegiatan.',
                'default_weight' => 25,
                'minimum_score' => 0,
                'maximum_score' => 100,
                'is_active' => true,
            ],
            [
                'code' => 'BUDGET_REASONABLENESS',
                'name' => 'Kewajaran Anggaran',
                'description' => 'Kewajaran dan efisiensi anggaran proposal.',
                'default_weight' => 25,
                'minimum_score' => 0,
                'maximum_score' => 100,
                'is_active' => true,
            ],
        ];

        foreach ($criteria as $criterion) {
            EvaluationCriteria::updateOrCreate(
                ['code' => $criterion['code']],
                $criterion
            );
        }

        $this->command?->info('Master data berhasil dibuat.');
        $this->command?->warn(
            'Data master ini adalah data awal development dan perlu dikonfirmasi dengan business rule resmi.'
        );
    }
}