<?php

namespace Database\Seeders;

use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use App\Models\GrantProgram;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $grantProgram = GrantProgram::query()->firstOrCreate(
            ['code' => 'HIBAH-2026'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Program Hibah Pemberdayaan Masyarakat 2026',
                'description' => 'Program hibah bantuan dana pemberdayaan sosial dan ekonomi masyarakat.',
                'fiscal_year' => '2026',
                'status' => 'active',
                'registration_start_at' => now()->startOfYear(),
                'registration_end_at' => now()->addMonths(6),
                'minimum_amount' => 10000000,
                'maximum_amount' => 100000000,
                'total_budget' => 2000000000,
                'is_active' => true,
            ]
        );

        $announcements = [
            [
                'title' => 'Pembukaan Pendaftaran Usulan Hibah Tahun Anggaran 2026',
                'slug' => 'pembukaan-pendaftaran-usulan-hibah-ta-2026',
                'category' => 'schedule',
                'excerpt' => 'Pemerintah resmi membuka pengajuan proposal hibah tahun anggaran 2026.',
                'content' => 'Pengajuan proposal hibah dibuka mulai tanggal 1 Januari 2026 sampai dengan 30 Juni 2026 melalui portal resmi SIKOMANDO.',
                'status' => AnnouncementStatus::PUBLISHED,
                'is_pinned' => true,
                'published_at' => now()->subDays(15),
                'grant_program_id' => $grantProgram->id,
            ],
            [
                'title' => 'Petunjuk Teknis dan Format Rencana Anggaran Biaya (RAB)',
                'slug' => 'petunjuk-teknis-dan-format-rencana-anggaran-biaya-rab',
                'category' => 'guideline',
                'excerpt' => 'Format dan tata cara penyusunan proposal hibah sesuai regulasi terbaru.',
                'content' => 'Seluruh pemohon diwajibkan mengikuti format rencana anggaran biaya (RAB) dan melampirkan legalitas organisasi yang sah.',
                'status' => AnnouncementStatus::PUBLISHED,
                'is_pinned' => false,
                'published_at' => now()->subDays(10),
                'grant_program_id' => $grantProgram->id,
            ],
            [
                'title' => 'Pengumuman Hasil Verifikasi Administrasi Proposal Periode I',
                'slug' => 'pengumuman-hasil-verifikasi-administrasi-proposal-periode-i',
                'category' => 'selection_result',
                'excerpt' => 'Hasil verifikasi administrasi usulan proposal periode pertama telah selesai dievaluasi.',
                'content' => 'Proposal yang dinyatakan lolos administrasi berhak melanjutkan ke tahapan evaluasi substansi dan survei lapangan.',
                'status' => AnnouncementStatus::PUBLISHED,
                'is_pinned' => false,
                'published_at' => now()->subDays(3),
                'grant_program_id' => $grantProgram->id,
            ],
        ];

        foreach ($announcements as $item) {
            Announcement::query()->updateOrCreate(
                ['slug' => $item['slug']],
                array_merge($item, ['id' => (string) Str::uuid()])
            );
        }
    }
}
