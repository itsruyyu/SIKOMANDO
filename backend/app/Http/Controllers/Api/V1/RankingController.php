<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FinalizeRankingRequest;
use App\Http\Requests\Api\V1\GenerateRankingRequest;
use App\Http\Requests\Api\V1\ReviewRankingRequest;
use App\Http\Resources\Api\V1\RankingResource;
use App\Http\Resources\Api\V1\RecommendationResource;
use App\Http\Responses\ApiResponse;
use App\Models\GrantProgram;
use App\Models\Proposal;
use App\Models\Ranking;
use App\Models\Recommendation;
use App\Services\RankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RankingController extends Controller
{
    public function __construct(
        private readonly RankingService $rankingService,
    ) {}

    public function preview(
        GrantProgram $grantProgram,
    ): JsonResponse {
        $this->authorize('preview', [Ranking::class, $grantProgram]);

        $previewData = $this->rankingService->preview($grantProgram);

        return ApiResponse::success(
            $previewData,
            'Hasil preview perangkingan berhasil diambil.'
        );
    }

    public function generate(
        GenerateRankingRequest $request,
        GrantProgram $grantProgram,
    ): JsonResponse {
        $this->authorize('generate', [Ranking::class, $grantProgram]);

        $ranking = $this->rankingService->generate(
            grantProgram: $grantProgram,
            actor: $request->user(),
            data: $request->validated(),
        );

        return (new RankingResource($ranking))
            ->additional([
                'success' => true,
                'message' => 'Perangkingan program berhasil dihasilkan.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function index(
        GrantProgram $grantProgram,
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [Ranking::class, $grantProgram]);

        $rankings = $this->rankingService->paginateForProgram($grantProgram);

        return RankingResource::collection($rankings)
            ->additional([
                'success' => true,
                'message' => 'Daftar riwayat perangkingan program berhasil diambil.',
            ]);
    }

    public function show(
        GrantProgram $grantProgram,
        Ranking $ranking,
    ): RankingResource {
        $this->ensureRankingBelongsToProgram($grantProgram, $ranking);

        $this->authorize('view', $ranking);

        $ranking = $this->rankingService->findForProgram($grantProgram, $ranking);

        return (new RankingResource($ranking))
            ->additional([
                'success' => true,
                'message' => 'Detail hasil perangkingan berhasil diambil.',
            ]);
    }

    public function review(
        ReviewRankingRequest $request,
        GrantProgram $grantProgram,
        Ranking $ranking,
    ): RankingResource {
        $this->ensureRankingBelongsToProgram($grantProgram, $ranking);

        $this->authorize('review', $ranking);

        $ranking = $this->rankingService->review(
            ranking: $ranking,
            actor: $request->user(),
            data: $request->validated(),
        );

        return (new RankingResource($ranking))
            ->additional([
                'success' => true,
                'message' => 'Hasil perangkingan berhasil ditinjau.',
            ]);
    }

    public function finalize(
        FinalizeRankingRequest $request,
        GrantProgram $grantProgram,
        Ranking $ranking,
    ): RankingResource {
        $this->ensureRankingBelongsToProgram($grantProgram, $ranking);

        $this->authorize('finalize', $ranking);

        $ranking = $this->rankingService->finalize(
            ranking: $ranking,
            actor: $request->user(),
            data: $request->validated(),
        );

        return (new RankingResource($ranking))
            ->additional([
                'success' => true,
                'message' => 'Hasil perangkingan berhasil difinalisasi dan rekomendasi resmi telah ditetapkan.',
            ]);
    }

    public function regenerate(
        GenerateRankingRequest $request,
        GrantProgram $grantProgram,
        Ranking $ranking,
    ): RankingResource {
        $this->ensureRankingBelongsToProgram($grantProgram, $ranking);

        $this->authorize('regenerate', $ranking);

        $ranking = $this->rankingService->regenerate(
            ranking: $ranking,
            actor: $request->user(),
            data: $request->validated(),
        );

        return (new RankingResource($ranking))
            ->additional([
                'success' => true,
                'message' => 'Hasil perangkingan berhasil diperbarui ulang (regenerate).',
            ]);
    }

    public function proposalRecommendation(
        Proposal $proposal,
    ): RecommendationResource {
        $this->authorize('view', [Recommendation::class, $proposal]);

        $recommendation = $this->rankingService->getProposalRecommendation($proposal);

        abort_unless(
            $recommendation !== null,
            404,
            'Rekomendasi untuk proposal ini belum tersedia.'
        );

        return (new RecommendationResource($recommendation))
            ->additional([
                'success' => true,
                'message' => 'Hasil rekomendasi proposal berhasil diambil.',
            ]);
    }

    private function ensureRankingBelongsToProgram(
        GrantProgram $grantProgram,
        Ranking $ranking,
    ): void {
        abort_unless(
            (string) $ranking->grant_program_id === (string) $grantProgram->id,
            404,
            'Data perangkingan tidak ditemukan pada program hibah tersebut.'
        );
    }
}
