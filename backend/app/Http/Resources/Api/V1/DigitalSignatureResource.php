<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Decision;
use App\Models\Handover;
use App\Models\Proposal;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DigitalSignatureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $signable = $this->signable;

        $documentNumber = match (true) {
            $signable instanceof Decision => $signable->decision_number,
            $signable instanceof Receipt => $signable->receipt_number,
            $signable instanceof Handover => $signable->handover_number,
            $signable instanceof Proposal => $signable->proposal_number,
            default => $signable->number ?? $signable->reference_number ?? 'DOK-'.substr($this->id, 0, 8),
        };

        $documentTitle = match (true) {
            $signable instanceof Decision => 'Surat Keputusan (SK) Penetapan Hibah',
            $signable instanceof Receipt => 'Kuitansi Realisasi Pembayaran Belanja',
            $signable instanceof Handover => 'Berita Acara Serah Terima (BAST)',
            $signable instanceof Proposal => 'Dokumen Usulan Hibah: '.($signable->title ?? ''),
            default => class_basename($this->signable_type),
        };

        $qrIdentity = $signable && method_exists($signable, 'qrIdentity') ? $signable->qrIdentity : null;

        return [
            'id' => $this->id,
            'signable_type' => class_basename($this->signable_type),
            'signable_id' => $this->signable_id,
            'document_title' => $documentTitle,
            'document_number' => $documentNumber,
            'status' => $this->status?->value ?? (string) $this->status,
            'status_label' => method_exists($this->status, 'label') ? $this->status->label() : ucfirst(str_replace('_', ' ', $this->status?->value ?? '')),
            'signer_name' => $this->signer_name,
            'signer_position' => $this->signer_position,
            'signer' => [
                'name' => $this->signer_name,
                'position' => $this->signer_position,
                'nip' => $this->signer_nip_snapshot ?? $this->profile?->nip,
            ],
            'signed_at' => $this->signed_at?->toISOString(),
            'valid_from' => $this->valid_from?->toISOString(),
            'valid_until' => $this->valid_until?->toISOString(),
            'is_expired' => $this->is_expired,
            'document_hash' => $this->document_hash,
            'document_hash_short' => $this->document_hash ? substr($this->document_hash, 0, 10).'…'.substr($this->document_hash, -8) : null,
            'notes' => $this->notes,
            'rejection_reason' => $this->rejection_reason,
            'revocation_reason' => $this->revocation_reason,
            'qr' => $qrIdentity ? [
                'token' => $qrIdentity->token,
                'verification_url' => $qrIdentity->verification_url,
                'status' => $qrIdentity->status?->value ?? (string) $qrIdentity->status,
            ] : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
