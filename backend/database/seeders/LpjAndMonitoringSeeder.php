<?php

namespace Database\Seeders;

use App\Enums\QrType;
use App\Models\DocumentType;
use App\Models\LpjDocument;
use App\Models\LpjItem;
use App\Models\LpjSubmission;
use App\Models\MonitoringItem;
use App\Models\MonitoringRecord;
use App\Models\Proposal;
use App\Models\User;
use App\Services\QrService;
use Illuminate\Database\Seeder;

class LpjAndMonitoringSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@sikomando.test')->first();
        $verifikator = User::where('email', 'verifikator@sikomando.test')->first();
        $surveyor = User::where('email', 'surveyor@sikomando.test')->first();
        $docTypeLpj = DocumentType::where('code', 'LPJ_DOCUMENT')->first();
        $qrService = app(QrService::class);

        // 1. LPJ for proposals 13 and 14
        $lpjProposals = Proposal::whereIn('status', ['lpj_submitted', 'completed'])->get();

        foreach ($lpjProposals as $idx => $prop) {
            $isVerified = $prop->status->value === 'completed';
            $amount = $prop->approved_amount ?? $prop->requested_amount;

            $lpj = LpjSubmission::updateOrCreate(
                ['proposal_id' => $prop->id],
                [
                    'organization_id' => $prop->organization_id,
                    'grant_program_id' => $prop->grant_program_id,
                    'submitted_by' => $prop->applicant_id,
                    'verified_by' => $isVerified ? $verifikator->id : null,
                    'approved_by' => $isVerified ? $admin->id : null,
                    'closed_by' => $isVerified ? $admin->id : null,
                    'lpj_number' => 'LPJ-HB/2026/00'.($idx + 1),
                    'status' => $isVerified ? 'closed' : 'submitted',
                    'total_received' => $amount,
                    'total_spent' => $amount,
                    'remaining_balance' => 0,
                    'summary' => 'Laporan Pertanggungjawaban pelaksanaan kegiatan '.$prop->title.' beserta bukti pengeluaran sah.',
                    'notes' => 'Seluruh dana telah direalisasikan 100% dan bukti transaksi lengkap.',
                    'verification_notes' => $isVerified ? 'LPJ telah diperiksa administrasi & bukti bayar fisik. Dinyatakan LENGKAP & SAH.' : null,
                    'submitted_at' => now()->subDays(4),
                    'verified_at' => $isVerified ? now()->subDays(2) : null,
                    'approved_at' => $isVerified ? now()->subDays(2) : null,
                    'closed_at' => $isVerified ? now()->subDays(2) : null,
                ]
            );

            try {
                $qrService->generateFor($lpj, QrType::LPJ, $prop->applicant, null, [
                    'lpj_number' => $lpj->lpj_number,
                    'total_spent' => $lpj->total_spent,
                    'status' => $lpj->status,
                ]);
            } catch (\Throwable $e) {}

            // LPJ Items
            LpjItem::updateOrCreate(
                [
                    'lpj_submission_id' => $lpj->id,
                    'item_name' => 'Realisasi Belanja Sarana Peralatan Utama',
                ],
                [
                    'category' => 'Belanja Modal',
                    'description' => 'Pembayaran lunas pengadaan sarana fisik sesuai BAST.',
                    'quantity' => 1,
                    'unit' => 'Paket',
                    'unit_price' => $amount * 0.5,
                    'subtotal' => $amount * 0.5,
                    'planned_amount' => $amount * 0.5,
                    'realized_amount' => $amount * 0.5,
                    'variance' => 0,
                    'status' => 'reported',
                    'notes' => 'Dilengkapi faktur pajak & kuitansi asli.',
                ]
            );

            LpjItem::updateOrCreate(
                [
                    'lpj_submission_id' => $lpj->id,
                    'item_name' => 'Realisasi Honorarium & Narasumber Pelatihan',
                ],
                [
                    'category' => 'Belanja Jasa',
                    'description' => 'Honor instruktur pelatihan dan sertifikat peserta.',
                    'quantity' => 1,
                    'unit' => 'Paket',
                    'unit_price' => $amount * 0.35,
                    'subtotal' => $amount * 0.35,
                    'planned_amount' => $amount * 0.35,
                    'realized_amount' => $amount * 0.35,
                    'variance' => 0,
                    'status' => 'reported',
                    'notes' => 'Dilengkapi daftar hadir & bukti transfer.',
                ]
            );

            LpjItem::updateOrCreate(
                [
                    'lpj_submission_id' => $lpj->id,
                    'item_name' => 'Realisasi Konsumsi & Pelaporan Dokumentasi',
                ],
                [
                    'category' => 'Belanja Operasional',
                    'description' => 'Konsumsi rapat koordinasi, dokumentasi foto, dan jilid buku LPJ.',
                    'quantity' => 1,
                    'unit' => 'Paket',
                    'unit_price' => $amount * 0.15,
                    'subtotal' => $amount * 0.15,
                    'planned_amount' => $amount * 0.15,
                    'realized_amount' => $amount * 0.15,
                    'variance' => 0,
                    'status' => 'reported',
                    'notes' => 'Nota belanja riil.',
                ]
            );

            // LPJ Document
            LpjDocument::updateOrCreate(
                [
                    'lpj_submission_id' => $lpj->id,
                    'document_title' => 'Buku Laporan Pertanggungjawaban Lengkap',
                ],
                [
                    'document_type' => 'LPJ_REPORT',
                    'original_filename' => 'Salinan_Lengkap_Buku_LPJ.pdf',
                    'stored_filename' => \Illuminate\Support\Str::uuid().'.pdf',
                    'storage_path' => 'lpj/'.$lpj->id.'/buku_lpj.pdf',
                    'file_size' => 1024 * 1200,
                    'mime_type' => 'application/pdf',
                    'disk' => 'local',
                    'status' => 'verified',
                    'uploaded_by' => $prop->applicant_id,
                ]
            );
        }

        // 2. Monitoring Records for proposals 11..14
        $monevProposals = Proposal::whereIn('status', [
            'disbursed', 'implementation', 'lpj_submitted', 'completed'
        ])->get();

        foreach ($monevProposals as $idx => $prop) {
            $isComplete = $prop->status->value === 'completed';

            $record = MonitoringRecord::updateOrCreate(
                ['proposal_id' => $prop->id],
                [
                    'created_by' => $surveyor->id,
                    'monitoring_number' => 'MONEV/2026/00'.($idx + 1),
                    'monitoring_type' => 'periodic',
                    'status' => 'completed',
                    'monitoring_date' => now()->subDays(5 - $idx)->toDateString(),
                    'progress_percentage' => $isComplete ? 100.00 : (75.00 + ($idx * 8)),
                    'financial_progress_percentage' => $isComplete ? 100.00 : (80.00 + ($idx * 6)),
                    'summary' => 'Pemantauan berkala lapangan atas progres fisik dan kesesuaian administrasi hibah.',
                    'findings' => 'Barang fisik telah terpasang dan digunakan sesuai peruntukan program.',
                    'recommendations' => $isComplete ? 'Program tuntas dan dapat ditutup secara formal.' : 'Lanjutkan penyusunan berkas LPJ akhir.',
                    'submitted_at' => now()->subDays(5 - $idx),
                    'verified_at' => now()->subDays(4 - $idx),
                    'verified_by' => $admin->id,
                ]
            );

            try {
                $qrService->generateFor($record, QrType::MONITORING, $surveyor, null, [
                    'monitoring_number' => $record->monitoring_number,
                    'progress' => $record->progress_percentage.'%',
                ]);
            } catch (\Throwable $e) {}

            MonitoringItem::updateOrCreate(
                [
                    'monitoring_record_id' => $record->id,
                    'indicator_code' => 'MONEV-ITM-01',
                ],
                [
                    'indicator_name' => 'Pemeriksaan Fisik Sarana & Pemanfaatan Barang',
                    'description' => 'Pemeriksaan langsung di lapangan atas sarana yang direalisasikan.',
                    'target_value' => 100.00,
                    'actual_value' => $record->progress_percentage,
                    'unit' => '%',
                    'status' => 'reported',
                    'notes' => 'Peralatan berfungsi baik dan terdata pada buku inventaris organisasi.',
                ]
            );
        }

        $this->command?->info('LPJ submissions, expense items, and monitoring records seeded.');
    }
}
