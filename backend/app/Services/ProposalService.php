<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Models\GrantProgram;
use App\Models\Organization;
use App\Models\Proposal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProposalService
{
    public function __construct(
        private readonly ProposalWorkflowService $workflow,
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function create(
        GrantProgram $program,
        Organization $organization,
        string $applicantId,
        array $data,
        ?string $requestId = null,
    ): Proposal {
        if (! $program->is_active) {
            throw ValidationException::withMessages([
                'grant_program_id' => 'Program hibah tidak aktif.',
            ]);
        }

        if (! $organization->is_active) {
            throw ValidationException::withMessages([
                'organization_id' => 'Organisasi tidak aktif.',
            ]);
        }

        return DB::transaction(function () use (
            $program,
            $organization,
            $applicantId,
            $data,
            $requestId
        ): Proposal {
            $proposal = Proposal::create([
                'proposal_number' => $this->generateProposalNumber($program),
                'grant_program_id' => $program->id,
                'organization_id' => $organization->id,
                'applicant_id' => $applicantId,
                'title' => $data['title'],
                'background' => $data['background'] ?? null,
                'objectives' => $data['objectives'] ?? null,
                'benefits' => $data['benefits'] ?? null,
                'activities' => $data['activities'] ?? null,
                'outputs' => $data['outputs'] ?? null,
                'requested_amount' => 0,
                'approved_amount' => 0,
                'status' => ProposalStatus::DRAFT,
                'revision_count' => 0,
                'created_by' => $applicantId,
                'updated_by' => $applicantId,
            ]);

            $this->auditLog->record(
                action: 'proposal.created',
                module: 'proposal',
                entityType: Proposal::class,
                entityId: $proposal->id,
                newValues: [
                    'proposal_number' => $proposal->proposal_number,
                    'grant_program_id' => $program->id,
                    'organization_id' => $organization->id,
                    'status' => ProposalStatus::DRAFT->value,
                ],
                requestId: $requestId,
            );

            return $proposal;
        });
    }

    public function submit(
        Proposal $proposal,
        string $actorId,
        ?string $requestId = null,
    ): Proposal {
        $proposal->loadMissing([
            'grantProgram',
            'organization',
            'budgetItems',
            'documents',
        ]);

        $this->validateBeforeSubmit($proposal);

        return $this->workflow->transition(
            proposal: $proposal,
            targetStatus: ProposalStatus::SUBMITTED,
            actorId: $actorId,
            reason: 'Proposal diajukan oleh pemohon.',
            requestId: $requestId,
        );
    }

    private function validateBeforeSubmit(Proposal $proposal): void
    {
        $errors = [];

        if (blank($proposal->title)) {
            $errors['title'] = 'Judul proposal wajib diisi.';
        }

        if ($proposal->budgetItems->isEmpty()) {
            $errors['budget'] = 'Proposal harus memiliki minimal satu item RAB.';
        }

        if ((float) $proposal->requested_amount <= 0) {
            $errors['requested_amount'] = 'Total pengajuan harus lebih besar dari nol.';
        }

        if (! $proposal->organization) {
            $errors['organization'] = 'Organisasi proposal tidak valid.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function generateProposalNumber(GrantProgram $program): string
    {
        $year = $program->fiscal_year ?: now()->year;
        $prefix = 'PROP-' . $year . '-';

        do {
            $number = $prefix . strtoupper(Str::random(8));
        } while (Proposal::where('proposal_number', $number)->exists());

        return $number;
    }
}