<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreRevisionRequest;
use App\Http\Resources\Api\V1\ProposalResource;
use App\Http\Resources\Api\V1\RevisionResource;
use App\Models\Proposal;
use App\Models\Revision;
use App\Services\RevisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevisionController extends Controller
{
    public function __construct(
        private readonly RevisionService $revisionService
    ) {
    }

    public function index(
        Request $request,
        Proposal $proposal
    ) {
        $this->authorize('view', $proposal);

        $revisions = $proposal->revisions()
            ->with([
                'requester',
                'items',
            ])
            ->latest('revision_number')
            ->paginate(
                min(max($request->integer('per_page', 15), 1), 100)
            )
            ->withQueryString();

        return RevisionResource::collection($revisions)
            ->additional([
                'success' => true,
                'message' => 'Daftar revisi berhasil diambil.',
            ]);
    }

    public function store(
        StoreRevisionRequest $request,
        Proposal $proposal
    ): RevisionResource {
        $revision = $this->revisionService->create(
            proposal: $proposal,
            actor: $request->user(),
            reason: $request->validated('reason'),
            items: $request->validated('items'),
            requestId: $request->header('X-Request-ID'),
        );

        return (new RevisionResource($revision))
            ->additional([
                'success' => true,
                'message' => 'Permintaan revisi berhasil dibuat.',
            ]);
    }

    public function show(
        Request $request,
        Proposal $proposal,
        Revision $revision
    ): RevisionResource {
        $this->authorize('view', $proposal);

        abort_unless(
            $revision->proposal_id === $proposal->id,
            404
        );

        return (new RevisionResource(
            $revision->load([
                'requester',
                'items',
            ])
        ))->additional([
            'success' => true,
            'message' => 'Detail revisi berhasil diambil.',
        ]);
    }

    public function submit(
        Request $request,
        Proposal $proposal,
        Revision $revision
    ): ProposalResource {
        $this->authorize('submit', $proposal);

        abort_unless(
            $revision->proposal_id === $proposal->id,
            404
        );

        $updatedProposal = $this->revisionService->submit(
            revision: $revision,
            actor: $request->user(),
            requestId: $request->header('X-Request-ID'),
        );

        return (new ProposalResource($updatedProposal))
            ->additional([
                'success' => true,
                'message' => 'Proposal berhasil diajukan ulang setelah revisi.',
            ]);
    }
}