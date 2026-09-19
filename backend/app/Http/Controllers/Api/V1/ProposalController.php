<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProposalApiRequest;
use App\Http\Resources\Api\V1\ProposalResource;
use App\Models\GrantProgram;
use App\Models\Organization;
use App\Models\Proposal;
use App\Services\ProposalService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProposalController extends Controller
{
    public function __construct(
        private readonly ProposalService $proposalService
    ) {}

    /**
     * Display a listing of proposals.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Proposal::class);

        $query = Proposal::query()
            ->with([
                'grantProgram',
                'organization',
                'applicant',
            ])
            ->latest();

        if ($request->user()->hasRole('PEMOHON')) {
            $query->where(
                'applicant_id',
                $request->user()->id
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->string('status')->toString()
            );
        }

        $perPage = min(
            max($request->integer('per_page', 15), 1),
            100
        );

        $proposals = $query
            ->paginate($perPage)
            ->withQueryString();

        return ProposalResource::collection($proposals)
            ->additional([
                'success' => true,
                'message' => 'Daftar proposal berhasil diambil.',
            ]);
    }

    /**
     * Store a newly created proposal.
     */
    public function store(
        StoreProposalApiRequest $request
    ): ProposalResource {
        $validated = $request->validated();

        $program = GrantProgram::query()
            ->findOrFail($validated['grant_program_id']);

        $organization = Organization::query()
            ->findOrFail($validated['organization_id']);

        $proposal = $this->proposalService->create(
            program: $program,
            organization: $organization,
            applicantId: $request->user()->id,
            data: [
                'title' => $validated['title'],
                'background' => $validated['background'] ?? null,
                'objectives' => $validated['objectives'] ?? null,
                'benefits' => $validated['benefits'] ?? null,
                'activities' => $validated['activities'] ?? null,
                'outputs' => $validated['outputs']
                    ?? $validated['expected_outputs']
                    ?? null,
            ],
            requestId: $request->header('X-Request-ID'),
        );

        if (! empty($validated['budget_items'])) {
            app(\App\Services\ProposalBudgetService::class)->replaceItems(
                $proposal,
                $validated['budget_items'],
                $request->header('X-Request-ID')
            );
            $proposal->refresh();
        }

        $proposal->load([
            'grantProgram',
            'organization',
            'applicant',
            'budgetItems',
            'documents',
        ]);

        return (new ProposalResource($proposal))
            ->additional([
                'success' => true,
                'message' => 'Proposal berhasil dibuat.',
            ]);
    }

    /**
     * Display the specified proposal.
     */
    public function show(
        Proposal $proposal
    ): ProposalResource {
        $this->authorize('view', $proposal);

        $proposal->load([
            'grantProgram',
            'organization',
            'applicant',
            'budgetItems',
            'documents',
        ]);

        return (new ProposalResource($proposal))
            ->additional([
                'success' => true,
                'message' => 'Detail proposal berhasil diambil.',
            ]);
    }

    /**
     * Submit a proposal for verification.
     */
    public function submit(
        Request $request,
        Proposal $proposal
    ): ProposalResource {
        $this->authorize('submit', $proposal);

        $submittedProposal = $this->proposalService->submit(
            proposal: $proposal,
            actorId: $request->user()->id,
            requestId: $request->header('X-Request-ID'),
        );

        return (new ProposalResource(
            $submittedProposal->load([
                'grantProgram',
                'organization',
                'applicant',
                'budgetItems',
                'documents',
            ])
        ))->additional([
            'success' => true,
            'message' => 'Proposal berhasil diajukan.',
        ]);
    }
}
