<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SignatureStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DigitalSignatureResource;
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
     * List all signature documents (Registry / Daftar Berkas TTD).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DigitalSignature::class);

        $user = $request->user();
        $query = DigitalSignature::with(['profile', 'signable', 'signer:id,name,email', 'requester:id,name,email'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('signable_type'), fn ($q, $type) => $q->where('signable_type', 'like', "%{$type}%"))
            ->when($request->query('from'), fn ($q, $from) => $q->whereDate('signed_at', '>=', $from))
            ->when($request->query('to'), fn ($q, $to) => $q->whereDate('signed_at', '<=', $to));

        if (! $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR'])) {
            $query->where('signer_id', $user->id);
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $signatures = $query->latest('created_at')->paginate($perPage);

        return ApiResponse::paginated(
            paginator: $signatures,
            resourceClass: DigitalSignatureResource::class,
            message: 'Daftar berkas tanda tangan berhasil diambil.'
        );
    }

    /**
     * List pending signature requests for current user or all if admin.
     */
    public function pending(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DigitalSignature::class);

        $user = $request->user();
        $query = DigitalSignature::with(['profile', 'signable', 'requester:id,name,email'])
            ->where('status', SignatureStatus::PENDING_SIGNATURE);

        if (! $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO'])) {
            $query->where('signer_id', $user->id);
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $signatures = $query->latest('created_at')->paginate($perPage);

        return ApiResponse::paginated(
            paginator: $signatures,
            resourceClass: DigitalSignatureResource::class,
            message: 'Daftar dokumen menunggu tanda tangan berhasil diambil.'
        );
    }

    /**
     * Show digital signature detail.
     */
    public function show(DigitalSignature $signature): JsonResponse
    {
        $this->authorize('view', $signature);

        return ApiResponse::success(
            data: $signature->load(['profile', 'signable', 'signer:id,name,email', 'requester:id,name,email']),
            data: new DigitalSignatureResource($signature->load(['profile', 'signable', 'signer:id,name,email', 'requester:id,name,email'])),
            message: 'Detail tanda tangan digital berhasil diambil.'
        );
    }

    /**
     * Request signature on an eligible document or entity.
     */
    public function requestSignature(Request $request): JsonResponse
    {
        $this->authorize('requestSignature', DigitalSignature::class);

        $validated = $request->validate([
            'signable_type' => ['required', 'string', 'in:Decision,Receipt,Handover,Proposal,LpjSubmission'],
            'signable_id' => ['required', 'uuid'],
            'signer_id' => ['required', 'uuid', 'exists:users,id'],
            'document_path' => ['required', 'string'],
            'passphrase' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
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
            notes: $validated['notes'] ?? null
        );

        return ApiResponse::created(
            data: new DigitalSignatureResource($signature),
            message: 'Permintaan tanda tangan digital berhasil dibuat.'
        );
    }

    /**
     * Perform digital signing of the document.
     */
    public function sign(Request $request, DigitalSignature $signature): JsonResponse
    {
        $this->authorize('sign', $signature);

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
            data: new DigitalSignatureResource($signed),
            message: 'Dokumen berhasil ditandatangani secara digital dengan verifikasi resmi.'
        );
    }

    /**
     * Reject signature request.
     */
    public function reject(Request $request, DigitalSignature $signature): JsonResponse
    {
        $this->authorize('reject', $signature);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $rejected = $this->signatureService->reject(
            signature: $signature,
            actor: $request->user(),
            reason: $validated['reason']
        );

        return ApiResponse::success(
            data: new DigitalSignatureResource($rejected),
            message: 'Permintaan tanda tangan digital ditolak.'
        );
    }

    /**
     * Revoke signed document signature.
     */
    public function revoke(Request $request, DigitalSignature $signature): JsonResponse
    {
        $this->authorize('revoke', $signature);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $revoked = $this->signatureService->revoke(
            signature: $signature,
            actor: $request->user(),
            reason: $validated['reason']
        );

        return ApiResponse::success(
            data: new DigitalSignatureResource($revoked),
            message: 'Tanda tangan digital berhasil dicabut (REVOKED).'
        );
    }

    /**
     * Get QR details for a digital signature.
     */
    public function qr(DigitalSignature $signature): JsonResponse
    {
        $this->authorize('view', $signature);

        $signable = $signature->signable;
        $qr = $signable && method_exists($signable, 'qrIdentity') ? $signable->qrIdentity : null;

        return ApiResponse::success([
            'token' => $qr?->token,
            'verification_url' => $qr?->verification_url,
            'status' => $qr?->status?->value,
            'expires_at' => $qr?->expires_at?->toISOString(),
            'valid_from' => $signature->valid_from?->toISOString(),
            'valid_until' => $signature->valid_until?->toISOString(),
            'is_expired' => $signature->is_expired,
            'signer_name' => $signature->signer_name,
            'signer_position' => $signature->signer_position,
            'signed_at' => $signature->signed_at?->toISOString(),
            'document_hash' => $signature->document_hash,
        ], 'Informasi QR tanda tangan berhasil diambil.');
    }
}
