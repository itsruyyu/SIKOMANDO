<?php

namespace App\Services;

use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use App\Models\GrantProgram;
use App\Models\Proposal;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PublicPortalService
{
    /**
     * Post-draft proposal statuses representing officially submitted applications.
     */
    protected const SUBMITTED_STATUSES = [
        'submitted',
        'verification',
        'revision',
        'verified',
        'evaluation',
        'survey',
        'recommended',
        'approval',
        'approved',
        'disbursed',
        'implementation',
        'lpj_submitted',
        'lpj_verified',
        'completed',
        'rejected',
    ];

    /**
     * Statuses representing proposals undergoing processing.
     */
    protected const IN_PROCESS_STATUSES = [
        'submitted',
        'verification',
        'revision',
        'verified',
        'evaluation',
        'survey',
        'recommended',
        'approval',
    ];

    /**
     * Statuses representing approved and downstream active proposals.
     */
    protected const APPROVED_STATUSES = [
        'approved',
        'disbursed',
        'implementation',
        'lpj_submitted',
        'lpj_verified',
        'completed',
    ];

    /**
     * Get paginated active grant programs for public listing.
     */
    public function getPublicGrantPrograms(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = GrantProgram::query()
            ->publiclyAvailable()
            ->withCount(['documentRequirements' => fn ($q) => $q->where('is_active', true)]);

        if (! empty($filters['fiscal_year'])) {
            $query->where('fiscal_year', $filters['fiscal_year']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['keyword'])) {
            $kw = '%'.trim($filters['keyword']).'%';
            $query->where(function ($q) use ($kw) {
                $q->where('name', 'ILIKE', $kw)
                    ->orWhere('description', 'ILIKE', $kw)
                    ->orWhere('code', 'ILIKE', $kw);
            });
        }

        $perPage = min(max($perPage, 1), 100);

        return $query->orderByDesc('fiscal_year')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get single active grant program detail for public viewing.
     */
    public function getPublicGrantProgram(GrantProgram $program): GrantProgram
    {
        if (! $program->is_active) {
            abort(404, 'Program hibah tidak ditemukan atau belum aktif.');
        }

        $program->load([
            'documentRequirements' => fn ($q) => $q->where('is_active', true)
                ->with(['documentType', 'requirement'])
                ->orderBy('sort_order'),
        ]);

        return $program;
    }

    /**
     * Get paginated published announcements.
     */
    public function getPublicAnnouncements(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Announcement::query()
            ->published()
            ->with('grantProgram:id,code,name,fiscal_year');

        if (! empty($filters['grant_program_id'])) {
            $query->where('grant_program_id', $filters['grant_program_id']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['keyword'])) {
            $kw = '%'.trim($filters['keyword']).'%';
            $query->where(function ($q) use ($kw) {
                $q->where('title', 'ILIKE', $kw)
                    ->orWhere('excerpt', 'ILIKE', $kw)
                    ->orWhere('content', 'ILIKE', $kw);
            });
        }

        if (! empty($filters['date_from'])) {
            $query->where('published_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (! empty($filters['date_to'])) {
            $query->where('published_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        $perPage = min(max($perPage, 1), 100);

        return $query->orderedForPublic()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get single published announcement by model instance.
     */
    public function getPublicAnnouncement(Announcement $announcement): Announcement
    {
        if (
            $announcement->status !== AnnouncementStatus::PUBLISHED ||
            ! $announcement->published_at ||
            $announcement->published_at->isFuture()
        ) {
            abort(404, 'Pengumuman tidak ditemukan atau belum dipublikasikan.');
        }

        $announcement->load('grantProgram:id,code,name,fiscal_year');

        return $announcement;
    }

    /**
     * Get public statistics summary (all aggregates, zero PII).
     */
    public function getPublicStatisticsSummary(): array
    {
        $activeProgramsCount = GrantProgram::query()->publiclyAvailable()->count();

        $proposalCounts = DB::table('proposals')
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->selectRaw("
                COUNT(*) as total_submitted,
                COUNT(CASE WHEN status IN ('".implode("','", self::IN_PROCESS_STATUSES)."') THEN 1 END) as in_process,
                COUNT(CASE WHEN status IN ('".implode("','", self::APPROVED_STATUSES)."') THEN 1 END) as approved,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(DISTINCT CASE WHEN status IN ('".implode("','", self::APPROVED_STATUSES)."') THEN organization_id END) as recipient_orgs,
                COALESCE(SUM(CASE WHEN status IN ('".implode("','", self::APPROVED_STATUSES)."') THEN approved_amount ELSE 0 END), 0) as total_approved_amount
            ")
            ->first();

        $totalDisbursed = (float) DB::table('disbursements')
            ->where('status', 'paid')
            ->sum('paid_amount');

        $completedLpj = DB::table('lpj_submissions')
            ->whereIn('status', ['approved', 'finalized'])
            ->count();

        return [
            'active_programs_count' => (int) $activeProgramsCount,
            'total_proposals_submitted' => (int) ($proposalCounts->total_submitted ?? 0),
            'proposals_in_process' => (int) ($proposalCounts->in_process ?? 0),
            'proposals_approved' => (int) ($proposalCounts->approved ?? 0),
            'proposals_rejected' => (int) ($proposalCounts->rejected ?? 0),
            'proposals_completed' => (int) ($proposalCounts->completed ?? 0),
            'recipient_organizations_count' => (int) ($proposalCounts->recipient_orgs ?? 0),
            'total_approved_amount' => (float) ($proposalCounts->total_approved_amount ?? 0),
            'total_disbursed_amount' => (float) $totalDisbursed,
            'completed_lpj_count' => (int) $completedLpj,
            'last_updated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get detailed public statistics including breakdowns by fiscal year and by program.
     */
    public function getPublicStatistics(): array
    {
        $summary = $this->getPublicStatisticsSummary();

        // Breakdown by Fiscal Year
        $byFiscalYear = DB::table('grant_programs as gp')
            ->where('gp.is_active', true)
            ->leftJoin('proposals as p', function ($join) {
                $join->on('p.grant_program_id', '=', 'gp.id')
                    ->whereNull('p.deleted_at')
                    ->whereNotIn('p.status', ['draft', 'cancelled']);
            })
            ->selectRaw("
                gp.fiscal_year,
                COUNT(DISTINCT gp.id) as programs_count,
                COUNT(p.id) as submitted_proposals,
                COUNT(CASE WHEN p.status IN ('".implode("','", self::APPROVED_STATUSES)."') THEN 1 END) as approved_proposals,
                COALESCE(SUM(CASE WHEN p.status IN ('".implode("','", self::APPROVED_STATUSES)."') THEN p.approved_amount ELSE 0 END), 0) as approved_amount
            ")
            ->groupBy('gp.fiscal_year')
            ->orderByDesc('gp.fiscal_year')
            ->get()
            ->map(fn ($row) => [
                'fiscal_year' => $row->fiscal_year,
                'programs_count' => (int) $row->programs_count,
                'submitted_proposals_count' => (int) $row->submitted_proposals,
                'approved_proposals_count' => (int) $row->approved_proposals,
                'approved_amount' => (float) $row->approved_amount,
            ])
            ->toArray();

        // Breakdown by Active Grant Programs
        $byProgram = GrantProgram::query()
            ->publiclyAvailable()
            ->orderByDesc('fiscal_year')
            ->orderBy('name')
            ->get()
            ->map(function (GrantProgram $program) {
                return $this->getProgramTransparencyMetrics($program);
            })
            ->toArray();

        return [
            'summary' => $summary,
            'by_fiscal_year' => $byFiscalYear,
            'by_program' => $byProgram,
        ];
    }

    /**
     * Get transparency overview for all publicly available grant programs.
     */
    public function getPublicTransparencySummary(): array
    {
        $programs = GrantProgram::query()
            ->publiclyAvailable()
            ->orderByDesc('fiscal_year')
            ->orderBy('name')
            ->get();

        $programMetrics = $programs->map(function (GrantProgram $program) {
            return $this->getProgramTransparencyMetrics($program);
        })->toArray();

        $overall = $this->getPublicStatisticsSummary();

        return [
            'overview' => $overall,
            'programs' => $programMetrics,
        ];
    }

    /**
     * Get transparency data for a specific grant program.
     */
    public function getPublicTransparencyForProgram(GrantProgram $program): array
    {
        if (! $program->is_active) {
            abort(404, 'Program hibah tidak ditemukan atau belum aktif.');
        }

        return $this->getProgramTransparencyMetrics($program);
    }

    /**
     * Calculate aggregate transparency metrics for a single program.
     */
    protected function getProgramTransparencyMetrics(GrantProgram $program): array
    {
        $proposalCounts = DB::table('proposals')
            ->where('grant_program_id', $program->id)
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->selectRaw("
                COUNT(*) as total_received,
                COUNT(CASE WHEN status IN ('".implode("','", ['verified', 'evaluation', 'survey', 'recommended', 'approval', 'approved', 'disbursed', 'implementation', 'lpj_submitted', 'lpj_verified', 'completed'])."') THEN 1 END) as verified_count,
                COUNT(CASE WHEN status IN ('".implode("','", ['recommended', 'approval', 'approved', 'disbursed', 'implementation', 'lpj_submitted', 'lpj_verified', 'completed'])."') THEN 1 END) as evaluated_count,
                COUNT(CASE WHEN status IN ('".implode("','", self::IN_PROCESS_STATUSES)."') THEN 1 END) as in_process_count,
                COUNT(CASE WHEN status IN ('".implode("','", self::APPROVED_STATUSES)."') THEN 1 END) as approved_count,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count,
                COUNT(DISTINCT CASE WHEN status IN ('".implode("','", self::APPROVED_STATUSES)."') THEN organization_id END) as recipient_count,
                COALESCE(SUM(CASE WHEN status IN ('".implode("','", self::APPROVED_STATUSES)."') THEN approved_amount ELSE 0 END), 0) as approved_amount
            ")
            ->first();

        $disbursedAmount = (float) DB::table('disbursements as d')
            ->join('proposals as p', 'p.id', '=', 'd.proposal_id')
            ->where('p.grant_program_id', $program->id)
            ->where('d.status', 'paid')
            ->sum('d.paid_amount');

        $completedLpjs = (int) DB::table('lpj_submissions as l')
            ->join('proposals as p', 'p.id', '=', 'l.proposal_id')
            ->where('p.grant_program_id', $program->id)
            ->whereIn('l.status', ['approved', 'finalized'])
            ->count();

        return [
            'grant_program' => [
                'id' => $program->id,
                'code' => $program->code,
                'name' => $program->name,
                'fiscal_year' => $program->fiscal_year,
                'total_budget' => (float) ($program->total_budget ?? 0),
                'minimum_amount' => (float) ($program->minimum_amount ?? 0),
                'maximum_amount' => (float) ($program->maximum_amount ?? 0),
            ],
            'proposals_received' => (int) ($proposalCounts->total_received ?? 0),
            'proposals_verified' => (int) ($proposalCounts->verified_count ?? 0),
            'proposals_evaluated' => (int) ($proposalCounts->evaluated_count ?? 0),
            'proposals_in_process' => (int) ($proposalCounts->in_process_count ?? 0),
            'proposals_approved' => (int) ($proposalCounts->approved_count ?? 0),
            'proposals_rejected' => (int) ($proposalCounts->rejected_count ?? 0),
            'recipient_organizations_count' => (int) ($proposalCounts->recipient_count ?? 0),
            'total_approved_amount' => (float) ($proposalCounts->approved_amount ?? 0),
            'total_disbursed_amount' => $disbursedAmount,
            'completed_lpj_count' => $completedLpjs,
        ];
    }

    /**
     * Get ordered public timeline stages for a grant program.
     */
    public function getPublicProgramTimeline(GrantProgram $program): array
    {
        if (! $program->is_active) {
            abort(404, 'Program hibah tidak ditemukan atau belum aktif.');
        }

        $now = now();
        $regStart = $program->registration_start_at;
        $regEnd = $program->registration_end_at;

        $stages = [];

        // 1. Tahap Pendaftaran Proposal
        $stages[] = [
            'stage_code' => 'REGISTRATION',
            'stage_name' => 'Pendaftaran & Pengajuan Usulan Proposal',
            'description' => 'Masa pengajuan proposal hibah dan kelengkapan dokumen administrasi oleh pemohon.',
            'start_date' => $regStart?->toDateString(),
            'end_date' => $regEnd?->toDateString(),
            'status' => $this->resolveStageStatus($now, $regStart, $regEnd),
        ];

        // 2. Tahap Verifikasi Administrasi
        $verifStart = $regEnd ? (clone $regEnd)->addDay() : null;
        $verifEnd = $verifStart ? (clone $verifStart)->addDays(14) : null;
        $stages[] = [
            'stage_code' => 'VERIFICATION',
            'stage_name' => 'Verifikasi Administrasi & Kelengkapan Dokumen',
            'description' => 'Pemeriksaan validitas legalitas organisasi dan kesesuaian berkas persyaratan proposal.',
            'start_date' => $verifStart?->toDateString(),
            'end_date' => $verifEnd?->toDateString(),
            'status' => $this->resolveStageStatus($now, $verifStart, $verifEnd),
        ];

        // 3. Tahap Evaluasi Kelayakan & Survei Lapangan
        $evalStart = $verifEnd ? (clone $verifEnd)->addDay() : null;
        $evalEnd = $evalStart ? (clone $evalStart)->addDays(21) : null;
        $stages[] = [
            'stage_code' => 'EVALUATION_AND_SURVEY',
            'stage_name' => 'Evaluasi Kelayakan & Survei Lapangan',
            'description' => 'Penilaian substansi program, kewajaran anggaran, dan peninjauan faktual di lapangan.',
            'start_date' => $evalStart?->toDateString(),
            'end_date' => $evalEnd?->toDateString(),
            'status' => $this->resolveStageStatus($now, $evalStart, $evalEnd),
        ];

        // 4. Tahap Penetapan & Penerbitan SK Hibah
        $decStart = $evalEnd ? (clone $evalEnd)->addDay() : null;
        $decEnd = $decStart ? (clone $decStart)->addDays(7) : null;
        $stages[] = [
            'stage_code' => 'DECISION',
            'stage_name' => 'Penetapan Keputusan & Penerbitan Surat Keputusan (SK)',
            'description' => 'Perangkingan resmi dan pengesahan daftar penerima hibah melalui Surat Keputusan Pejabat Pembina.',
            'start_date' => $decStart?->toDateString(),
            'end_date' => $decEnd?->toDateString(),
            'status' => $this->resolveStageStatus($now, $decStart, $decEnd),
        ];

        // 5. Tahap Penyaluran Dana
        $disbStart = $decEnd ? (clone $decEnd)->addDay() : null;
        $disbEnd = $disbStart ? (clone $disbStart)->addDays(30) : null;
        $stages[] = [
            'stage_code' => 'DISBURSEMENT',
            'stage_name' => 'Pencairan & Penyaluran Dana Hibah',
            'description' => 'Proses transfer dana bantuan hibah ke rekening resmi lembaga/organisasi penerima.',
            'start_date' => $disbStart?->toDateString(),
            'end_date' => $disbEnd?->toDateString(),
            'status' => $this->resolveStageStatus($now, $disbStart, $disbEnd),
        ];

        // 6. Tahap Pelaksanaan & Pertanggungjawaban (LPJ)
        $lpjStart = $disbStart;
        $lpjEnd = $regStart ? (clone $regStart)->endOfYear() : null;
        $stages[] = [
            'stage_code' => 'LPJ_SUBMISSION',
            'stage_name' => 'Pelaksanaan Kegiatan & Pelaporan LPJ',
            'description' => 'Penyelesaian realisasi program dan pelaporan pertanggungjawaban penggunaan dana.',
            'start_date' => $lpjStart?->toDateString(),
            'end_date' => $lpjEnd?->toDateString(),
            'status' => $this->resolveStageStatus($now, $lpjStart, $lpjEnd),
        ];

        return $stages;
    }

    /**
     * Get public document requirements metadata for a grant program.
     */
    public function getPublicProgramDocuments(GrantProgram $program): array
    {
        if (! $program->is_active) {
            abort(404, 'Program hibah tidak ditemukan atau belum aktif.');
        }

        return $program->documentRequirements()
            ->where('is_active', true)
            ->with(['documentType', 'requirement'])
            ->orderBy('sort_order')
            ->get()
            ->map(function ($docReq) {
                return [
                    'id' => $docReq->id,
                    'requirement_id' => $docReq->requirement_id,
                    'requirement_name' => $docReq->requirement?->name ?: 'Persyaratan Dokumen',
                    'requirement_code' => $docReq->requirement?->code,
                    'document_type_id' => $docReq->document_type_id,
                    'document_type_name' => $docReq->documentType?->name ?: 'Dokumen Lampiran',
                    'document_type_code' => $docReq->documentType?->code,
                    'scope' => $docReq->scope ?: 'proposal',
                    'is_mandatory' => (bool) $docReq->is_mandatory,
                    'maximum_files' => (int) ($docReq->maximum_files ?? 1),
                    'description' => $docReq->requirement?->description,
                ];
            })
            ->toArray();
    }

    /**
     * Helper to resolve status of a timeline stage.
     */
    protected function resolveStageStatus(CarbonInterface $now, ?CarbonInterface $start, ?CarbonInterface $end): string
    {
        if (! $start && ! $end) {
            return 'scheduled';
        }

        if ($start && $now->lt($start)) {
            return 'upcoming';
        }

        if ($end && $now->gt($end)) {
            return 'completed';
        }

        return 'active';
    }
}
