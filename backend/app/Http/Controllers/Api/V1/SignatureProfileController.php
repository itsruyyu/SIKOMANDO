<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\SignatureProfile;
use App\Services\DigitalSignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SignatureProfileController extends Controller
{
    public function __construct(
        protected DigitalSignatureService $signatureService
    ) {}

    /**
     * List authorized signature profiles.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SignatureProfile::class);

        $profiles = SignatureProfile::with('user:id,name,email')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate((int) $request->input('per_page', 15));

        return ApiResponse::success(
            data: $profiles,
            message: 'Daftar profil penandatangan resmi berhasil diambil.'
        );
    }

    /**
     * Create a new signature profile.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', SignatureProfile::class);

        $validated = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:active,inactive,suspended'],
            'authority_level' => ['nullable', 'string', 'in:officer,head_of_division,head_of_department,regional_head'],
            'effective_start_date' => ['nullable', 'date'],
            'effective_end_date' => ['nullable', 'date', 'after_or_equal:effective_start_date'],
        ]);

        $profile = $this->signatureService->createProfile($request->user(), $validated);

        return ApiResponse::created(
            data: $profile,
            message: 'Profil penandatangan berhasil dibuat.'
        );
    }

    /**
     * Show signature profile detail.
     */
    public function show(SignatureProfile $profile): JsonResponse
    {
        $this->authorize('view', $profile);

        return ApiResponse::success(
            data: $profile->load('user:id,name,email'),
            message: 'Detail profil penandatangan berhasil diambil.'
        );
    }

    /**
     * Update signature profile.
     */
    public function update(Request $request, SignatureProfile $profile): JsonResponse
    {
        $this->authorize('update', $profile);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'position' => ['sometimes', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'string', 'in:active,inactive,suspended'],
            'authority_level' => ['nullable', 'string', 'in:officer,head_of_division,head_of_department,regional_head'],
            'effective_start_date' => ['nullable', 'date'],
            'effective_end_date' => ['nullable', 'date', 'after_or_equal:effective_start_date'],
        ]);

        $updated = $this->signatureService->updateProfile($profile, $request->user(), $validated);

        return ApiResponse::success(
            data: $updated,
            message: 'Profil penandatangan berhasil diperbarui.'
        );
    }

    /**
     * Upload signature image (visual representation) or certificate.
     */
    public function uploadVisual(Request $request, SignatureProfile $profile): JsonResponse
    {
        $this->authorize('uploadVisual', $profile);

        // Disallow SVG to prevent embedded script execution / Stored XSS
        $request->validate([
            'signature_image' => ['required', 'file', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
            'signature_image' => ['required', 'file', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);

        $updated = $this->signatureService->uploadSignatureImage(
            profile: $profile,
            file: $request->file('signature_image'),
            actor: $request->user()
        );

        return ApiResponse::success(
            data: $updated,
            message: 'Gambar spesimen tanda tangan berhasil diunggah.'
        );
    }
}

