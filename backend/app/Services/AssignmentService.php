<?php

namespace App\Services;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentType;
use App\Models\Proposal;
use App\Models\ProposalAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignmentService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Create a new proposal assignment.
     *
     * @throws ValidationException
     */
    public function assign(Proposal $proposal, array $data, User $actor): ProposalAssignment
    {
        $assignedUser = User::find($data['assigned_user_id']);
        if (! $assignedUser) {
            throw ValidationException::withMessages([
                'assigned_user_id' => 'User yang ditugaskan tidak ditemukan.',
            ]);
        }

        // User must be active
        if (! $assignedUser->is_active) {
            throw ValidationException::withMessages([
                'assigned_user_id' => 'User yang ditugaskan sedang tidak aktif.',
            ]);
        }

        $type = AssignmentType::from($data['assignment_type']);

        // User must have the required role
        if (! $assignedUser->hasRole($type->requiredRole())) {
            throw ValidationException::withMessages([
                'assigned_user_id' => "User tidak memiliki role {$type->requiredRole()} yang diperlukan untuk penugasan {$type->label()}.",
            ]);
        }

        // Prevent duplicate active assignment for same type on same proposal
        $existing = ProposalAssignment::where('proposal_id', $proposal->id)
            ->where('assignment_type', $type)
            ->active()
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'assignment_type' => "Sudah ada penugasan {$type->label()} aktif untuk proposal ini. Cabut terlebih dahulu sebelum membuat penugasan baru.",
            ]);
        }

        return DB::transaction(function () use ($proposal, $data, $type, $actor) {
            $assignment = ProposalAssignment::create([
                'proposal_id' => $proposal->id,
                'assigned_user_id' => $data['assigned_user_id'],
                'assignment_type' => $type,
                'status' => AssignmentStatus::ASSIGNED,
                'assigned_by' => $actor->id,
                'assigned_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->auditLogService->record(
                action: 'assignment.created',
                module: 'assignment',
                entityType: ProposalAssignment::class,
                entityId: $assignment->id,
                newValues: [
                    'proposal_id' => $proposal->id,
                    'assigned_user_id' => $data['assigned_user_id'],
                    'assignment_type' => $type->value,
                ],
                metadata: ['actor_id' => $actor->id]
            );

            return $assignment->load(['assignedUser:id,name,email', 'proposal:id,title,proposal_number', 'assigner:id,name']);
        });
    }

    /**
     * Revoke an active assignment.
     *
     * @throws ValidationException
     */
    public function revoke(ProposalAssignment $assignment, array $data, User $actor): ProposalAssignment
    {
        if (! $assignment->status->isActive()) {
            throw ValidationException::withMessages([
                'assignment' => 'Hanya penugasan aktif yang dapat dicabut.',
            ]);
        }

        return DB::transaction(function () use ($assignment, $data, $actor) {
            $oldStatus = $assignment->status->value;

            $assignment->update([
                'status' => AssignmentStatus::REVOKED,
                'revoked_at' => now(),
                'revoked_by' => $actor->id,
                'reason' => $data['reason'] ?? null,
            ]);

            $this->auditLogService->record(
                action: 'assignment.revoked',
                module: 'assignment',
                entityType: ProposalAssignment::class,
                entityId: $assignment->id,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status' => AssignmentStatus::REVOKED->value,
                    'reason' => $data['reason'] ?? null,
                ],
                metadata: ['actor_id' => $actor->id]
            );

            return $assignment->fresh(['assignedUser:id,name,email', 'proposal:id,title,proposal_number', 'assigner:id,name', 'revoker:id,name']);
        });
    }

    /**
     * Get workload statistics for dashboard.
     */
    public function getWorkloadStats(?string $assignmentType = null, ?string $grantProgramId = null): array
    {
        $query = DB::table('proposal_assignments')
            ->join('users', 'proposal_assignments.assigned_user_id', '=', 'users.id')
            ->select(
                'users.id as user_id',
                'users.name as user_name',
                'proposal_assignments.assignment_type',
                DB::raw('COUNT(*) as total_tasks'),
                DB::raw("COUNT(*) FILTER (WHERE proposal_assignments.status = 'ASSIGNED') as pending_tasks"),
                DB::raw("COUNT(*) FILTER (WHERE proposal_assignments.status = 'IN_PROGRESS') as active_tasks"),
                DB::raw("COUNT(*) FILTER (WHERE proposal_assignments.status = 'COMPLETED') as completed_tasks"),
                DB::raw("COUNT(*) FILTER (WHERE proposal_assignments.status = 'REVOKED') as revoked_tasks"),
            )
            ->groupBy('users.id', 'users.name', 'proposal_assignments.assignment_type');

        if ($assignmentType) {
            $query->where('proposal_assignments.assignment_type', $assignmentType);
        }

        if ($grantProgramId) {
            $query->join('proposals', 'proposal_assignments.proposal_id', '=', 'proposals.id')
                ->where('proposals.grant_program_id', $grantProgramId);
        }

        return $query->orderBy('users.name')->get()->toArray();
    }
}
