<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\PolicyConfiguration;
use App\Models\PolicyVersion;
use App\Services\PolicyConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PolicyConfigurationController extends Controller
{
    public function __construct(
        protected PolicyConfigurationService $policyService
    ) {}

    /**
     * List all policy configurations with their active version.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', PolicyConfiguration::class);

        $configs = PolicyConfiguration::with(['versions' => function ($q) {
            $q->orderByDesc('version_number');
        }])->get();

        return ApiResponse::success(
            $configs,
            'Daftar konfigurasi kebijakan berhasil diambil.'
        );
    }

    /**
     * List all versions of a specific policy configuration.
     */
    public function versionHistory(string $code): JsonResponse
    {
        $config = PolicyConfiguration::where('code', $code)->firstOrFail();
        Gate::authorize('view', $config);

        $versions = $config->versions()
            ->with(['creator:id,name', 'approver:id,name'])
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success(
            $versions,
            "Riwayat versi konfigurasi kebijakan {$code} berhasil diambil."
        );
    }

    /**
     * Create a new draft version for a policy configuration.
     */
    public function storeVersion(Request $request, string $code): JsonResponse
    {
        Gate::authorize('createVersion', PolicyConfiguration::class);

        $request->validate([
            'version_number' => ['nullable', 'string', 'max:50'],
            'configuration_data' => ['required', 'array'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        $config = PolicyConfiguration::where('code', $code)->firstOrFail();

        $version = $this->policyService->createDraftVersion(
            $config,
            $request->all(),
            $request->user()
        );

        return ApiResponse::created(
            $version->load('creator:id,name'),
            'Draft versi kebijakan berhasil dibuat.'
        );
    }

    /**
     * Approve a draft policy version using Maker-Checker.
     */
    public function approveVersion(Request $request, PolicyVersion $policyVersion): JsonResponse
    {
        Gate::authorize('approveVersion', PolicyConfiguration::class);

        $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $approved = $this->policyService->approveVersion(
            $policyVersion,
            $request->user(),
            $request->input('notes')
        );

        return ApiResponse::success(
            $approved->load(['creator:id,name', 'approver:id,name']),
            'Versi kebijakan berhasil disetujui (maker-checker diverifikasi).'
        );
    }

    /**
     * Activate an approved policy version.
     */
    public function activateVersion(Request $request, PolicyVersion $policyVersion): JsonResponse
    {
        Gate::authorize('activateVersion', PolicyConfiguration::class);

        $activated = $this->policyService->activateVersion(
            $policyVersion,
            $request->user()
        );

        return ApiResponse::success(
            $activated->load(['creator:id,name', 'approver:id,name']),
            'Versi kebijakan berhasil diaktifkan.'
        );
    }
}
