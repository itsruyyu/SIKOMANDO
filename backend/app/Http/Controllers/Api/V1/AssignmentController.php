<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RevokeAssignmentRequest;
use App\Http\Requests\Api\V1\StoreAssignmentRequest;
use App\Http\Resources\Api\V1\ProposalAssignmentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Proposal;
use App\Models\ProposalAssignment;
use App\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AssignmentController extends Controller
{
    public function __construct(
        protected AssignmentService $assignmentService
    ) {}

    /**
     * List all assignments with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ProposalAssignment::class);

        $query = ProposalAssignment::with([
            'assignedUser:id,name,email',
            'proposal:id,title,proposal_number,status',
            'assigner:id,name',
        ])
            ->when($request->query('assignment_type'), fn ($q, $t) => $q->where('assignment_type', $t))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('assigned_user_id'), fn ($q, $u) => $q->where('assigned_user_id', $u))
            ->orderByDesc('assigned_at');

        $assignments = $query->paginate($request->integer('per_page', 15));

        return ApiResponse::success([
            'data' => ProposalAssignmentResource::collection($assignments),
            'meta' => [
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'per_page' => $assignments->perPage(),
                'total' => $assignments->total(),
            ],
        ], 'Daftar penugasan berhasil diambil.');
    }

    /**
     * Store a new assignment.
     */
    public function store(StoreAssignmentRequest $request): JsonResponse
    {
        Gate::authorize('create', ProposalAssignment::class);

        $proposal = Proposal::findOrFail($request->input('proposal_id'));

        $assignment = $this->assignmentService->assign(
            $proposal,
            $request->validated(),
            $request->user()
        );

        return ApiResponse::created(
            new ProposalAssignmentResource($assignment),
            'Penugasan berhasil dibuat.'
        );
    }

    /**
     * Display the specified assignment.
     */
    public function show(ProposalAssignment $assignment): JsonResponse
    {
        Gate::authorize('view', $assignment);

        $assignment->load([
            'assignedUser:id,name,email',
            'proposal:id,title,proposal_number,status',
            'assigner:id,name',
            'revoker:id,name',
        ]);

        return ApiResponse::success(
            new ProposalAssignmentResource($assignment),
            'Detail penugasan berhasil diambil.'
        );
    }

    /**
     * Revoke an active assignment.
     */
    public function revoke(RevokeAssignmentRequest $request, ProposalAssignment $assignment): JsonResponse
    {
        Gate::authorize('revoke', $assignment);

        $revoked = $this->assignmentService->revoke(
            $assignment,
            $request->validated(),
            $request->user()
        );

        return ApiResponse::success(
            new ProposalAssignmentResource($revoked),
            'Penugasan berhasil dicabut.'
        );
    }

    /**
     * List assignments for the authenticated user.
     */
    public function myAssignments(Request $request): JsonResponse
    {
        $assignments = ProposalAssignment::with([
            'proposal:id,title,proposal_number,status,grant_program_id',
            'proposal.grantProgram:id,name,fiscal_year',
            'assigner:id,name',
        ])
            ->where('assigned_user_id', $request->user()->id)
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('assigned_at')
            ->paginate($request->integer('per_page', 15));

        return ApiResponse::success([
            'data' => ProposalAssignmentResource::collection($assignments),
            'meta' => [
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'per_page' => $assignments->perPage(),
                'total' => $assignments->total(),
            ],
        ], 'Daftar penugasan saya berhasil diambil.');
    }

    /**
     * List assignments for a specific proposal.
     */
    public function proposalAssignments(Proposal $proposal, Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ProposalAssignment::class);

        $assignments = $proposal->assignments()
            ->with(['assignedUser:id,name,email', 'assigner:id,name', 'revoker:id,name'])
            ->orderByDesc('assigned_at')
            ->get();

        return ApiResponse::success(
            ProposalAssignmentResource::collection($assignments),
            'Daftar penugasan proposal berhasil diambil.'
        );
    }

    /**
     * Workload statistics.
     */
    public function workload(Request $request): JsonResponse
    {
        Gate::authorize('viewWorkload', ProposalAssignment::class);

        $stats = $this->assignmentService->getWorkloadStats(
            $request->query('assignment_type'),
            $request->query('grant_program_id')
        );

        return ApiResponse::success(
            $stats,
            'Statistik beban kerja berhasil diambil.'
        );
    }
}
