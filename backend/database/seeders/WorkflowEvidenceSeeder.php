<?php

namespace Database\Seeders;

use App\Enums\QrType;
use App\Models\Approval;
use App\Models\ApprovalAction;
use App\Models\Decision;
use App\Models\DecisionDocument;
use App\Models\DecisionDocumentVersion;
use App\Models\DocumentRequirement;
use App\Models\Evaluation;
use App\Models\EvaluationCriteria;
use App\Models\EvaluationItem;
use App\Models\FieldSurvey;
use App\Models\FieldSurveyDocument;
use App\Models\FieldSurveyFinding;
use App\Models\FieldSurveyItem;
use App\Models\GrantProgram;
use App\Models\Proposal;
use App\Models\Ranking;
use App\Models\RankingItem;
use App\Models\Recommendation;
use App\Models\RecommendationItem;
use App\Models\Revision;
use App\Models\RevisionItem;
use App\Models\User;
use App\Models\Verification;
use App\Models\VerificationItem;
use App\Services\QrService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WorkflowEvidenceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@sikomando.test')->first();
        $verifikator = User::where('email', 'verifikator@sikomando.test')->first();
        $evaluator = User::where('email', 'evaluator@sikomando.test')->first();
        $surveyor = User::where('email', 'surveyor@sikomando.test')->first();
        $approver = User::where('email', 'approver@sikomando.test')->first();
        $criteria = EvaluationCriteria::all();
        $qrService = app(QrService::class);

        // 1. Verifications for proposals 3..14
        $proposalsWithVerification = Proposal::whereNotIn('status', ['draft', 'submitted'])->get();

        foreach ($proposalsWithVerification as $prop) {
            $isRevision = $prop->status->value === 'revision';
            $isCompleted = !in_array($prop->status->value, ['verification', 'revision']);

            $verification = Verification::updateOrCreate(
                ['proposal_id' => $prop->id],
                [
                    'verifier_id' => $verifikator->id,
                    'verification_number' => 'VER/'.str_replace('/', '-', $prop->proposal_number),
                    'status' => $isCompleted ? 'completed' : ($isRevision ? 'revision_required' : 'in_progress'),
                    'result' => $isCompleted ? 'pass' : ($isRevision ? 'need_revision' : null),
                    'summary' => 'Pemeriksaan kelengkapan dokumen administrasi proposal hibah.',
                    'notes' => $isCompleted ? 'Seluruh berkas persyaratan lengkap dan absah.' : 'Menunggu pemenuhan checklist kelengkapan berkas.',
                    'started_at' => now()->subDays(24),
                    'completed_at' => $isCompleted ? now()->subDays(22) : null,
                ]
            );

            // Verification Items
            $docReqs = DocumentRequirement::where('grant_program_id', $prop->grant_program_id)->get();
            foreach ($docReqs as $dReq) {
                VerificationItem::updateOrCreate(
                    [
                        'verification_id' => $verification->id,
                        'item_code' => 'CHECK-REQ-'.$dReq->id,
                    ],
                    [
                        'requirement_id' => $dReq->requirement_id,
                        'document_type_id' => $dReq->document_type_id,
                        'checked_by' => $verifikator->id,
                        'item_name' => 'Kelengkapan '.$dReq->scope.' '.$dReq->id,
                        'result' => $isCompleted ? 'pass' : ($isRevision ? 'need_revision' : 'pending'),
                        'notes' => $isCompleted ? 'Dokumen valid & sah' : ($isRevision ? 'Format RAB perlu disesuaikan dengan standar biaya umum daerah' : 'Dalam telaah'),
                        'checked_at' => now()->subDays(23),
                    ]
                );
            }

            // If revision status, seed Revision and RevisionItem
            if ($isRevision) {
                $revision = Revision::updateOrCreate(
                    [
                        'proposal_id' => $prop->id,
                        'revision_number' => 1,
                    ],
                    [
                        'requested_by' => $verifikator->id,
                        'status' => 'requested',
                        'reason' => 'Perbaikan rincian komponen RAB dan penyesuaian narasi luaran kegiatan.',
                        'requested_at' => now()->subDays(2),
                    ]
                );

                RevisionItem::updateOrCreate(
                    [
                        'revision_id' => $revision->id,
                        'field_name' => 'proposal_budget_items',
                    ],
                    [
                        'item_code' => 'REV-RAB-01',
                        'description' => 'Sesuaikan harga satuan belanja modal dengan standar harga Pemprov Sulut.',
                        'old_value' => 'Harga satuan di atas SBU',
                        'new_value' => null,
                        'status' => 'open',
                    ]
                );

                RevisionItem::updateOrCreate(
                    [
                        'revision_id' => $revision->id,
                        'field_name' => 'activities',
                    ],
                    [
                        'item_code' => 'REV-ACT-02',
                        'description' => 'Lengkapi jadwal tahapan pelaksanaan mingguan.',
                        'old_value' => 'Jadwal bulanan',
                        'new_value' => null,
                        'status' => 'open',
                    ]
                );
            }
        }

        // 2. Evaluations for proposals 6..14
        $proposalsWithEval = Proposal::whereIn('status', [
            'evaluation', 'survey', 'recommended', 'approval', 'approved', 'disbursed', 'implementation', 'lpj_submitted', 'completed'
        ])->get();

        foreach ($proposalsWithEval as $idx => $prop) {
            $isEvalCompleted = $prop->status->value !== 'evaluation';
            $scoreBase = 80 + ($idx % 15);

            $evaluation = Evaluation::updateOrCreate(
                ['proposal_id' => $prop->id],
                [
                    'evaluator_id' => $evaluator->id,
                    'evaluation_number' => 'EVAL/'.str_replace('/', '-', $prop->proposal_number),
                    'status' => $isEvalCompleted ? 'COMPLETED' : 'IN_PROGRESS',
                    'total_score' => $isEvalCompleted ? $scoreBase : null,
                    'final_score' => $isEvalCompleted ? $scoreBase : null,
                    'result' => $isEvalCompleted ? 'RECOMMENDED' : null,
                    'summary' => 'Penilaian kesesuaian teknis, kapasitas organisasi, dan kewajaran anggaran.',
                    'started_at' => now()->subDays(21),
                    'completed_at' => $isEvalCompleted ? now()->subDays(19) : null,
                ]
            );

            foreach ($criteria as $crit) {
                EvaluationItem::updateOrCreate(
                    [
                        'evaluation_id' => $evaluation->id,
                        'evaluation_criteria_id' => $crit->id,
                    ],
                    [
                        'weight' => 25.0,
                        'score' => $isEvalCompleted ? $scoreBase : 75.0,
                        'weighted_score' => $isEvalCompleted ? ($scoreBase * 0.25) : 18.75,
                        'minimum_score' => 60.0,
                        'maximum_score' => 100.0,
                        'result' => $isEvalCompleted ? 'PASS' : 'PENDING',
                        'notes' => 'Kriteria dinilai memenuhi indikator kelayakan teknis program.',
                        'scored_at' => $isEvalCompleted ? now()->subDays(20) : null,
                    ]
                );
            }
        }

        // 3. Field Surveys for proposals 7..14
        $proposalsWithSurvey = Proposal::whereIn('status', [
            'survey', 'recommended', 'approval', 'approved', 'disbursed', 'implementation', 'lpj_submitted', 'completed'
        ])->get();

        foreach ($proposalsWithSurvey as $idx => $prop) {
            $isSurveyCompleted = $prop->status->value !== 'survey';

            $survey = FieldSurvey::updateOrCreate(
                ['proposal_id' => $prop->id],
                [
                    'surveyor_id' => $surveyor->id,
                    'survey_number' => 'SRV/'.str_replace('/', '-', $prop->proposal_number),
                    'status' => $isSurveyCompleted ? 'completed' : 'in_progress',
                    'result' => $isSurveyCompleted ? 'recommended' : null,
                    'scheduled_date' => now()->subDays(18)->toDateString(),
                    'started_at' => now()->subDays(18),
                    'completed_at' => $isSurveyCompleted ? now()->subDays(16) : null,
                    'location_name' => $prop->organization->name,
                    'location_address' => $prop->organization->address,
                    'latitude' => 1.4748300,
                    'longitude' => 124.8420800,
                    'summary' => 'Hasil verifikasi faktual keberadaan sekretariat dan keabsahan pengurus di lokasi.',
                    'recommendation' => 'Organisasi aktif, sarana fisik ada, dan siap menerima serta mengelola dana hibah.',
                ]
            );

            // Generate QR for Field Survey
            try {
                $qrService->generateFor($survey, QrType::FIELD_SURVEY, $surveyor, null, [
                    'survey_number' => $survey->survey_number,
                    'proposal' => $prop->proposal_number,
                ]);
            } catch (\Throwable $e) {}

            FieldSurveyItem::updateOrCreate(
                [
                    'field_survey_id' => $survey->id,
                    'item_code' => 'SURVEY-ITM-01',
                ],
                [
                    'item_name' => 'Pemeriksaan Keberadaan Sekretariat Fisik',
                    'description' => 'Verifikasi faktual domisili dan papan nama kantor organisasi.',
                    'result' => 'pass',
                    'notes' => 'Sekretariat nyata dan memiliki papan nama organisasi resmi.',
                    'checked_by' => $surveyor->id,
                    'checked_at' => now()->subDays(17),
                ]
            );

            FieldSurveyItem::updateOrCreate(
                [
                    'field_survey_id' => $survey->id,
                    'item_code' => 'SURVEY-ITM-02',
                ],
                [
                    'item_name' => 'Wawancara Pengurus & Penerima Manfaat',
                    'description' => 'Wawancara langsung legalitas personalia pengurus.',
                    'result' => 'pass',
                    'notes' => 'Pengurus hadir lengkap (Ketua, Sekretaris, Bendahara).',
                    'checked_by' => $surveyor->id,
                    'checked_at' => now()->subDays(17),
                ]
            );

            FieldSurveyFinding::updateOrCreate(
                [
                    'field_survey_id' => $survey->id,
                    'finding_code' => 'FND-SRV-'.strtoupper(Str::random(6)),
                ],
                [
                    'title' => 'Kesiapan Ruang Pelatihan',
                    'finding_type' => 'readiness',
                    'description' => 'Ruang kegiatan memadai untuk menampung peserta pelatihan.',
                    'severity' => 'low',
                    'recommended_action' => 'Dapat dilanjutkan ke proses pencairan.',
                    'status' => 'open',
                ]
            );

            FieldSurveyDocument::updateOrCreate(
                [
                    'field_survey_id' => $survey->id,
                    'document_title' => 'Foto Plang Sekretariat dan Kantor Organisasi',
                ],
                [
                    'original_filename' => 'Plang_Sekretariat_Faktual.jpg',
                    'stored_filename' => Str::uuid().'.jpg',
                    'storage_path' => 'surveys/'.$survey->id.'/foto1.jpg',
                    'file_size' => 1024 * 350,
                    'mime_type' => 'image/jpeg',
                    'disk' => 'local',
                    'status' => 'active',
                    'uploaded_by' => $surveyor->id,
                    'notes' => 'Papan nama sekretariat organisasi di lokasi verifikasi.',
                ]
            );
        }

        // 4. Program Rankings & Recommendation
        $prog1 = GrantProgram::where('code', 'GP-SULUT-2026-01')->first();
        if ($prog1) {
            $ranking = Ranking::updateOrCreate(
                [
                    'grant_program_id' => $prog1->id,
                    'ranking_number' => 'RANK-GP-SULUT-2026-01-01',
                ],
                [
                    'status' => 'finalized',
                    'weights_snapshot' => [
                        'evaluation' => 0.40,
                        'survey' => 0.40,
                        'priority' => 0.20,
                    ],
                    'cutoff_score' => 75.0,
                    'total_proposals' => 8,
                    'recommended_count' => 6,
                    'not_recommended_count' => 2,
                    'summary' => 'Hasil perankingan komposit usulan hibah tahun 2026 berdasarkan evaluasi teknis dan survei lapangan.',
                    'generated_by' => $admin?->id,
                    'reviewed_by' => $admin?->id,
                    'finalized_at' => now()->subDays(15),
                    'generated_at' => now()->subDays(16),
                ]
            );

            $rankedProposals = Proposal::where('grant_program_id', $prog1->id)
                ->whereIn('status', ['recommended', 'approval', 'approved', 'disbursed', 'implementation', 'lpj_submitted'])
                ->get();

            foreach ($rankedProposals as $rankIdx => $rProp) {
                RankingItem::updateOrCreate(
                    [
                        'ranking_id' => $ranking->id,
                        'proposal_id' => $rProp->id,
                    ],
                    [
                        'rank' => $rankIdx + 1,
                        'evaluation_score' => 85.0 - $rankIdx,
                        'survey_score' => 88.0 - $rankIdx,
                        'final_score' => 86.5 - $rankIdx,
                        'status' => 'eligible',
                        'recommendation_result' => 'recommended',
                        'recommendation_reason' => 'Memenuhi passing grade kelayakan program di atas 75.0.',
                    ]
                );

                // Recommendations
                $rec = Recommendation::updateOrCreate(
                    ['proposal_id' => $rProp->id],
                    [
                        'recommended_by' => $admin?->id,
                        'recommendation_number' => 'REC/'.str_replace('/', '-', $rProp->proposal_number),
                        'status' => 'completed',
                        'result' => 'recommended',
                        'recommended_amount' => $rProp->approved_amount ?? $rProp->requested_amount,
                        'summary' => 'Direkomendasikan menerima hibah daerah TA 2026.',
                        'submitted_at' => now()->subDays(15),
                        'completed_at' => now()->subDays(14),
                    ]
                );

                RecommendationItem::updateOrCreate(
                    [
                        'recommendation_id' => $rec->id,
                        'item_code' => 'REC-ITM-01',
                    ],
                    [
                        'item_name' => 'Kelayakan Pendanaan Program Hibah',
                        'result' => 'RECOMMENDED',
                        'description' => 'Alokasi disesuaikan dengan pagu anggaran daerah yang tersedia: Rp '.number_format($rProp->approved_amount ?? $rProp->requested_amount, 0, ',', '.'),
                        'notes' => 'Memenuhi kriteria prioritas pembangunan daerah Sulawesi Utara.',
                    ]
                );
            }
        }

        // 5. Approvals for proposals 9..14
        $proposalsWithApproval = Proposal::whereIn('status', [
            'approval', 'approved', 'disbursed', 'implementation', 'lpj_submitted', 'completed'
        ])->get();

        foreach ($proposalsWithApproval as $prop) {
            $isApproved = $prop->status->value !== 'approval';

            $approval = Approval::updateOrCreate(
                ['proposal_id' => $prop->id],
                [
                    'approval_number' => 'APP/'.str_replace('/', '-', $prop->proposal_number),
                    'approval_type' => 'proposal',
                    'required_levels' => 1,
                    'current_level' => 1,
                    'status' => $isApproved ? 'approved' : 'pending',
                    'summary' => 'Persetujuan pimpinan daerah atas penetapan usulan hibah.',
                    'started_at' => now()->subDays(14),
                    'completed_at' => $isApproved ? now()->subDays(13) : null,
                ]
            );

            if ($isApproved) {
                ApprovalAction::updateOrCreate(
                    [
                        'approval_id' => $approval->id,
                        'level' => 1,
                    ],
                    [
                        'actor_id' => $approver->id,
                        'action' => 'approve',
                        'notes' => 'Disetujui untuk diterbitkan SK Penetapan Penerima Hibah.',
                        'acted_at' => now()->subDays(13),
                    ]
                );
            } else {
                ApprovalAction::updateOrCreate(
                    [
                        'approval_id' => $approval->id,
                        'level' => 1,
                    ],
                    [
                        'actor_id' => $admin->id,
                        'action' => 'submit',
                        'notes' => 'Pengajuan usulan untuk telaah persetujuan pimpinan.',
                        'acted_at' => now()->subDays(14),
                    ]
                );
            }
        }

        // 6. Decisions & Decision Documents for proposals 10..14
        $proposalsWithDecision = Proposal::whereIn('status', [
            'approved', 'disbursed', 'implementation', 'lpj_submitted', 'completed'
        ])->get();

        foreach ($proposalsWithDecision as $idx => $prop) {
            $decision = Decision::updateOrCreate(
                ['proposal_id' => $prop->id],
                [
                    'issued_by' => $approver->id,
                    'decision_number' => 'SK-GUB-SULUT/2026/00'.($idx + 1),
                    'decision_type' => 'grant_approval',
                    'status' => 'published',
                    'result' => 'approved',
                    'decision_date' => now()->subDays(12)->toDateString(),
                    'approved_amount' => $prop->approved_amount ?? $prop->requested_amount,
                    'title' => 'Penetapan Penerima Hibah Daerah untuk '.$prop->organization->name,
                    'summary' => 'Surat Keputusan Gubernur Sulawesi Utara tentang Penetapan Organisasi Penerima dan Alokasi Belanja Hibah Daerah.',
                    'issued_at' => now()->subDays(12),
                ]
            );

            // Generate QR for Decision SK
            try {
                $qrService->generateFor($decision, QrType::DECISION_SK, $approver, null, [
                    'decision_number' => $decision->decision_number,
                    'recipient' => $prop->organization->name,
                    'approved_amount' => $decision->approved_amount,
                ]);
            } catch (\Throwable $e) {}

            $dDoc = DecisionDocument::updateOrCreate(
                [
                    'decision_id' => $decision->id,
                    'document_type' => 'DECISION_SK',
                ],
                [
                    'document_title' => 'Salinan Naskah SK Penetapan Hibah '.$decision->decision_number,
                    'original_filename' => 'SK_Penetapan_'.str_replace(['/', '-'], '_', $decision->decision_number).'.pdf',
                    'stored_filename' => Str::uuid().'.pdf',
                    'storage_path' => 'decisions/'.$decision->id.'/sk_penetapan.pdf',
                    'file_size' => 1024 * 850,
                    'file_hash' => hash('sha256', $decision->id.'decision_doc'),
                    'mime_type' => 'application/pdf',
                    'disk' => 'local',
                    'version' => 1,
                    'status' => 'active',
                    'uploaded_by' => $approver->id,
                    'generated_at' => now()->subDays(12),
                ]
            );

            DecisionDocumentVersion::updateOrCreate(
                [
                    'decision_document_id' => $dDoc->id,
                    'version_number' => 1,
                ],
                [
                    'created_by' => $approver->id,
                    'original_filename' => $dDoc->original_filename,
                    'stored_filename' => $dDoc->stored_filename,
                    'disk' => $dDoc->disk,
                    'storage_path' => $dDoc->storage_path,
                    'mime_type' => $dDoc->mime_type,
                    'file_size' => $dDoc->file_size,
                    'file_hash' => $dDoc->file_hash,
                    'status' => 'active',
                    'created_at' => now()->subDays(12),
                ]
            );
        }

        $this->command?->info('Verifications, evaluations, field surveys, rankings, approvals, and decisions seeded.');
    }
}
