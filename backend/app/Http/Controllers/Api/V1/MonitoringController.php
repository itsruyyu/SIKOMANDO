<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\MonitoringRecord;
use App\Models\Proposal;
use App\Services\MonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function __construct(
        protected MonitoringService $monitoringService
    ) {}

    /**
     * List monitoring records for a proposal.
     */
    public function index(Request $request, Proposal $proposal): JsonResponse
    {
        $records = $this->monitoringService->paginateForProposal(
            proposal: $proposal,
            perPage: (int) $request->input('per_page', 15)
        );

        return ApiResponse::success(
            data: $records,
            message: 'Daftar rekam monitoring proposal berhasil diambil.'
        );
    }

    /**
     * Create monitoring record.
     */
    public function store(Request $request, Proposal $proposal): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'monitoring_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'overall_result' => ['nullable', 'string', 'max:50'],
        ]);

        $record = $this->monitoringService->createRecord($proposal, $request->user(), $validated);

        return ApiResponse::created(
            data: $record,
            message: 'Rekam monitoring berhasil dibuat dan QR code diterbitkan.'
        );
    }

    /**
     * Show monitoring record details with checked items.
     */
    public function show(MonitoringRecord $record): JsonResponse
    {
        return ApiResponse::success(
            data: $record->load(['proposal.organization', 'inspector:id,name,email', 'items.realizationItem.qrIdentity', 'qrIdentity']),
            message: 'Detail rekam monitoring berhasil diambil.'
        );
    }

    /**
     * Scan QR code or verify physical realization item during monitoring.
     */
    public function checkItem(Request $request, MonitoringRecord $record): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['nullable', 'string'],
            'realization_item_id' => ['nullable', 'uuid', 'exists:realization_items,id'],
            'condition' => ['required', 'string', 'in:good,damaged,lost,GOOD,DAMAGED,LOST'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_address' => ['nullable', 'string'],
            'photos' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $item = $this->monitoringService->recordItemCheck($record, $request->user(), $validated);

        return ApiResponse::created(
            data: $item,
            message: 'Pemeriksaan fisik barang via QR berhasil dicatat dalam monitoring.'
        );
    }

    /**
     * Complete monitoring record.
     */
    public function complete(Request $request, MonitoringRecord $record): JsonResponse
    {
        $validated = $request->validate([
            'overall_result' => ['required', 'string', 'in:SATISFACTORY,NEEDS_IMPROVEMENT,NON_COMPLIANT'],
            'notes' => ['nullable', 'string'],
        ]);

        $completed = $this->monitoringService->completeRecord($record, $request->user(), $validated);

        return ApiResponse::success(
            data: $completed,
            message: 'Rekam monitoring berhasil diselesaikan.'
        );
    }
}
