<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\QrVerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\QrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicQrVerificationController extends Controller
{
    public function __construct(
        protected QrService $qrService
    ) {}

    /**
     * Verify authenticity of a QR token publicly without disclosing private personal info.
     */
    public function verify(Request $request, string $token): JsonResponse
    {
        $result = $this->qrService->verifyToken(
            token: $token,
            actor: $request->user('sanctum'),
            ip: $request->ip(),
            userAgent: $request->userAgent(),
            context: 'public_web'
        );

        if ($result['status'] === QrVerificationStatus::NOT_FOUND->value) {
            return ApiResponse::error(
                message: $result['message'],
                status: 404,
                errors: $result
            );
        }

        return ApiResponse::success(
            data: $result,
            message: $result['message']
        );
    }
}

