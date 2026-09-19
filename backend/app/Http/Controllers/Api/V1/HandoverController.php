<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Handover;
use App\Models\RealizationPackage;
use App\Services\HandoverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HandoverController extends Controller
{
    public function __construct(
        protected HandoverService $handoverService
    ) {}

    /**
     * List handovers (BAST) for a package.
     */
    public function index(RealizationPackage $package): JsonResponse
    {
        $handovers = Handover::where('realization_package_id', $package->id)
            ->with(['qrIdentity', 'items'])
            ->latest()
            ->get();

        return ApiResponse::success(
            data: $handovers,
            message: 'Daftar BAST (Berita Acara Serah Terima) berhasil diambil.'
        );
    }

    /**
     * Create handover (BAST).
     */
    public function store(Request $request, RealizationPackage $package): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'handover_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'handover_by_name' => ['required', 'string', 'max:255'],
            'handover_by_position' => ['nullable', 'string', 'max:255'],
            'handover_to_name' => ['required', 'string', 'max:255'],
            'handover_to_position' => ['nullable', 'string', 'max:255'],
            'handover_to_organization' => ['nullable', 'string', 'max:255'],
            'document_path' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.realization_item_id' => ['required_with:items', 'uuid', 'exists:realization_items,id'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.condition_at_handover' => ['nullable', 'string', 'in:GOOD,DAMAGED,LOST'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $handover = $this->handoverService->createHandover(
            package: $package,
            actor: $request->user(),
            data: $validated,
            items: $validated['items'] ?? []
        );

        return ApiResponse::created(
            data: $handover,
            message: 'Berita Acara Serah Terima (BAST) berhasil dibuat dan QR code diterbitkan.'
        );
    }

    /**
     * Show handover detail.
     */
    public function show(Handover $handover): JsonResponse
    {
        return ApiResponse::success(
            data: $handover->load(['package.proposal.organization', 'items', 'qrIdentity', 'digitalSignatures']),
            message: 'Detail BAST berhasil diambil.'
        );
    }

    /**
     * Submit handover.
     */
    public function submit(Request $request, Handover $handover): JsonResponse
    {
        $submitted = $this->handoverService->submitHandover($handover, $request->user());

        return ApiResponse::success(
            data: $submitted,
            message: 'BAST berhasil diajukan.'
        );
    }

    /**
     * Complete handover.
     */
    public function complete(Request $request, Handover $handover): JsonResponse
    {
        $completed = $this->handoverService->completeHandover($handover, $request->user());

        return ApiResponse::success(
            data: $completed,
            message: 'BAST berhasil diselesaikan dan status barang dialihkan.'
        );
    }

    /**
     * Cancel handover.
     */
    public function cancel(Request $request, Handover $handover): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $cancelled = $this->handoverService->cancelHandover($handover, $request->user(), $validated['reason']);

        return ApiResponse::success(
            data: $cancelled,
            message: 'BAST berhasil dibatalkan dan QR terkait dicabut.'
        );
    }
}
