<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CloseProposalRequest;
use App\Http\Requests\Api\V1\RejectLpjRequest;
use App\Http\Requests\Api\V1\RequestLpjRevisionRequest;
use App\Http\Requests\Api\V1\ReviewLpjRequest;
use App\Http\Requests\Api\V1\StoreLpjRequest;
use App\Http\Requests\Api\V1\UpdateLpjRequest;
use App\Http\Requests\Api\V1\UploadLpjDocumentRequest;
use App\Http\Resources\Api\V1\LpjDocumentResource;
use App\Http\Resources\Api\V1\LpjSubmissionResource;
use App\Http\Resources\Api\V1\ProposalResource;
use App\Http\Responses\ApiResponse;
use App\Models\LpjDocument;
use App\Models\LpjSubmission;
use App\Models\Proposal;
use App\Services\DocumentService;
use App\Services\LpjService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LpjController extends Controller
{
    public function __construct(
        protected LpjService $lpjService,
        protected DocumentService $documentService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', LpjSubmission::class);

        $filters = $request->only(['status', 'proposal_id', 'organization_id']);
        $lpjs = $this->lpjService->paginateLpjs($filters, (int) $request->input('per_page', 15));

        return LpjSubmissionResource::collection($lpjs)->additional([
            'success' => true,
            'message' => 'Daftar LPJ berhasil diambil.',
        ]);
    }

    public function proposalLpjs(Proposal $proposal): AnonymousResourceCollection
    {
        $this->authorize('viewProposalLpjs', [LpjSubmission::class, $proposal]);

        $lpjs = $this->lpjService->paginateLpjs(['proposal_id' => $proposal->id]);

        return LpjSubmissionResource::collection($lpjs)->additional([
            'success' => true,
            'message' => 'Daftar LPJ proposal berhasil diambil.',
        ]);
    }

    public function show(LpjSubmission $lpj): LpjSubmissionResource
    {
        $this->authorize('view', $lpj);

        $loaded = $this->lpjService->findLpj($lpj->id);

        return (new LpjSubmissionResource($loaded))->additional([
            'success' => true,
            'message' => 'Detail LPJ berhasil diambil.',
        ]);
    }

    public function store(Proposal $proposal, StoreLpjRequest $request): JsonResponse
    {
        $this->authorize('create', [LpjSubmission::class, $proposal]);

        $submission = $this->lpjService->createSubmission(
            $proposal,
            $request->user(),
            $request->validated()
        );

        return (new LpjSubmissionResource($submission))
            ->additional([
                'success' => true,
                'message' => 'LPJ berhasil dibuat.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(LpjSubmission $lpj, UpdateLpjRequest $request): LpjSubmissionResource
    {
        $this->authorize('update', $lpj);

        $updated = $this->lpjService->updateSubmission(
            $lpj,
            $request->user(),
            $request->validated()
        );

        return (new LpjSubmissionResource($updated))->additional([
            'success' => true,
            'message' => 'LPJ berhasil diperbarui.',
        ]);
    }

    public function submit(LpjSubmission $lpj, Request $request): LpjSubmissionResource
    {
        $this->authorize('submit', $lpj);

        $submitted = $this->lpjService->submitSubmission(
            $lpj,
            $request->user()
        );

        return (new LpjSubmissionResource($submitted))->additional([
            'success' => true,
            'message' => 'LPJ berhasil diajukan untuk verifikasi.',
        ]);
    }

    public function review(LpjSubmission $lpj, ReviewLpjRequest $request): LpjSubmissionResource
    {
        $this->authorize('review', $lpj);

        $reviewed = $this->lpjService->reviewSubmission(
            $lpj,
            $request->user(),
            $request->validated()
        );

        return (new LpjSubmissionResource($reviewed))->additional([
            'success' => true,
            'message' => 'LPJ sedang dalam peninjauan verifikator.',
        ]);
    }

    public function requestRevision(LpjSubmission $lpj, RequestLpjRevisionRequest $request): LpjSubmissionResource
    {
        $this->authorize('requestRevision', $lpj);

        $revised = $this->lpjService->requestRevision(
            $lpj,
            $request->user(),
            $request->validated()
        );

        return (new LpjSubmissionResource($revised))->additional([
            'success' => true,
            'message' => 'Permintaan revisi LPJ berhasil dikirimkan.',
        ]);
    }

    public function approve(LpjSubmission $lpj, Request $request): LpjSubmissionResource
    {
        $this->authorize('approve', $lpj);

        $approved = $this->lpjService->approveSubmission(
            $lpj,
            $request->user(),
            $request->all()
        );

        return (new LpjSubmissionResource($approved))->additional([
            'success' => true,
            'message' => 'LPJ berhasil disetujui.',
        ]);
    }

    public function reject(LpjSubmission $lpj, RejectLpjRequest $request): LpjSubmissionResource
    {
        $this->authorize('reject', $lpj);

        $rejected = $this->lpjService->rejectSubmission(
            $lpj,
            $request->user(),
            $request->validated()
        );

        return (new LpjSubmissionResource($rejected))->additional([
            'success' => true,
            'message' => 'LPJ berhasil ditolak.',
        ]);
    }

    public function finalize(LpjSubmission $lpj, Request $request): LpjSubmissionResource
    {
        $this->authorize('finalize', $lpj);

        $finalized = $this->lpjService->finalizeSubmission(
            $lpj,
            $request->user()
        );

        return (new LpjSubmissionResource($finalized))->additional([
            'success' => true,
            'message' => 'LPJ berhasil difinalisasi.',
        ]);
    }

    public function uploadDocument(LpjSubmission $lpj, UploadLpjDocumentRequest $request): JsonResponse
    {
        $this->authorize('uploadDocument', $lpj);

        $doc = $this->lpjService->addDocument(
            $lpj,
            $request->user(),
            $request->validated()
        );

        return (new LpjDocumentResource($doc))
            ->additional([
                'success' => true,
                'message' => 'Dokumen bukti LPJ berhasil diunggah.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function downloadDocument(
        LpjSubmission $lpj,
        LpjDocument $document,
        Request $request
    ): StreamedResponse {
        if ($document->lpj_submission_id !== $lpj->id) {
            abort(404, 'Dokumen tidak ditemukan pada pengajuan LPJ tersebut.');
        }

        $this->authorize('view', $lpj);

        return $this->documentService->downloadLpjDocument(
            document: $document,
            actor: $request->user(),
        );
    }

    public function closingSummary(Proposal $proposal): JsonResponse
    {
        $this->authorize('viewProposalLpjs', [LpjSubmission::class, $proposal]);

        $summary = $this->lpjService->getClosingSummary($proposal);

        return ApiResponse::success($summary, 'Ringkasan kelayakan penutupan proposal berhasil diambil.');
    }

    public function closeProposal(Proposal $proposal, CloseProposalRequest $request): JsonResponse
    {
        $this->authorize('close', [LpjSubmission::class, $proposal]);

        $closed = $this->lpjService->closeProposal(
            $proposal,
            $request->user(),
            $request->validated()
        );

        return (new ProposalResource($closed))
            ->additional([
                'success' => true,
                'message' => 'Proposal telah resmi selesai dan ditutup.',
            ])
            ->response();
    }
}
