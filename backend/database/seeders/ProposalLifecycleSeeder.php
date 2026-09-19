<?php

namespace Database\Seeders;

use App\Enums\ProposalStatus;
use App\Enums\QrType;
use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use App\Models\GrantProgram;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\ProposalAssignment;
use App\Models\ProposalBudgetItem;
use App\Models\ProposalDocument;
use App\Models\ProposalDocumentVersion;
use App\Models\ProposalStatusHistory;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\QrService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProposalLifecycleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@sikomando.test')->first();
        $verifikator = User::where('email', 'verifikator@sikomando.test')->first();
        $evaluator = User::where('email', 'evaluator@sikomando.test')->first();
        $surveyor = User::where('email', 'surveyor@sikomando.test')->first();

        $pemohon1 = User::where('email', 'pemohon@sikomando.test')->first();
        $pemohon2 = User::where('email', 'pemohon2@sikomando.test')->first();
        $pemohon3 = User::where('email', 'pemohon3@sikomando.test')->first();

        $org1 = Organization::where('code', 'ORG-YPB-001')->first();
        $org2 = Organization::where('code', 'ORG-LPMP-002')->first();
        $org3 = Organization::where('code', 'ORG-SSB-003')->first();
        $org4 = Organization::where('code', 'ORG-YAI-004')->first();

        $prog1 = GrantProgram::where('code', 'GP-SULUT-2026-01')->first();
        $prog2 = GrantProgram::where('code', 'GP-SULUT-2026-02')->first();
        $prog3 = GrantProgram::where('code', 'GP-SULUT-2025-01')->first();

        $docTypeProposal = DocumentType::where('code', 'PROPOSAL_DOCUMENT')->first();
        $docTypeBudget = DocumentType::where('code', 'BUDGET_DOCUMENT')->first();
        $qrService = app(QrService::class);

        $proposalsData = [
            [
                'number' => 'PROP-SULUT/2026/01/0001',
                'program' => $prog1,
                'org' => $org1,
                'applicant' => $pemohon1,
                'title' => 'Pengembangan Pusat Pelatihan Digital & Vokasi Pemuda Wenang Manado',
                'status' => ProposalStatus::DRAFT,
                'requested' => 75000000,
                'approved' => null,
            ],
            [
                'number' => 'PROP-SULUT/2026/01/0002',
                'program' => $prog1,
                'org' => $org2,
                'applicant' => $pemohon2,
                'title' => 'Program Edukasi Konservasi Terumbu Karang & Bank Sampah Pesisir Malalayang',
                'status' => ProposalStatus::SUBMITTED,
                'requested' => 60000000,
                'approved' => null,
            ],
            [
                'number' => 'PROP-SULUT/2026/01/0003',
                'program' => $prog1,
                'org' => $org3,
                'applicant' => $pemohon3,
                'title' => 'Fasilitasi Workshop Kolintang Generasi Muda dan Pagelaran Budaya Minahasa',
                'status' => ProposalStatus::VERIFICATION,
                'requested' => 85000000,
                'approved' => null,
            ],
            [
                'number' => 'PROP-SULUT/2026/01/0004',
                'program' => $prog1,
                'org' => $org1,
                'applicant' => $pemohon1,
                'title' => 'Pemberdayaan UMKM Kreatif Kerajinan Bambu Batik Minahasa di Kota Manado',
                'status' => ProposalStatus::REVISION,
                'requested' => 90000000,
                'approved' => null,
            ],
            [
                'number' => 'PROP-SULUT/2026/01/0005',
                'program' => $prog1,
                'org' => $org2,
                'applicant' => $pemohon2,
                'title' => 'Pengadaan Alat Tangkap Ikan Ramah Lingkungan & Solar Freezer Nelayan Tradisional',
                'status' => ProposalStatus::VERIFIED,
                'requested' => 120000000,
                'approved' => null,
            ],
            [
                'number' => 'PROP-SULUT/2026/02/0006',
                'program' => $prog2,
                'org' => $org4,
                'applicant' => $pemohon1,
                'title' => 'Renovasi Sarana Asrama dan Laboratorium Komputer Bina Insan Harapan Manado',
                'status' => ProposalStatus::EVALUATION,
                'requested' => 150000000,
                'approved' => null,
            ],
            [
                'number' => 'PROP-SULUT/2026/02/0007',
                'program' => $prog2,
                'org' => $org4,
                'applicant' => $pemohon1,
                'title' => 'Pengadaan Fasilitas MCK Sehat dan Air Bersih Sanitasi Pendidikan Keagamaan',
                'status' => ProposalStatus::SURVEY,
                'requested' => 95000000,
                'approved' => null,
            ],
            [
                'number' => 'PROP-SULUT/2026/01/0008',
                'program' => $prog1,
                'org' => $org3,
                'applicant' => $pemohon3,
                'title' => 'Pelatihan Pembuatan Alat Musik Tradisional Kayu Cempaka dan Sanggar Seni Budaya',
                'status' => ProposalStatus::RECOMMENDED,
                'requested' => 110000000,
                'approved' => 100000000,
            ],
            [
                'number' => 'PROP-SULUT/2026/01/0009',
                'program' => $prog1,
                'org' => $org2,
                'applicant' => $pemohon2,
                'title' => 'Pusat Daur Ulang Plastik Mandiri Komunitas Pesisir Bunaken Indah',
                'status' => ProposalStatus::APPROVAL,
                'requested' => 140000000,
                'approved' => 125000000,
            ],
            [
                'number' => 'PROP-SULUT/2026/01/0010',
                'program' => $prog1,
                'org' => $org1,
                'applicant' => $pemohon1,
                'title' => 'Akselerasi Kewirausahaan Digital Pemuda Berbasis Komunitas Sulawesi Utara',
                'status' => ProposalStatus::APPROVED,
                'requested' => 180000000,
                'approved' => 160000000,
            ],
            [
                'number' => 'PROP-SULUT/2026/02/0011',
                'program' => $prog2,
                'org' => $org4,
                'applicant' => $pemohon1,
                'title' => 'Pembangunan Aula Serbaguna Edukasi dan Literasi Karakter Generasi Muda',
                'status' => ProposalStatus::DISBURSED,
                'requested' => 250000000,
                'approved' => 220000000,
            ],
            [
                'number' => 'PROP-SULUT/2026/01/0012',
                'program' => $prog1,
                'org' => $org2,
                'applicant' => $pemohon2,
                'title' => 'Pengadaan Kapal Patroli Kayu Penjaga Terumbu Karang & Ekowisata Bahari',
                'status' => ProposalStatus::IMPLEMENTATION,
                'requested' => 175000000,
                'approved' => 165000000,
            ],
            [
                'number' => 'PROP-SULUT/2026/01/0013',
                'program' => $prog1,
                'org' => $org3,
                'applicant' => $pemohon3,
                'title' => 'Revitalisasi Panggung Budaya Terbuka dan Studio Latihan Seni Musik Kolintang',
                'status' => ProposalStatus::LPJ_SUBMITTED,
                'requested' => 130000000,
                'approved' => 120000000,
            ],
            [
                'number' => 'PROP-SULUT/2025/01/0014',
                'program' => $prog3,
                'org' => $org3,
                'applicant' => $pemohon3,
                'title' => 'Festival Budaya Tradisi Baku Bekel dan Pameran Pusaka Sulawesi Utara 2025',
                'status' => ProposalStatus::COMPLETED,
                'requested' => 100000000,
                'approved' => 100000000,
            ],
        ];

        foreach ($proposalsData as $idx => $p) {
            $proposal = Proposal::updateOrCreate(
                ['proposal_number' => $p['number']],
                [
                    'grant_program_id' => $p['program']->id,
                    'organization_id' => $p['org']->id,
                    'applicant_id' => $p['applicant']->id,
                    'title' => $p['title'],
                    'background' => 'Kebutuhan mendesak penguatan kapasitas masyarakat Sulawesi Utara melalui program '.$p['title'].'.',
                    'objectives' => 'Mewujudkan kemandirian, akuntabilitas, dan kesejahteraan sosial masyarakat.',
                    'benefits' => 'Peningkatan kapasitas 500+ anggota organisasi dan masyarakat sekitar.',
                    'activities' => 'Pelatihan, pengadaan sarana fisik, pendampingan, dan pelaporan pertanggungjawaban.',
                    'expected_outputs' => 'Tersedianya sarana fisik dan terbentuknya jejaring komunitas mandiri.',
                    'requested_amount' => $p['requested'],
                    'approved_amount' => $p['approved'],
                    'status' => $p['status'],
                    'revision_count' => $p['status'] === ProposalStatus::REVISION ? 1 : 0,
                    'submitted_at' => $p['status'] !== ProposalStatus::DRAFT ? now()->subDays(30 - $idx) : null,
                    'verified_at' => in_array($p['status']->value, ['verified', 'evaluation', 'survey', 'recommended', 'approval', 'approved', 'disbursed', 'implementation', 'lpj_submitted', 'completed']) ? now()->subDays(25 - $idx) : null,
                    'approved_at' => in_array($p['status']->value, ['approved', 'disbursed', 'implementation', 'lpj_submitted', 'completed']) ? now()->subDays(15 - $idx) : null,
                    'completed_at' => $p['status'] === ProposalStatus::COMPLETED ? now()->subDays(2) : null,
                    'created_by' => $p['applicant']->id,
                    'updated_by' => $admin?->id,
                ]
            );

            // Generate QR Identity for Proposal
            try {
                $qrService->generateFor($proposal, QrType::PROPOSAL, $p['applicant'], null, [
                    'proposal_number' => $proposal->proposal_number,
                    'organization' => $p['org']->name,
                    'status' => $proposal->status->value,
                ]);
            } catch (\Throwable $e) {
                // Ignore
            }

            // 1. RAB Items
            $item1Price = round($p['requested'] * 0.4);
            $item2Price = round($p['requested'] * 0.35);
            $item3Price = $p['requested'] - $item1Price - $item2Price;

            ProposalBudgetItem::updateOrCreate(
                [
                    'proposal_id' => $proposal->id,
                    'item_name' => 'Pengadaan Perlengkapan Utama Sarana Fasilitasi',
                ],
                [
                    'category' => 'Belanja Modal / Peralatan',
                    'description' => 'Paket sarana penunjang kegiatan utama sesuai spesifikasi teknis.',
                    'quantity' => 1,
                    'unit' => 'Paket',
                    'unit_price' => $item1Price,
                    'subtotal' => $item1Price,
                    'sort_order' => 1,
                ]
            );

            ProposalBudgetItem::updateOrCreate(
                [
                    'proposal_id' => $proposal->id,
                    'item_name' => 'Biaya Pelatihan, Pelaksanaan Teknis & Honorarium Instruktur',
                ],
                [
                    'category' => 'Belanja Jasa & Pelatihan',
                    'description' => 'Pelaksanaan kegiatan pelatihan dan transfer pengetahuan.',
                    'quantity' => 1,
                    'unit' => 'Kegiatan',
                    'unit_price' => $item2Price,
                    'subtotal' => $item2Price,
                    'sort_order' => 2,
                ]
            );

            ProposalBudgetItem::updateOrCreate(
                [
                    'proposal_id' => $proposal->id,
                    'item_name' => 'Konsumsi, Dokumentasi, Publikasi & Penjilidan Laporan',
                ],
                [
                    'category' => 'Belanja Operasional',
                    'description' => 'Penunjang operasional, konsumsi peserta, dan administrasi laporan.',
                    'quantity' => 1,
                    'unit' => 'Paket',
                    'unit_price' => $item3Price,
                    'subtotal' => $item3Price,
                    'sort_order' => 3,
                ]
            );

            // 2. Proposal Documents
            $docReq = DocumentRequirement::where('grant_program_id', $p['program']->id)->first();

            $pDoc = ProposalDocument::updateOrCreate(
                [
                    'proposal_id' => $proposal->id,
                    'original_filename' => 'Proposal_Resmi_'.str_replace(['/', '-'], '_', $p['number']).'.pdf',
                ],
                [
                    'document_type_id' => $docTypeProposal?->id,
                    'document_requirement_id' => $docReq?->id,
                    'stored_filename' => Str::uuid().'.pdf',
                    'storage_disk' => 'public',
                    'storage_path' => 'proposals/'.$proposal->id.'/proposal.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => 1024 * 768,
                    'file_hash' => hash('sha256', $proposal->id.'proposal'),
                    'version' => 1,
                    'status' => 'verified',
                    'uploaded_by' => $p['applicant']->id,
                ]
            );

            ProposalDocumentVersion::updateOrCreate(
                [
                    'proposal_document_id' => $pDoc->id,
                    'version_number' => 1,
                ],
                [
                    'created_by' => $p['applicant']->id,
                    'original_filename' => $pDoc->original_filename,
                    'stored_filename' => $pDoc->stored_filename,
                    'storage_disk' => 'public',
                    'storage_path' => $pDoc->storage_path,
                    'mime_type' => $pDoc->mime_type,
                    'file_size' => $pDoc->file_size,
                    'file_hash' => $pDoc->file_hash,
                    'created_at' => now(),
                ]
            );

            // Ensure physical dummy file exists on disk
            $storageDisk = $pDoc->storage_disk ?? 'public';
            $storagePath = $pDoc->storage_path;
            if (! Storage::disk($storageDisk)->exists($storagePath)) {
                try {
                    $docService = app(DocumentService::class);
                    $pdfContent = $docService->generatePlaceholderPdf($pDoc);
                    Storage::disk($storageDisk)->put($storagePath, $pdfContent);
                } catch (\Throwable $e) {
                    // Ignore if generation fails during seed
                }
            }

            // 3. Status History
            ProposalStatusHistory::create([
                'proposal_id' => $proposal->id,
                'from_status' => null,
                'to_status' => $proposal->status->value,
                'changed_by' => $p['applicant']->id,
                'reason' => 'Tahapan siklus hidup awal sistem.',
                'changed_at' => now()->subDays(20),
            ]);

            // 4. Assignments
            if ($verifikator && in_array($p['status']->value, ['verification', 'revision', 'verified', 'evaluation', 'survey', 'recommended', 'approval', 'approved', 'disbursed', 'implementation', 'lpj_submitted', 'completed'])) {
                ProposalAssignment::updateOrCreate(
                    [
                        'proposal_id' => $proposal->id,
                        'assignment_type' => 'VERIFICATION',
                    ],
                    [
                        'assigned_user_id' => $verifikator->id,
                        'status' => in_array($p['status']->value, ['verification', 'revision']) ? 'IN_PROGRESS' : 'COMPLETED',
                        'assigned_by' => $admin->id,
                        'assigned_at' => now()->subDays(25),
                        'started_at' => now()->subDays(24),
                        'completed_at' => !in_array($p['status']->value, ['verification', 'revision']) ? now()->subDays(22) : null,
                    ]
                );
            }

            if ($evaluator && in_array($p['status']->value, ['evaluation', 'survey', 'recommended', 'approval', 'approved', 'disbursed', 'implementation', 'lpj_submitted', 'completed'])) {
                ProposalAssignment::updateOrCreate(
                    [
                        'proposal_id' => $proposal->id,
                        'assignment_type' => 'EVALUATION',
                    ],
                    [
                        'assigned_user_id' => $evaluator->id,
                        'status' => $p['status']->value === 'evaluation' ? 'IN_PROGRESS' : 'COMPLETED',
                        'assigned_by' => $admin->id,
                        'assigned_at' => now()->subDays(22),
                        'started_at' => now()->subDays(21),
                        'completed_at' => $p['status']->value !== 'evaluation' ? now()->subDays(19) : null,
                    ]
                );
            }

            if ($surveyor && in_array($p['status']->value, ['survey', 'recommended', 'approval', 'approved', 'disbursed', 'implementation', 'lpj_submitted', 'completed'])) {
                ProposalAssignment::updateOrCreate(
                    [
                        'proposal_id' => $proposal->id,
                        'assignment_type' => 'FIELD_SURVEY',
                    ],
                    [
                        'assigned_user_id' => $surveyor->id,
                        'status' => $p['status']->value === 'survey' ? 'IN_PROGRESS' : 'COMPLETED',
                        'assigned_by' => $admin->id,
                        'assigned_at' => now()->subDays(19),
                        'started_at' => now()->subDays(18),
                        'completed_at' => $p['status']->value !== 'survey' ? now()->subDays(16) : null,
                    ]
                );
            }
        }

        $this->command?->info('14 proposals with RAB, documents, history, and assignments seeded.');
    }
}
