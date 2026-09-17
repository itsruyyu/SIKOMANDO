<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\PublicPortalService;
use Illuminate\Http\JsonResponse;

class PublicStatisticController extends Controller
{
    public function __construct(
        protected PublicPortalService $publicPortalService
    ) {}

    /**
     * Display detailed public aggregate statistics.
     */
    public function index(): JsonResponse
    {
        $statistics = $this->publicPortalService->getPublicStatistics();

        return ApiResponse::success(
            data: $statistics,
            message: 'Statistik publik SIKOMANDO berhasil diambil.'
        );
    }

    /**
     * Display summary public aggregate statistics.
     */
    public function summary(): JsonResponse
    {
        $summary = $this->publicPortalService->getPublicStatisticsSummary();

        return ApiResponse::success(
            data: $summary,
            message: 'Ringkasan statistik publik berhasil diambil.'
        );
    }
}

