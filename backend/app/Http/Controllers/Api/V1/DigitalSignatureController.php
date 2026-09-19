<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SignatureStatus;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Decision;
use App\Models\DigitalSignature;
use App\Models\Handover;
use App\Models\LpjSubmission;
use App\Models\Proposal;
use App\Models\Receipt;
use App\Services\DigitalSignatureService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DigitalSignatureController extends Controller
{
    public function __construct(
        protected DigitalSignatureService $signatureService
    ) {}

    /**
     * List pending signature requests for current user or all if admin.
     */
    public function pending(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = DigitalSignature::with(['profile', 'signable', 'requester:id,name,email'])
            ->where('status', SignatureStatus::PENDING_SIGNATURE);

        if (! $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO'])) {
            $query->where('signer_id', $user->id);
        }

        $signatures = $query->latest('created_at')->paginate((int) $request->input('per_page', 15));

        return ApiResponse::success(
            data: $signatures,
            message: 'Daftar dokumen menunggu tanda tangan berhasil diambil.'
        );
    }

    /**
     * Show digital signature detail.
     */
    public function show(DigitalSignature $signature): JsonResponse
    {
        return ApiResponse::success(
            data: $signature->load(['profile', 'signable', 'signer:id,name,email', 'requester:id,name,email']),
            message: 'Detail tanda tangan digital berhasil diambil.'
        );
    }

    /**
     * Request signature on an eligible document or entity.
     */
    public function requestSignature(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'signable_type' => ['required', 'string', 'in:Decision,Receipt,Handover,Proposal,LpjSubmission'],
            'signable_id' => ['required', 'uuid'],
            'signer_id' => ['required', 'uuid', 'exists:users,id'],
            'document_path' => ['required', 'string'],
            'passphrase' => ['nullable', 'string'],
        ]);

        $modelClass = match ($validated['signable_type']) {
            'Decision' => Decision::class,
            'Receipt' => Receipt::class,
            'Handover' => Handover::class,
            'Proposal' => Proposal::class,
            'LpjSubmission' => LpjSubmission::class,
            default => throw ValidationException::withMessages(['signable_type' => 'Tipe entitas tidak valid untuk penandatanganan.']),
        };

        /** @var Model $signable */
        $signable = $modelClass::findOrFail($validated['signable_id']);
        $signer = \App\Models\User::findOrFail($validated['signer_id']);

        $signature = $this->signatureService->requestSignature(
            signable: $signable,
            signer: $signer,
            requester: $request->user(),
            documentPath: $validated['document_path'] ?? null,
            notes: $request->input('notes')
        );

        return ApiResponse::created(
            data: $signature,
            message: 'Permintaan tanda tangan digital berhasil dibuat.'
        );
    }

    /**
     * Perform digital signing of the document.
     */
    public function sign(Request $request, DigitalSignature $signature): JsonResponse
    {
        $validated = $request->validate([
            'passphrase' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $signed = $this->signatureService->sign(
            signature: $signature,
            actor: $request->user(),
            notes: $validated['notes'] ?? null
        );

        return ApiResponse::success(
            data: $signed,
            message: 'Dokumen berhasil ditandatangani secara digital dengan verifikasi kriptografis.'
        );
    }

    /**
     * Reject signature request.
     */
    public function reject(Request $request, DigitalSignature $signature): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $rejected = $this->signatureService->reject(
            signature: $signature,
            actor: $request->user(),
            reason: $validated['reason']
        );

        return ApiResponse::success(
            data: $rejected,
            message: 'Permintaan tanda tangan digital ditolak.'
        );
    }

    /**
     * Revoke signed document signature.
     */
    public function revoke(Request $request, DigitalSignature $signature): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $revoked = $this->signatureService->revoke(
            signature: $signature,
            actor: $request->user(),
            reason: $validated['reason']
        );

        return ApiResponse::success(
            data: $revoked,
            message: 'Tanda tangan digital berhasil dicabut (REVOKED).'
        );
    }
}
