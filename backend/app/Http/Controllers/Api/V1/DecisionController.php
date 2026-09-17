<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GenerateDecisionDocumentRequest;
use App\Http\Resources\Api\V1\DecisionDocumentResource;
use App\Http\Resources\Api\V1\DecisionResource;
use App\Models\Decision;
use App\Models\DecisionDocument;
use App\Models\Proposal;
use App\Services\ApprovalService;
use App\Services\DecisionDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DecisionController extends Controller
{
    public function __construct(
        protected ApprovalService $approvalService,
        protected DecisionDocumentService $decisionDocumentService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Decision::class);

        $filters = $request->only(['status', 'result', 'proposal_id', 'grant_program_id']);
        $decisions = $this->approvalService->paginateDecisions($filters, (int) $request->input('per_page', 15));

        return DecisionResource::collection($decisions)->additional([
            'success' => true,
            'message' => 'Daftar keputusan berhasil diambil.',
        ]);
    }

    public function show(Decision $decision): DecisionResource
    {
        $this->authorize('view', $decision);

        $loaded = $this->approvalService->findDecision($decision->id);

        return (new DecisionResource($loaded))->additional([
            'success' => true,
            'message' => 'Detail keputusan berhasil diambil.',
        ]);
    }

    public function proposalDecision(Proposal $proposal): DecisionResource
    {
        $this->authorize('viewProposalDecision', [Decision::class, $proposal]);

        $decision = $proposal->decisions()->latest('issued_at')->firstOrFail();

        $this->authorize('view', $decision);

        $loaded = $this->approvalService->findDecision($decision->id);

        return (new DecisionResource($loaded))->additional([
            'success' => true,
            'message' => 'Keputusan resmi proposal berhasil diambil.',
        ]);
    }

    public function generateDocument(
        Decision $decision,
        GenerateDecisionDocumentRequest $request
    ): JsonResponse {
        $this->authorize('generateDocument', $decision);

        $document = $this->decisionDocumentService->generateDocument(
            $decision,
            $request->user(),
            $request->validated()
        );

        return (new DecisionDocumentResource($document))
            ->additional([
                'success' => true,
                'message' => 'Dokumen SK resmi berhasil diterbitkan.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function showDocument(
        Decision $decision,
        DecisionDocument $document
    ): DecisionDocumentResource {
        $this->ensureDocumentBelongsToDecision($decision, $document);
        $this->authorize('view', $decision);

        return (new DecisionDocumentResource($document->load(['template', 'templateVersion', 'uploader'])))->additional([
            'success' => true,
            'message' => 'Metadata dokumen SK berhasil diambil.',
        ]);
    }

    public function downloadDocument(
        Decision $decision,
        DecisionDocument $document
    ): StreamedResponse {
        $this->ensureDocumentBelongsToDecision($decision, $document);
        $this->authorize('downloadDocument', [$decision, $document]);

        return $this->decisionDocumentService->downloadDocument($document);
    }

    private function ensureDocumentBelongsToDecision(Decision $decision, DecisionDocument $document): void
    {
        if ($document->decision_id !== $decision->id) {
            abort(404, 'Dokumen keputusan tidak ditemukan pada keputusan tersebut.');
        }
    }
}

