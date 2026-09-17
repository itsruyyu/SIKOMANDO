<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CompleteFieldSurveyRequest;
use App\Http\Requests\Api\V1\FillFieldSurveyResultRequest;
use App\Http\Requests\Api\V1\ReviewFieldSurveyRequest;
use App\Http\Requests\Api\V1\StoreFieldSurveyDocumentRequest;
use App\Http\Requests\Api\V1\StoreFieldSurveyFindingRequest;
use App\Http\Requests\Api\V1\StoreFieldSurveyRequest;
use App\Http\Requests\Api\V1\UpdateFieldSurveyItemRequest;
use App\Http\Requests\Api\V1\UpdateFieldSurveyScheduleRequest;
use App\Http\Resources\Api\V1\FieldSurveyDocumentResource;
use App\Http\Resources\Api\V1\FieldSurveyFindingResource;
use App\Http\Resources\Api\V1\FieldSurveyItemResource;
use App\Http\Resources\Api\V1\FieldSurveyResource;
use App\Http\Responses\ApiResponse;
use App\Models\FieldSurvey;
use App\Models\FieldSurveyItem;
use App\Models\Proposal;
use App\Services\FieldSurveyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FieldSurveyController extends Controller
{
    public function __construct(
        private readonly FieldSurveyService $fieldSurveyService,
    ) {}

    public function index(
        Request $request,
        Proposal $proposal,
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [FieldSurvey::class, $proposal]);

        $surveys = $this->fieldSurveyService->paginateForProposal(
            proposal: $proposal,
            user: $request->user(),
        );

        return FieldSurveyResource::collection($surveys)
            ->additional([
                'success' => true,
                'message' => 'Daftar survei lapangan berhasil diambil.',
            ]);
    }

    public function mySurveys(
        Request $request,
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', FieldSurvey::class);

        $surveys = $this->fieldSurveyService->paginateForSurveyor(
            surveyor: $request->user(),
        );

        return FieldSurveyResource::collection($surveys)
            ->additional([
                'success' => true,
                'message' => 'Daftar tugas survei lapangan berhasil diambil.',
            ]);
    }

    public function store(
        StoreFieldSurveyRequest $request,
        Proposal $proposal,
    ): JsonResponse {
        $this->authorize('create', [FieldSurvey::class, $proposal]);

        $survey = $this->fieldSurveyService->create(
            proposal: $proposal,
            surveyor: $request->validated('surveyor_id'),
            data: $request->validated(),
            actor: $request->user(),
        );

        return (new FieldSurveyResource($survey))
            ->additional([
                'success' => true,
                'message' => 'Survei lapangan berhasil dibuat.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function show(
        Proposal $proposal,
        FieldSurvey $fieldSurvey,
    ): FieldSurveyResource {
        $this->ensureSurveyBelongsToProposal(
            proposal: $proposal,
            fieldSurvey: $fieldSurvey,
        );

        $this->authorize('view', $fieldSurvey);

        $survey = $this->fieldSurveyService->findForProposal(
            proposal: $proposal,
            survey: $fieldSurvey,
        );

        return (new FieldSurveyResource($survey))
            ->additional([
                'success' => true,
                'message' => 'Detail survei lapangan berhasil diambil.',
            ]);
    }

    public function updateSchedule(
        UpdateFieldSurveyScheduleRequest $request,
        Proposal $proposal,
        FieldSurvey $fieldSurvey,
    ): FieldSurveyResource {
        $this->ensureSurveyBelongsToProposal(
            proposal: $proposal,
            fieldSurvey: $fieldSurvey,
        );

        $this->authorize('updateSchedule', $fieldSurvey);

        $survey = $this->fieldSurveyService->updateSchedule(
            survey: $fieldSurvey,
            data: $request->validated(),
        );

        return (new FieldSurveyResource($survey))
            ->additional([
                'success' => true,
                'message' => 'Jadwal survei lapangan berhasil diperbarui.',
            ]);
    }

    public function start(
        Request $request,
        Proposal $proposal,
        FieldSurvey $fieldSurvey,
    ): FieldSurveyResource {
        $this->ensureSurveyBelongsToProposal(
            proposal: $proposal,
            fieldSurvey: $fieldSurvey,
        );

        $this->authorize('start', $fieldSurvey);

        $survey = $this->fieldSurveyService->start(
            survey: $fieldSurvey,
        );

        return (new FieldSurveyResource($survey))
            ->additional([
                'success' => true,
                'message' => 'Pelaksanaan survei lapangan berhasil dimulai.',
            ]);
    }

    public function updateItem(
        UpdateFieldSurveyItemRequest $request,
        Proposal $proposal,
        FieldSurvey $fieldSurvey,
        FieldSurveyItem $item,
    ): FieldSurveyItemResource {
        $this->ensureSurveyBelongsToProposal(
            proposal: $proposal,
            fieldSurvey: $fieldSurvey,
        );

        $this->ensureItemBelongsToSurvey(
            fieldSurvey: $fieldSurvey,
            item: $item,
        );

        $this->authorize('updateItem', $fieldSurvey);

        $updatedItem = $this->fieldSurveyService->updateItem(
            survey: $fieldSurvey,
            item: $item,
            data: $request->validated(),
            actor: $request->user(),
        );

        return (new FieldSurveyItemResource($updatedItem))
            ->additional([
                'success' => true,
                'message' => 'Item pemeriksaan survei lapangan berhasil diperbarui.',
            ]);
    }

    public function storeFinding(
        StoreFieldSurveyFindingRequest $request,
        Proposal $proposal,
        FieldSurvey $fieldSurvey,
    ): JsonResponse {
        $this->ensureSurveyBelongsToProposal(
            proposal: $proposal,
            fieldSurvey: $fieldSurvey,
        );

        $this->authorize('addFinding', $fieldSurvey);

        $finding = $this->fieldSurveyService->addFinding(
            survey: $fieldSurvey,
            data: $request->validated(),
        );

        return (new FieldSurveyFindingResource($finding))
            ->additional([
                'success' => true,
                'message' => 'Temuan survei lapangan berhasil ditambahkan.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function storeDocument(
        StoreFieldSurveyDocumentRequest $request,
        Proposal $proposal,
        FieldSurvey $fieldSurvey,
    ): JsonResponse {
        $this->ensureSurveyBelongsToProposal(
            proposal: $proposal,
            fieldSurvey: $fieldSurvey,
        );

        $this->authorize('addDocument', $fieldSurvey);

        $document = $this->fieldSurveyService->addDocument(
            survey: $fieldSurvey,
            data: $request->validated(),
            actor: $request->user(),
        );

        return (new FieldSurveyDocumentResource($document))
            ->additional([
                'success' => true,
                'message' => 'Dokumen survei lapangan berhasil diunggah.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function fillResult(
        FillFieldSurveyResultRequest $request,
        Proposal $proposal,
        FieldSurvey $fieldSurvey,
    ): FieldSurveyResource {
        $this->ensureSurveyBelongsToProposal(
            proposal: $proposal,
            fieldSurvey: $fieldSurvey,
        );

        $this->authorize('fillResult', $fieldSurvey);

        $survey = $this->fieldSurveyService->fillResult(
            survey: $fieldSurvey,
            data: $request->validated(),
        );

        return (new FieldSurveyResource($survey))
            ->additional([
                'success' => true,
                'message' => 'Hasil survei lapangan berhasil disimpan.',
            ]);
    }

    public function submit(
        Request $request,
        Proposal $proposal,
        FieldSurvey $fieldSurvey,
    ): FieldSurveyResource {
        $this->ensureSurveyBelongsToProposal(
            proposal: $proposal,
            fieldSurvey: $fieldSurvey,
        );

        $this->authorize('submit', $fieldSurvey);

        $survey = $this->fieldSurveyService->submit(
            survey: $fieldSurvey,
            notes: $request->input('notes'),
        );

        return (new FieldSurveyResource($survey))
            ->additional([
                'success' => true,
                'message' => 'Survei lapangan berhasil diajukan untuk ditinjau.',
            ]);
    }

    public function review(
        ReviewFieldSurveyRequest $request,
        Proposal $proposal,
        FieldSurvey $fieldSurvey,
    ): FieldSurveyResource {
        $this->ensureSurveyBelongsToProposal(
            proposal: $proposal,
            fieldSurvey: $fieldSurvey,
        );

        $this->authorize('review', $fieldSurvey);

        $survey = $this->fieldSurveyService->review(
            survey: $fieldSurvey,
            action: $request->validated('action'),
            notes: $request->validated('notes'),
            reviewer: $request->user(),
        );

        return (new FieldSurveyResource($survey))
            ->additional([
                'success' => true,
                'message' => 'Survei lapangan berhasil ditinjau.',
            ]);
    }

    public function complete(
        CompleteFieldSurveyRequest $request,
        Proposal $proposal,
        FieldSurvey $fieldSurvey,
    ): FieldSurveyResource {
        $this->ensureSurveyBelongsToProposal(
            proposal: $proposal,
            fieldSurvey: $fieldSurvey,
        );

        $this->authorize('complete', $fieldSurvey);

        $survey = $this->fieldSurveyService->complete(
            survey: $fieldSurvey,
            data: $request->validated(),
            actor: $request->user(),
        );

        return (new FieldSurveyResource($survey))
            ->additional([
                'success' => true,
                'message' => 'Survei lapangan berhasil diselesaikan.',
            ]);
    }

    public function destroy(
        Proposal $proposal,
        FieldSurvey $fieldSurvey,
    ): JsonResponse {
        $this->ensureSurveyBelongsToProposal(
            proposal: $proposal,
            fieldSurvey: $fieldSurvey,
        );

        $this->authorize('delete', $fieldSurvey);

        $this->fieldSurveyService->delete($fieldSurvey);

        return ApiResponse::success(
            null,
            'Survei lapangan berhasil dihapus.'
        );
    }

    private function ensureSurveyBelongsToProposal(
        Proposal $proposal,
        FieldSurvey $fieldSurvey,
    ): void {
        abort_unless(
            (string) $fieldSurvey->proposal_id === (string) $proposal->id,
            404,
            'Survei lapangan tidak ditemukan pada proposal tersebut.'
        );
    }

    private function ensureItemBelongsToSurvey(
        FieldSurvey $fieldSurvey,
        FieldSurveyItem $item,
    ): void {
        abort_unless(
            (string) $item->field_survey_id === (string) $fieldSurvey->id,
            404,
            'Item pemeriksaan tidak ditemukan pada survei lapangan tersebut.'
        );
    }
}
