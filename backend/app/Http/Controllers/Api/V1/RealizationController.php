<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Proposal;
use App\Models\RealizationItem;
use App\Models\RealizationPackage;
use App\Services\RealizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RealizationController extends Controller
{
    public function __construct(
        protected RealizationService $realizationService
    ) {}

    /**
     * List realization packages for a proposal.
     */
    public function indexPackages(Proposal $proposal): JsonResponse
    {
        $packages = RealizationPackage::where('proposal_id', $proposal->id)
            ->with(['qrIdentity', 'items.qrIdentity'])
            ->withCount('items')
            ->latest()
            ->get();

        return ApiResponse::success(
            data: $packages,
            message: 'Daftar paket realisasi berhasil diambil.'
        );
    }

    /**
     * Create realization package.
     */
    public function storePackage(Request $request, Proposal $proposal): JsonResponse
    {
        $validated = $request->validate([
            'package_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_completion_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $package = $this->realizationService->createPackage($proposal, $request->user(), $validated);

        return ApiResponse::created(
            data: $package,
            message: 'Paket realisasi berhasil dibuat dan QR code telah digenerate.'
        );
    }

    /**
     * Show realization package details.
     */
    public function showPackage(RealizationPackage $package): JsonResponse
    {
        return ApiResponse::success(
            data: $package->load(['proposal.organization', 'items.budgetItem', 'items.qrIdentity', 'qrIdentity', 'receipts', 'handovers']),
            message: 'Detail paket realisasi berhasil diambil.'
        );
    }

    /**
     * Update realization package.
     */
    public function updatePackage(Request $request, RealizationPackage $package): JsonResponse
    {
        $validated = $request->validate([
            'package_name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_completion_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $updated = $this->realizationService->updatePackage($package, $request->user(), $validated);

        return ApiResponse::success(
            data: $updated,
            message: 'Paket realisasi berhasil diperbarui.'
        );
    }

    /**
     * Submit realization package for verification.
     */
    public function submitPackage(Request $request, RealizationPackage $package): JsonResponse
    {
        $submitted = $this->realizationService->submitPackage($package, $request->user());

        return ApiResponse::success(
            data: $submitted,
            message: 'Paket realisasi berhasil diajukan untuk verifikasi.'
        );
    }

    /**
     * Verify realization package.
     */
    public function verifyPackage(Request $request, RealizationPackage $package): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:VERIFIED,COMPLETED'],
            'notes' => ['nullable', 'string'],
        ]);

        $verified = $this->realizationService->verifyPackage($package, $request->user(), $validated);

        return ApiResponse::success(
            data: $verified,
            message: 'Paket realisasi berhasil diverifikasi.'
        );
    }

    /**
     * Add realization item to package.
     */
    public function addItem(Request $request, RealizationPackage $package): JsonResponse
    {
        $validated = $request->validate([
            'proposal_budget_item_id' => ['nullable', 'uuid'],
            'item_code' => ['nullable', 'string', 'max:50'],
            'item_name' => ['required', 'string', 'max:255'],
            'specification' => ['nullable', 'string'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:50'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_address' => ['nullable', 'string'],
            'location_notes' => ['nullable', 'string'],
            'photos' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $item = $this->realizationService->addItem($package, $request->user(), $validated);

        return ApiResponse::created(
            data: $item,
            message: 'Barang/item realisasi berhasil ditambahkan beserta QR code fisiknya.'
        );
    }

    /**
     * Show realization item detail.
     */
    public function showItem(RealizationItem $item): JsonResponse
    {
        return ApiResponse::success(
            data: $item->load(['package.proposal.organization', 'budgetItem', 'qrIdentity', 'histories.recorder:id,name,email']),
            message: 'Detail barang realisasi berhasil diambil.'
        );
    }

    /**
     * Update realization item.
     */
    public function updateItem(Request $request, RealizationItem $item): JsonResponse
    {
        $validated = $request->validate([
            'item_name' => ['sometimes', 'string', 'max:255'],
            'specification' => ['nullable', 'string'],
            'quantity' => ['sometimes', 'numeric', 'min:0.01'],
            'unit' => ['sometimes', 'string', 'max:50'],
            'unit_price' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', 'in:created,purchased,received,verified,assigned,monitored,closed,CREATED,PURCHASED,RECEIVED,VERIFIED,ASSIGNED,MONITORED,CLOSED'],
            'condition' => ['sometimes', 'string', 'in:good,damaged,lost,GOOD,DAMAGED,LOST'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_address' => ['nullable', 'string'],
            'photos' => ['nullable', 'array'],
            'history_notes' => ['nullable', 'string'],
        ]);

        $updated = $this->realizationService->updateItem($item, $request->user(), $validated);

        return ApiResponse::success(
            data: $updated,
            message: 'Data barang realisasi berhasil diperbarui dan riwayat tercatat.'
        );
    }

    /**
     * Record physical inspection on item.
     */
    public function inspectItem(Request $request, RealizationItem $item): JsonResponse
    {
        $validated = $request->validate([
            'condition' => ['required', 'string', 'in:good,damaged,lost,GOOD,DAMAGED,LOST'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_address' => ['nullable', 'string'],
            'photos' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
            'action' => ['nullable', 'string'],
        ]);

        $inspected = $this->realizationService->recordInspection($item, $request->user(), $validated);

        return ApiResponse::success(
            data: $inspected,
            message: 'Pemeriksaan fisik barang realisasi berhasil dicatat.'
        );
    }
}
