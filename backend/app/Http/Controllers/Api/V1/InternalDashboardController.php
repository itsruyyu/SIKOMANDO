<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ProposalAssignment;
use App\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class InternalDashboardController extends Controller
{
    public function __construct(
        protected AssignmentService $assignmentService
    ) {}

    /**
     * Workload summary by user and role.
     */
    public function workloadSummary(Request $request): JsonResponse
    {
        Gate::authorize('viewWorkload', ProposalAssignment::class);

        $stats = $this->assignmentService->getWorkloadStats(
            $request->query('assignment_type'),
            $request->query('grant_program_id')
        );

        return ApiResponse::success(
            $stats,
            'Ringkasan beban kerja petugas berhasil diambil.'
        );
    }

    /**
     * Tasks grouped by status.
     */
    public function tasksByStatus(Request $request): JsonResponse
    {
        Gate::authorize('viewWorkload', ProposalAssignment::class);

        $tasks = DB::table('proposal_assignments')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get();

        return ApiResponse::success(
            $tasks,
            'Distribusi status tugas berhasil diambil.'
        );
    }

    /**
     * Assignment audit history.
     */
    public function assignmentHistory(Request $request): JsonResponse
    {
        Gate::authorize('viewWorkload', ProposalAssignment::class);

        $history = ProposalAssignment::with([
            'assignedUser:id,name',
            'proposal:id,title,proposal_number',
            'assigner:id,name',
            'revoker:id,name',
        ])
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::success([
            'data' => $history->items(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ], 'Histori penugasan berhasil diambil.');
    }
}
