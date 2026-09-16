<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\GrantProgramResource;
use App\Models\GrantProgram;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GrantProgramController extends Controller
{
    /**
     * Display a listing of active grant programs.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = GrantProgram::query()
            ->where('is_active', true)
            ->orderByDesc('fiscal_year')
            ->orderBy('name');

        $perPage = min(
            max($request->integer('per_page', 15), 1),
            100
        );

        return GrantProgramResource::collection(
            $query->paginate($perPage)->withQueryString()
        )->additional([
            'success' => true,
            'message' => 'Daftar program hibah berhasil diambil.',
        ]);
    }

    /**
     * Display the specified grant program.
     */
    public function show(GrantProgram $grantProgram): GrantProgramResource
    {
        abort_unless($grantProgram->is_active, 404);

        return (new GrantProgramResource($grantProgram))
            ->additional([
                'success' => true,
                'message' => 'Detail program hibah berhasil diambil.',
            ]);
    }
}
