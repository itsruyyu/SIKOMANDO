<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReplaceProposalDocumentRequest;
use App\Http\Requests\Api\V1\StoreProposalDocumentRequest;
use App\Http\Resources\Api\V1\ProposalDocumentResource;
use App\Http\Resources\Api\V1\ProposalDocumentVersionResource;
use App\Http\Responses\ApiResponse;
use App\Models\Proposal;
use App\Models\ProposalDocument;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProposalDocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService
    ) {}

    public function index(Proposal $proposal): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [ProposalDocument::class, $proposal]);

        $documents = $proposal->documents()
            ->with(['documentType', 'uploader', 'versions.creator'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return ProposalDocumentResource::collection($documents)
            ->additional([
                'success' => true,
                'message' => 'Daftar dokumen proposal berhasil diambil.',
            ]);
    }

    public function store(Proposal $proposal, StoreProposalDocumentRequest $request): JsonResponse
    {
        $this->authorize('create', [ProposalDocument::class, $proposal]);

        $file = $request->file('file');
        $document = $this->documentService->uploadProposalDocument(
            proposal: $proposal,
            actor: $request->user(),
            data: $request->validated(),
            file: $file
        );

        return (new ProposalDocumentResource($document))
            ->additional([
                'success' => true,
                'message' => 'Dokumen proposal berhasil diunggah.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Proposal $proposal, ProposalDocument $document): JsonResponse
    {
        $this->ensureDocumentBelongsToProposal($proposal, $document);
        $this->authorize('view', $document);

        $document->load(['documentType', 'uploader', 'versions.creator', 'verifier']);

        return (new ProposalDocumentResource($document))
            ->additional([
                'success' => true,
                'message' => 'Detail dokumen proposal berhasil diambil.',
            ])
            ->response();
    }

    public function download(Proposal $proposal, ProposalDocument $document, Request $request): StreamedResponse
    {
        $this->ensureDocumentBelongsToProposal($proposal, $document);
        $this->authorize('download', $document);

        $versionNumber = $request->query('version') ? (int) $request->query('version') : null;

        return $this->documentService->downloadProposalDocument(
            document: $document,
            actor: $request->user(),
            versionNumber: $versionNumber
        );
    }

    public function replace(
        Proposal $proposal,
        ProposalDocument $document,
        ReplaceProposalDocumentRequest $request
    ): JsonResponse {
        $this->ensureDocumentBelongsToProposal($proposal, $document);
        $this->authorize('update', $document);

        $file = $request->file('file');
        $updated = $this->documentService->replaceProposalDocument(
            document: $document,
            actor: $request->user(),
            data: $request->validated(),
            file: $file
        );

        return (new ProposalDocumentResource($updated))
            ->additional([
                'success' => true,
                'message' => 'Dokumen proposal berhasil diperbarui ke versi baru.',
            ])
            ->response();
    }

    public function versions(Proposal $proposal, ProposalDocument $document): AnonymousResourceCollection
    {
        $this->ensureDocumentBelongsToProposal($proposal, $document);
        $this->authorize('view', $document);

        $versions = $document->versions()
            ->with('creator')
            ->orderByDesc('version_number')
            ->get();

        return ProposalDocumentVersionResource::collection($versions)
            ->additional([
                'success' => true,
                'message' => 'Riwayat versi dokumen berhasil diambil.',
            ]);
    }

    public function destroy(Proposal $proposal, ProposalDocument $document, Request $request): JsonResponse
    {
        $this->ensureDocumentBelongsToProposal($proposal, $document);
        $this->authorize('delete', $document);

        $this->documentService->deleteProposalDocument(
            document: $document,
            actor: $request->user(),
            reason: $request->input('reason')
        );

        return ApiResponse::success(null, 'Dokumen proposal berhasil dihapus.');
    }

    protected function ensureDocumentBelongsToProposal(Proposal $proposal, ProposalDocument $document): void
    {
        if ($document->proposal_id !== $proposal->id) {
            abort(404, 'Dokumen tidak ditemukan pada proposal tersebut.');
        }
    }
}
