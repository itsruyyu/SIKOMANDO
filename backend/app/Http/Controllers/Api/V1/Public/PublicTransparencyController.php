<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\GrantProgram;
use App\Services\PublicPortalService;
use Illuminate\Http\JsonResponse;

class PublicTransparencyController extends Controller
{
    public function __construct(
        protected PublicPortalService $publicPortalService
    ) {}

    /**
     * Display overall public transparency metrics across all active grant programs.
     */
    public function index(): JsonResponse
    {
        $transparency = $this->publicPortalService->getPublicTransparencySummary();

        return ApiResponse::success(
            data: $transparency,
            message: 'Data transparansi publik berhasil diambil.'
        );
    }

    /**
     * Display transparency metrics for a specific active grant program.
     */
    public function show(GrantProgram $grantProgram): JsonResponse
    {
        $metrics = $this->publicPortalService->getPublicTransparencyForProgram($grantProgram);

        return ApiResponse::success(
            data: $metrics,
            message: 'Data transparansi program hibah berhasil diambil.'
        );
    }
}
