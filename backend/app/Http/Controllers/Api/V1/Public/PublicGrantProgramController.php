<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\PublicGrantProgramFilterRequest;
use App\Http\Resources\Api\V1\Public\PublicDocumentResource;
use App\Http\Resources\Api\V1\Public\PublicGrantProgramResource;
use App\Http\Responses\ApiResponse;
use App\Models\GrantProgram;
use App\Services\PublicPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicGrantProgramController extends Controller
{
    public function __construct(
        protected PublicPortalService $publicPortalService
    ) {}

    /**
     * Display a listing of publicly available grant programs.
     */
    public function index(PublicGrantProgramFilterRequest $request): AnonymousResourceCollection
    {
        $programs = $this->publicPortalService->getPublicGrantPrograms(
            filters: $request->validated(),
            perPage: (int) $request->input('per_page', 15)
        );

        return PublicGrantProgramResource::collection($programs)
            ->additional([
                'success' => true,
                'message' => 'Daftar program hibah publik berhasil diambil.',
            ]);
    }

    /**
     * Display the specified active grant program.
     */
    public function show(GrantProgram $grantProgram): JsonResponse
    {
        $program = $this->publicPortalService->getPublicGrantProgram($grantProgram);

        return (new PublicGrantProgramResource($program))
            ->additional([
                'success' => true,
                'message' => 'Detail program hibah publik berhasil diambil.',
            ])
            ->response();
    }

    /**
     * Display the public timeline stages of the specified grant program.
     */
    public function timeline(GrantProgram $grantProgram): JsonResponse
    {
        $timeline = $this->publicPortalService->getPublicProgramTimeline($grantProgram);

        return ApiResponse::success(
            data: $timeline,
            message: 'Tahapan timeline program hibah berhasil diambil.'
        );
    }

    /**
     * Display the public required documents metadata of the specified grant program.
     */
    public function documents(GrantProgram $grantProgram): JsonResponse
    {
        $documents = $this->publicPortalService->getPublicProgramDocuments($grantProgram);

        return ApiResponse::success(
            data: PublicDocumentResource::collection($documents)->resolve(),
            message: 'Informasi dokumen persyaratan program hibah berhasil diambil.'
        );
    }
}
