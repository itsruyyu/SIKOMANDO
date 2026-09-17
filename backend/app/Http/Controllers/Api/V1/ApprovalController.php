<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ApproveProposalRequest;
use App\Http\Requests\Api\V1\RejectProposalRequest;
use App\Http\Requests\Api\V1\ReviewApprovalRequest;
use App\Http\Requests\Api\V1\SubmitApprovalRequest;
use App\Http\Resources\Api\V1\ApprovalResource;
use App\Models\Approval;
use App\Models\Proposal;
use App\Services\ApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApprovalController extends Controller
{
    public function __construct(
        protected ApprovalService $approvalService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Approval::class);

        $filters = $request->only(['status', 'proposal_id', 'grant_program_id']);
        $approvals = $this->approvalService->paginateApprovals($filters, (int) $request->input('per_page', 15));

        return ApprovalResource::collection($approvals)->additional([
            'success' => true,
            'message' => 'Daftar persetujuan berhasil diambil.',
        ]);
    }

    public function show(Approval $approval): ApprovalResource
    {
        $this->authorize('view', $approval);

        $loaded = $this->approvalService->findApproval($approval->id);

        return (new ApprovalResource($loaded))->additional([
            'success' => true,
            'message' => 'Detail persetujuan berhasil diambil.',
        ]);
    }

    public function store(Proposal $proposal, SubmitApprovalRequest $request): JsonResponse
    {
        $this->authorize('create', [Approval::class, $proposal]);

        $approval = $this->approvalService->submitForApproval(
            $proposal,
            $request->user(),
            $request->validated()
        );

        return (new ApprovalResource($approval))
            ->additional([
                'success' => true,
                'message' => 'Proposal berhasil diajukan untuk persetujuan.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function proposalApprovals(Proposal $proposal): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Approval::class);

        $approvals = $this->approvalService->paginateApprovals(['proposal_id' => $proposal->id]);

        return ApprovalResource::collection($approvals)->additional([
            'success' => true,
            'message' => 'Daftar riwayat persetujuan proposal berhasil diambil.',
        ]);
    }

    public function review(Approval $approval, ReviewApprovalRequest $request): ApprovalResource
    {
        $this->authorize('review', $approval);

        $reviewed = $this->approvalService->reviewApproval(
            $approval,
            $request->user(),
            $request->validated()
        );

        return (new ApprovalResource($reviewed))->additional([
            'success' => true,
            'message' => 'Persetujuan berhasil ditinjau.',
        ]);
    }

    public function approve(Approval $approval, ApproveProposalRequest $request): ApprovalResource
    {
        $this->authorize('approve', $approval);

        $approved = $this->approvalService->approve(
            $approval,
            $request->user(),
            $request->validated()
        );

        return (new ApprovalResource($approved))->additional([
            'success' => true,
            'message' => 'Proposal berhasil disetujui dan ketetapan keputusan diterbitkan.',
        ]);
    }

    public function reject(Approval $approval, RejectProposalRequest $request): ApprovalResource
    {
        $this->authorize('reject', $approval);

        $rejected = $this->approvalService->reject(
            $approval,
            $request->user(),
            $request->validated()
        );

        return (new ApprovalResource($rejected))->additional([
            'success' => true,
            'message' => 'Proposal telah ditolak pada proses persetujuan.',
        ]);
    }
}
