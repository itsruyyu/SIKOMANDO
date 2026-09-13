<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProposalApiRequest;
use App\Http\Resources\Api\V1\ProposalResource;
use App\Models\Proposal;
use App\Services\ProposalService;
use Illuminate\Http\Request;

class ProposalController extends Controller
{
    public function __construct(
        private readonly ProposalService $proposalService
    ) {
    }

    public function index(Request $request)
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

        $proposals = $query->paginate(
            $request->integer('per_page', 15)
        );

        return ProposalResource::collection($proposals);
    }

    public function store(
        StoreProposalApiRequest $request
    ): ProposalResource {
        $proposal = $this->proposalService->create([
            'grant_program_id' => $request->validated('grant_program_id'),
            'organization_id' => $request->validated('organization_id'),
            'applicant_id' => $request->user()->id,
            'title' => $request->validated('title'),
            'background' => $request->validated('background'),
            'objectives' => $request->validated('objectives'),
            'benefits' => $request->validated('benefits'),
            'activities' => $request->validated('activities'),
            'expected_outputs' => $request->validated('expected_outputs'),
        ]);

        return new ProposalResource($proposal);
    }

    public function show(
        Proposal $proposal
    ): ProposalResource {
        $this->authorize('view', $proposal);

        return new ProposalResource(
            $proposal->load([
                'grantProgram',
                'organization',
                'applicant',
                'budgetItems',
                'documents',
            ])
        );
    }
}