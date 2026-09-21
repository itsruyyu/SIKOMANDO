<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Proposal;
use App\Models\Receipt;
use App\Services\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function __construct(
        protected ReceiptService $receiptService
    ) {}

    /**
     * List receipts (for a proposal or flat cross-proposal).
     */
    public function index(Request $request, ?Proposal $proposal = null): JsonResponse
    {
        $this->authorize('viewAny', Receipt::class);

        $query = Receipt::query()
            ->with(['qrIdentity', 'package', 'proposal.organization', 'verifier:id,name'])
            ->latest();

        if ($proposal && $proposal->exists) {
            $query->where('proposal_id', $proposal->id);
        }

        if ($request->user()->hasRole('PEMOHON')) {
            $query->whereHas('proposal', fn ($q) => $q->where('applicant_id', $request->user()->id));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $receipts = $query->paginate($perPage);

        return ApiResponse::paginated(
            paginator: $receipts,
            message: 'Daftar kuitansi realisasi berhasil diambil.'
        );
    }

    /**
     * Create receipt.
     */
    public function store(Request $request, Proposal $proposal): JsonResponse
    {
        $this->authorize('create', [Receipt::class, $proposal]);

        $validated = $request->validate([
            'realization_package_id' => ['nullable', 'uuid'],
            'disbursement_id' => ['nullable', 'uuid'],
            'amount' => ['required', 'numeric', 'min:1'],
            'receipt_date' => ['nullable', 'date'],
            'paid_to' => ['required', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'attachment_path' => ['nullable', 'string'],
        ]);

        $receipt = $this->receiptService->createReceipt($proposal, $request->user(), $validated);

        return ApiResponse::created(
            data: $receipt,
            message: 'Kuitansi realisasi berhasil dibuat dan QR code telah diterbitkan.'
        );
    }

    /**
     * Show receipt detail.
     */
    public function show(Receipt $receipt): JsonResponse
    {
        $this->authorize('view', $receipt);

        return ApiResponse::success(
            data: $receipt->load(['proposal.organization', 'package', 'qrIdentity']),
            data: $receipt->load(['proposal.organization', 'package', 'qrIdentity', 'verifier:id,name']),
            message: 'Detail kuitansi realisasi berhasil diambil.'
        );
    }

    /**
     * Update receipt.
     */
    public function update(Request $request, Receipt $receipt): JsonResponse
    {
        $this->authorize('update', $receipt);

        $validated = $request->validate([
            'amount' => ['sometimes', 'numeric', 'min:1'],
            'receipt_date' => ['nullable', 'date'],
            'paid_to' => ['sometimes', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'attachment_path' => ['nullable', 'string'],
        ]);

        $updated = $this->receiptService->updateReceipt($receipt, $request->user(), $validated);

        return ApiResponse::success(
            data: $updated,
            message: 'Kuitansi realisasi berhasil diperbarui.'
        );
    }

    /**
     * Verify receipt.
     */
    public function verify(Request $request, Receipt $receipt): JsonResponse
    {
        $this->authorize('verify', $receipt);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $verified = $this->receiptService->verifyReceipt($receipt, $request->user(), $validated['notes'] ?? null);

        return ApiResponse::success(
            data: $verified,
            message: 'Kuitansi realisasi berhasil diverifikasi.'
        );
    }

    /**
     * Cancel receipt.
     */
    public function cancel(Request $request, Receipt $receipt): JsonResponse
    {
        $this->authorize('cancel', $receipt);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $cancelled = $this->receiptService->cancelReceipt($receipt, $request->user(), $validated['reason']);

        return ApiResponse::success(
            data: $cancelled,
            message: 'Kuitansi realisasi berhasil dibatalkan dan QR terkait dicabut.'
        );
    }
}
