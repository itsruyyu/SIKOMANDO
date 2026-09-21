<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\QrIdentity;
use App\Services\QrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrManagementController extends Controller
{
    public function __construct(
        protected QrService $qrService
    ) {}

    /**
     * Resolve a QR token into its internal full operational entity.
     */
    public function resolve(Request $request, string $token): JsonResponse
    {
        $qrIdentity = QrIdentity::where('token', $token)->firstOrFail();
        $this->authorize('resolve', $qrIdentity);

        $actor = $request->user();
        $resolved = $this->qrService->resolveToken(
            token: $token,
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return ApiResponse::success(
            data: $resolved,
            message: 'Informasi operasional QR berhasil diakses.'
        );
    }

    /**
     * Revoke an active QR identity.
     */
    public function revoke(Request $request, QrIdentity $qrIdentity): JsonResponse
    {
        $this->authorize('manage', $qrIdentity);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $revoked = $this->qrService->revoke(
            qr: $qrIdentity,
            actor: $request->user(),
            reason: $validated['reason']
        );

        return ApiResponse::success(
            data: $revoked,
            message: 'QR Code berhasil dicabut (REVOKED).'
        );
    }

    /**
     * Regenerate a superseded QR identity.
     */
    public function regenerate(Request $request, QrIdentity $qrIdentity): JsonResponse
    {
        $this->authorize('manage', $qrIdentity);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $newQr = $this->qrService->regenerate(
            oldQr: $qrIdentity,
            actor: $request->user(),
            reason: $validated['reason']
        );

        return ApiResponse::created(
            data: $newQr,
            message: 'QR Code baru berhasil dibuat menggantikan versi lama.'
        );
    }

    /**
     * View verification history and scan logs for a QR identity.
     */
    public function logs(Request $request, QrIdentity $qrIdentity): JsonResponse
    {
        $this->authorize('manage', $qrIdentity);

        $logs = $qrIdentity->verificationLogs()
            ->with('scanner:id,name,email')
            ->latest('created_at')
            ->paginate((int) $request->input('per_page', 15));

        return ApiResponse::paginated(
            paginator: $logs,
            message: 'Riwayat verifikasi dan pemindaian QR berhasil diambil.'
        );
    }
}

