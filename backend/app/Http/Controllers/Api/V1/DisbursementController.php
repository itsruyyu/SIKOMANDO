<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ApproveDisbursementRequest;
use App\Http\Requests\Api\V1\RecordDisbursementTransactionRequest;
use App\Http\Requests\Api\V1\StoreDisbursementPlanRequest;
use App\Http\Requests\Api\V1\VerifyDisbursementRequest;
use App\Http\Resources\Api\V1\DisbursementPlanResource;
use App\Http\Resources\Api\V1\DisbursementResource;
use App\Http\Resources\Api\V1\DisbursementTransactionResource;
use App\Http\Responses\ApiResponse;
use App\Models\Disbursement;
use App\Models\Proposal;
use App\Services\DisbursementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DisbursementController extends Controller
{
    public function __construct(
        protected DisbursementService $disbursementService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Disbursement::class);

        $filters = $request->only(['status', 'proposal_id', 'grant_program_id']);
        $disbursements = $this->disbursementService->paginateDisbursements($filters, (int) $request->input('per_page', 15));

        return DisbursementResource::collection($disbursements)->additional([
            'success' => true,
            'message' => 'Daftar pencairan dana berhasil diambil.',
        ]);
    }

    public function show(Disbursement $disbursement): DisbursementResource
    {
        $this->authorize('view', $disbursement);

        $loaded = $this->disbursementService->findDisbursement($disbursement->id);

        return (new DisbursementResource($loaded))->additional([
            'success' => true,
            'message' => 'Detail pencairan dana berhasil diambil.',
        ]);
    }

    public function storePlan(Proposal $proposal, StoreDisbursementPlanRequest $request): JsonResponse
    {
        $this->authorize('create', [Disbursement::class, $proposal]);

        $plan = $this->disbursementService->createPlan(
            $proposal,
            $request->user(),
            $request->validated()
        );

        return (new DisbursementPlanResource($plan))
            ->additional([
                'success' => true,
                'message' => 'Rencana pencairan dana berhasil dibuat.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function proposalDisbursements(Proposal $proposal): AnonymousResourceCollection
    {
        $this->authorize('viewSummary', [Disbursement::class, $proposal]);

        $disbursements = $this->disbursementService->paginateDisbursements(['proposal_id' => $proposal->id]);

        return DisbursementResource::collection($disbursements)->additional([
            'success' => true,
            'message' => 'Riwayat pencairan dana proposal berhasil diambil.',
        ]);
    }

    public function proposalSummary(Proposal $proposal): JsonResponse
    {
        $this->authorize('viewSummary', [Disbursement::class, $proposal]);

        $summary = $this->disbursementService->getDisbursementSummary($proposal);

        return ApiResponse::success($summary, 'Ringkasan pencairan dana proposal berhasil diambil.');
    }

    public function verify(Disbursement $disbursement, VerifyDisbursementRequest $request): DisbursementResource
    {
        $this->authorize('verify', $disbursement);

        $verified = $this->disbursementService->verifyDisbursement(
            $disbursement,
            $request->user(),
            $request->validated()
        );

        return (new DisbursementResource($verified))->additional([
            'success' => true,
            'message' => 'Pencairan dana berhasil diverifikasi.',
        ]);
    }

    public function approve(Disbursement $disbursement, ApproveDisbursementRequest $request): DisbursementResource
    {
        $this->authorize('approve', $disbursement);

        $approved = $this->disbursementService->approveDisbursement(
            $disbursement,
            $request->user(),
            $request->validated()
        );

        return (new DisbursementResource($approved))->additional([
            'success' => true,
            'message' => 'Pencairan dana berhasil disetujui.',
        ]);
    }

    public function recordTransaction(
        Disbursement $disbursement,
        RecordDisbursementTransactionRequest $request
    ): JsonResponse {
        $this->authorize('process', $disbursement);

        $transaction = $this->disbursementService->recordTransaction(
            $disbursement,
            $request->user(),
            $request->validated()
        );

        return (new DisbursementTransactionResource($transaction))
            ->additional([
                'success' => true,
                'message' => 'Transaksi pencairan dana berhasil dicatat dan dana telah disalurkan.',
            ])
            ->response()
            ->setStatusCode(201);
    }
}
