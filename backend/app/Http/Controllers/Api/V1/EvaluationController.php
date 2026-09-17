<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CompleteEvaluationRequest;
use App\Http\Requests\Api\V1\StoreEvaluationRequest;
use App\Http\Requests\Api\V1\UpdateEvaluationItemRequest;
use App\Http\Resources\Api\V1\EvaluationResource;
use App\Models\Evaluation;
use App\Models\EvaluationItem;
use App\Models\Proposal;
use App\Services\EvaluationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EvaluationController extends Controller
{
    public function __construct(
        private readonly EvaluationService $evaluationService,
    ) {}

    public function index(
        Proposal $proposal
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [Evaluation::class, $proposal]);

        $evaluations = $this->evaluationService
            ->paginateForProposal($proposal);

        return EvaluationResource::collection($evaluations);
    }

    public function store(
        StoreEvaluationRequest $request,
        Proposal $proposal
    ): JsonResponse {
        $this->authorize('create', [Evaluation::class, $proposal]);

        $evaluation = $this->evaluationService->create(
            proposal: $proposal,
            user: $request->user(),
            data: $request->validated(),
        );

        return (new EvaluationResource(
            $evaluation->load([
                'proposal',
                'evaluator',
                'items.criteria',
            ])
        ))
            ->response()
            ->setStatusCode(201);
    }

    public function show(
        Proposal $proposal,
        Evaluation $evaluation
    ): EvaluationResource {
        $this->ensureEvaluationBelongsToProposal(
            proposal: $proposal,
            evaluation: $evaluation,
        );

        $this->authorize('view', $evaluation);

        $evaluation = $this->evaluationService->findForProposal(
            proposal: $proposal,
            evaluation: $evaluation,
        );

        return new EvaluationResource($evaluation);
    }

    public function updateItem(
        UpdateEvaluationItemRequest $request,
        Proposal $proposal,
        Evaluation $evaluation,
        EvaluationItem $item
    ): EvaluationResource {
        $this->ensureEvaluationBelongsToProposal(
            proposal: $proposal,
            evaluation: $evaluation,
        );

        $this->ensureItemBelongsToEvaluation(
            evaluation: $evaluation,
            item: $item,
        );

        $this->authorize('update', $evaluation);

        $evaluation = $this->evaluationService->updateItem(
            evaluation: $evaluation,
            item: $item,
            data: $request->validated(),
        );

        return new EvaluationResource(
            $evaluation->load([
                'proposal',
                'evaluator',
                'items.criteria',
            ])
        );
    }

    public function complete(
        CompleteEvaluationRequest $request,
        Proposal $proposal,
        Evaluation $evaluation
    ): EvaluationResource {
        $this->ensureEvaluationBelongsToProposal(
            proposal: $proposal,
            evaluation: $evaluation,
        );

        $this->authorize('complete', $evaluation);

        $evaluation = $this->evaluationService->complete(
            evaluation: $evaluation,
            data: $request->validated(),
        );

        return new EvaluationResource(
            $evaluation->load([
                'proposal',
                'evaluator',
                'items.criteria',
            ])
        );
    }

    private function ensureEvaluationBelongsToProposal(
        Proposal $proposal,
        Evaluation $evaluation
    ): void {
        abort_unless(
            (string) $evaluation->proposal_id === (string) $proposal->id,
            404,
            'Evaluation tidak ditemukan pada proposal tersebut.'
        );
    }

    private function ensureItemBelongsToEvaluation(
        Evaluation $evaluation,
        EvaluationItem $item
    ): void {
        abort_unless(
            (string) $item->evaluation_id === (string) $evaluation->id,
            404,
            'Evaluation item tidak ditemukan pada evaluation tersebut.'
        );
    }
}
