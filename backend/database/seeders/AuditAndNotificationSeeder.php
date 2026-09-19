<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AuditAndNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@sikomando.test')->first();
        $verifikator = User::where('email', 'verifikator@sikomando.test')->first();
        $evaluator = User::where('email', 'evaluator@sikomando.test')->first();
        $surveyor = User::where('email', 'surveyor@sikomando.test')->first();
        $approver = User::where('email', 'approver@sikomando.test')->first();
        $pemohon1 = User::where('email', 'pemohon@sikomando.test')->first();

        $proposal = Proposal::where('proposal_number', 'PROP-SULUT/2026/01/0010')->first() ?? Proposal::first();

        // 1. Audit Logs
        $logs = [
            [
                'actor' => $pemohon1,
                'action' => 'AUTH_LOGIN',
                'module' => 'AUTH',
                'entity_type' => 'User',
                'entity_id' => $pemohon1?->id,
                'notes' => 'Pengguna berhasil melakukan otentikasi login portal pemohon.',
                'time' => now()->subDays(28),
            ],
            [
                'actor' => $pemohon1,
                'action' => 'PROPOSAL_SUBMITTED',
                'module' => 'PROPOSAL',
                'entity_type' => 'Proposal',
                'entity_id' => $proposal?->id,
                'notes' => 'Pengajuan proposal baru nomor '.$proposal?->proposal_number.' oleh pemohon.',
                'time' => now()->subDays(27),
            ],
            [
                'actor' => $admin,
                'action' => 'ASSIGNMENT_CREATED',
                'module' => 'ASSIGNMENT',
                'entity_type' => 'ProposalAssignment',
                'entity_id' => $proposal?->id,
                'notes' => 'Penugasan petugas verifikator berkas oleh administrator.',
                'time' => now()->subDays(25),
            ],
            [
                'actor' => $verifikator,
                'action' => 'VERIFICATION_PASSED',
                'module' => 'VERIFICATION',
                'entity_type' => 'Verification',
                'entity_id' => $proposal?->id,
                'notes' => 'Pemeriksaan berkas administrasi dan checklist persyaratan dinyatakan sah.',
                'time' => now()->subDays(22),
            ],
            [
                'actor' => $evaluator,
                'action' => 'EVALUATION_COMPLETED',
                'module' => 'EVALUATION',
                'entity_type' => 'Evaluation',
                'entity_id' => $proposal?->id,
                'notes' => 'Evaluasi teknis dan penilaian scoring kriteria komposit tuntas.',
                'time' => now()->subDays(19),
            ],
            [
                'actor' => $surveyor,
                'action' => 'FIELD_SURVEY_COMPLETED',
                'module' => 'FIELD_SURVEY',
                'entity_type' => 'FieldSurvey',
                'entity_id' => $proposal?->id,
                'notes' => 'Verifikasi faktual lapangan selesai dengan geo-tagging koordinat GPS.',
                'time' => now()->subDays(16),
            ],
            [
                'actor' => $approver,
                'action' => 'APPROVAL_DECIDED',
                'module' => 'APPROVAL',
                'entity_type' => 'Approval',
                'entity_id' => $proposal?->id,
                'notes' => 'Persetujuan pimpinan atas alokasi pagu hibah disetujui.',
                'time' => now()->subDays(13),
            ],
            [
                'actor' => $approver,
                'action' => 'DECISION_ISSUED',
                'module' => 'DECISION',
                'entity_type' => 'Decision',
                'entity_id' => $proposal?->id,
                'notes' => 'Penerbitan Surat Keputusan (SK) penetapan penerima dan tanda tangan digital TTE.',
                'time' => now()->subDays(12),
            ],
            [
                'actor' => $admin,
                'action' => 'DISBURSEMENT_PAID',
                'module' => 'DISBURSEMENT',
                'entity_type' => 'Disbursement',
                'entity_id' => $proposal?->id,
                'notes' => 'Penerbitan SP2D dan pemindahbukuan dana kasda ke rekening penerima.',
                'time' => now()->subDays(8),
            ],
            [
                'actor' => $verifikator,
                'action' => 'LPJ_VERIFIED',
                'module' => 'LPJ',
                'entity_type' => 'LpjSubmission',
                'entity_id' => $proposal?->id,
                'notes' => 'Laporan Pertanggungjawaban belanja diperiksa fisik dan dinyatakan sah.',
                'time' => now()->subDays(2),
            ],
        ];

        foreach ($logs as $l) {
            AuditLog::create([
                'actor_id' => $l['actor']?->id,
                'action' => $l['action'],
                'module' => $l['module'],
                'entity_type' => $l['entity_type'],
                'entity_id' => $l['entity_id'] ?? (string) Str::uuid(),
                'request_id' => 'REQ-'.strtoupper(Str::random(12)),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) SIKOMANDO/1.0',
                'old_values' => null,
                'new_values' => ['notes' => $l['notes']],
                'metadata' => ['env' => 'seeder_development', 'source' => 'system'],
                'occurred_at' => $l['time'],
            ]);
        }

        // 2. Notifications
        $notifications = [
            [
                'user' => $pemohon1,
                'type' => 'PROPOSAL_STATUS_CHANGED',
                'title' => 'Usulan Hibah Telah Ditetapkan SK',
                'message' => 'Selamat! Usulan Anda nomor '.$proposal?->proposal_number.' telah disahkan melalui Surat Keputusan Gubernur.',
                'read' => false,
            ],
            [
                'user' => $verifikator,
                'type' => 'NEW_ASSIGNMENT',
                'title' => 'Tugas Baru Verifikasi Administrasi',
                'message' => 'Anda mendapatkan tugas verifikasi berkas usulan nomor PROP-SULUT/2026/01/0003.',
                'read' => true,
            ],
            [
                'user' => $evaluator,
                'type' => 'NEW_ASSIGNMENT',
                'title' => 'Tugas Baru Evaluasi Teknis',
                'message' => 'Silakan lakukan telaah penilaian scoring teknis pada usulan nomor PROP-SULUT/2026/02/0006.',
                'read' => false,
            ],
            [
                'user' => $surveyor,
                'type' => 'NEW_ASSIGNMENT',
                'title' => 'Jadwal Survei Lapangan Faktual',
                'message' => 'Jadwal survei lapangan untuk Yayasan Bina Insan Harapan Manado telah ditetapkan.',
                'read' => true,
            ],
            [
                'user' => $approver,
                'type' => 'PENDING_SIGNATURE',
                'title' => 'Dokumen Menunggu TTE Anda',
                'message' => 'Naskah Surat Keputusan Penetapan Penerima Hibah Daerah menunggu persetujuan dan tanda tangan digital.',
                'read' => false,
            ],
        ];

        foreach ($notifications as $n) {
            Notification::create([
                'user_id' => $n['user']->id,
                'type' => $n['type'],
                'title' => $n['title'],
                'message' => $n['message'],
                'entity_type' => 'Proposal',
                'entity_id' => $proposal?->id,
                'data' => ['proposal_number' => $proposal?->proposal_number],
                'read_at' => $n['read'] ? now()->subHours(5) : null,
                'sent_at' => now()->subDays(1),
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ]);
        }

        $this->command?->info('Audit logs and notifications seeded.');
    }
}
