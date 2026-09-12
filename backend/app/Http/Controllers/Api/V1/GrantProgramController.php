<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\GrantProgramResource;
use App\Models\GrantProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GrantProgramController extends Controller
{
    public function index(Request $request)
    {
        $programs = GrantProgram::query()
            ->where('is_active', true)
            ->orderByDesc('fiscal_year')
            ->orderBy('name')
            ->paginate(
                $request->integer('per_page', 15)
            );

        return GrantProgramResource::collection($programs);
    }

    public function show(GrantProgram $grantProgram): GrantProgramResource
    {
        abort_unless($grantProgram->is_active, 404);

        return new GrantProgramResource($grantProgram);
    }
}