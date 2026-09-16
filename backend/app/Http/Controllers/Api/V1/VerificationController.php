<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreVerificationApiRequest;
use App\Http\Requests\Api\V1\UpdateVerificationItemApiRequest;
use App\Http\Resources\Api\V1\VerificationItemResource;
use App\Http\Resources\Api\V1\VerificationResource;
use App\Models\Proposal;
use App\Models\Verification;
use App\Models\VerificationItem;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class VerificationController extends Controller
{
    public function __construct(
        private readonly VerificationService $verificationService,
    ) {}

    /**
     * Display a listing of verifications for a proposal.
     */
    public function index(
        Request $request,
        Proposal $proposal,
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [Verification::class, $proposal]);

        $verifications = $proposal->verifications()
            ->with([
                'verifier:id,name,email',
                'items',
            ])
            ->latest()
            ->paginate(
                min((int) $request->integer('per_page', 15), 100)
            );

        return VerificationResource::collection($verifications)
            ->additional([
                'success' => true,
                'message' => 'Daftar verifikasi berhasil diambil.',
            ]);
    }

    /**
     * Store a newly created verification for a proposal.
     */
    public function store(
        StoreVerificationApiRequest $request,
        Proposal $proposal,
    ): JsonResponse {
        $this->authorize('create', [
            Verification::class,
            $proposal,
        ]);

        $verification = $this->verificationService->create(
            proposal: $proposal,
            verifierId: (string) $request->user()->id,
        );

        return (new VerificationResource(
            $verification->load([
                'verifier:id,name,email',
                'items',
            ])
        ))->additional([
            'success' => true,
            'message' => 'Verifikasi berhasil dibuat.',
        ])->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified verification.
     */
    public function show(
        Proposal $proposal,
        Verification $verification,
    ): VerificationResource {
        abort_unless(
            $verification->proposal_id === $proposal->id,
            Response::HTTP_NOT_FOUND
        );

        $this->authorize('view', $verification);

        return (new VerificationResource(
            $verification->load([
                'verifier:id,name,email',
                'items',
                'items.requirement',
                'items.documentType',
                'items.checker:id,name,email',
            ])
        ))->additional([
            'success' => true,
            'message' => 'Detail verifikasi berhasil diambil.',
        ]);
    }

    /**
     * Update a specific verification item.
     */
    public function updateItem(
        UpdateVerificationItemApiRequest $request,
        Proposal $proposal,
        Verification $verification,
        VerificationItem $item,
    ): VerificationItemResource {
        abort_unless(
            $verification->proposal_id === $proposal->id,
            Response::HTTP_NOT_FOUND
        );

        abort_unless(
            $item->verification_id === $verification->id,
            Response::HTTP_NOT_FOUND
        );

        $this->authorize('update', $verification);

        $updatedItem = $this->verificationService->updateItem(
            verification: $verification,
            item: $item,
            result: $request->string('result')->toString(),
            notes: $request->input('notes'),
        );

        return (new VerificationItemResource(
            $updatedItem->load([
                'requirement',
                'documentType',
                'checker:id,name,email',
            ])
        ))->additional([
            'success' => true,
            'message' => 'Item verifikasi berhasil diperbarui.',
        ]);
    }

    /**
     * Complete a verification.
     */
    public function complete(
        Request $request,
        Proposal $proposal,
        Verification $verification,
    ): VerificationResource {
        abort_unless(
            $verification->proposal_id === $proposal->id,
            Response::HTTP_NOT_FOUND
        );

        $this->authorize('complete', $verification);

        $verification = $this->verificationService->complete(
            $verification
        );

        return (new VerificationResource(
            $verification->load([
                'verifier:id,name,email',
                'items',
                'items.requirement',
                'items.documentType',
                'items.checker:id,name,email',
            ])
        ))->additional([
            'success' => true,
            'message' => 'Verifikasi berhasil diselesaikan.',
        ]);
    }
}
