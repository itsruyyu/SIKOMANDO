<?php

namespace Database\Seeders;

use App\Enums\QrType;
use App\Models\Disbursement;
use App\Models\DisbursementPlan;
use App\Models\DisbursementTransaction;
use App\Models\Handover;
use App\Models\HandoverItem;
use App\Models\Proposal;
use App\Models\ProposalBudgetItem;
use App\Models\RealizationItem;
use App\Models\RealizationPackage;
use App\Models\Receipt;
use App\Models\User;
use App\Services\QrService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DisbursementAndRealizationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@sikomando.test')->first();
        $qrService = app(QrService::class);

        $proposals = Proposal::whereIn('status', [
            'disbursed', 'implementation', 'lpj_submitted', 'completed'
        ])->get();

        foreach ($proposals as $idx => $prop) {
            $amount = $prop->approved_amount ?? $prop->requested_amount;

            // 1. Disbursement Plan
            $plan = DisbursementPlan::updateOrCreate(
                ['proposal_id' => $prop->id],
                [
                    'created_by' => $admin->id,
                    'plan_number' => 'PLAN/'.str_replace('/', '-', $prop->proposal_number),
                    'total_stages' => 1,
                    'planned_amount' => $amount,
                    'status' => 'approved',
                    'notes' => 'Rencana pencairan dana hibah 100% sekaligus.',
                    'submitted_at' => now()->subDays(11),
                    'approved_at' => now()->subDays(10),
                ]
            );

            // 2. Disbursement (SP2D)
            $disbursement = Disbursement::updateOrCreate(
                ['proposal_id' => $prop->id],
                [
                    'disbursement_plan_id' => $plan->id,
                    'stage_number' => 1,
                    'disbursement_number' => 'SP2D-BKAD/2026/0'.($idx + 1).'/00'.($idx + 1),
                    'planned_amount' => $amount,
                    'approved_amount' => $amount,
                    'paid_amount' => $amount,
                    'status' => 'paid',
                    'bank_name' => 'Bank SulutGo (BSG)',
                    'bank_account_number' => '001021100'.rand(1000, 9999),
                    'bank_account_name' => $prop->organization->name,
                    'planned_date' => now()->subDays(10)->toDateString(),
                    'approved_date' => now()->subDays(9)->toDateString(),
                    'paid_date' => now()->subDays(8)->toDateString(),
                    'notes' => 'Penyaluran dana hibah langsung ke rekening giro resmi organisasi pada Bank SulutGo.',
                ]
            );

            // 3. Disbursement Transaction
            DisbursementTransaction::updateOrCreate(
                ['disbursement_id' => $disbursement->id],
                [
                    'recorded_by' => $admin->id,
                    'transaction_number' => 'TRX-BSG-'.strtoupper(Str::random(10)),
                    'transaction_type' => 'transfer',
                    'transaction_date' => now()->subDays(8)->toDateString(),
                    'amount' => $amount,
                    'status' => 'confirmed',
                    'bank_reference' => 'REF-BSG-'.rand(100000, 999999),
                    'recipient_name' => $disbursement->bank_account_name,
                    'bank_name' => 'Bank SulutGo (BSG)',
                    'bank_account_number' => $disbursement->bank_account_number,
                    'notes' => 'Pemindahbukuan Kasda ke rekening penerima berhasil.',
                ]
            );

            // 4. Realization Package
            $rabItem = ProposalBudgetItem::where('proposal_id', $prop->id)->first();

            $package = RealizationPackage::updateOrCreate(
                [
                    'proposal_id' => $prop->id,
                    'package_number' => 'PKG-RLZ-'.str_replace('/', '-', $prop->proposal_number),
                ],
                [
                    'name' => 'Paket Pengadaan Sarana & Pelaksanaan '.$prop->title,
                    'description' => 'Realisasi fisik belanja modal sarana prasarana sesuai naskah usulan.',
                    'status' => 'verified',
                    'package_date' => now()->subDays(7)->toDateString(),
                    'total_amount' => $amount * 0.85,
                    'created_by' => $prop->applicant_id,
                    'verified_by' => $admin->id,
                    'verified_at' => now()->subDays(5),
                ]
            );

            try {
                $qrService->generateFor($package, QrType::REALIZATION_PACKAGE, $prop->applicant, null, [
                    'package_number' => $package->package_number,
                    'total_amount' => $package->total_amount,
                ]);
            } catch (\Throwable $e) {}

            // 5. Realization Item
            $rItem = RealizationItem::updateOrCreate(
                [
                    'realization_package_id' => $package->id,
                    'item_code' => 'ITM-'.strtoupper(Str::random(8)),
                ],
                [
                    'proposal_budget_item_id' => $rabItem?->id,
                    'name' => 'Unit Komputer Desktop & Sarana Pelatihan Terverifikasi',
                    'category' => 'equipment',
                    'serial_number' => 'SN-SULUT-'.rand(10000, 99999),
                    'brand' => 'ASUS / Lenovo Pro',
                    'model' => 'Core i5 Workstation',
                    'specification' => 'Spesifikasi standar operasional pelatihan vokasi',
                    'quantity' => 5,
                    'unit' => 'Unit',
                    'unit_price' => ($amount * 0.4) / 5,
                    'total_amount' => $amount * 0.4,
                    'purchase_date' => now()->subDays(7)->toDateString(),
                    'location_name' => $prop->organization->address,
                    'latitude' => 1.4748300,
                    'longitude' => 124.8420800,
                    'condition' => 'good',
                    'status' => 'verified',
                    'created_by' => $prop->applicant_id,
                ]
            );

            try {
                $qrService->generateFor($rItem, QrType::REALIZATION_ITEM, $prop->applicant, null, [
                    'item_code' => $rItem->item_code,
                    'serial_number' => $rItem->serial_number,
                    'condition' => $rItem->condition,
                ]);
            } catch (\Throwable $e) {}

            // 6. Receipt
            $receipt = Receipt::updateOrCreate(
                [
                    'proposal_id' => $prop->id,
                    'receipt_number' => 'KW-HB/2026/0'.($idx + 1).'/00'.($idx + 1),
                ],
                [
                    'disbursement_id' => $disbursement->id,
                    'realization_package_id' => $package->id,
                    'payer_name' => $prop->organization->name,
                    'recipient_name' => 'CV. Jaya Mandiri Perkasa Manado',
                    'amount' => $amount * 0.4,
                    'receipt_date' => now()->subDays(7)->toDateString(),
                    'purpose' => 'Pembayaran lunas pengadaan sarana pelatihan dan perlengkapan kegiatan.',
                    'status' => 'verified',
                    'created_by' => $prop->applicant_id,
                ]
            );

            try {
                $qrService->generateFor($receipt, QrType::RECEIPT, $prop->applicant, null, [
                    'receipt_number' => $receipt->receipt_number,
                    'amount' => $receipt->amount,
                    'vendor' => $receipt->recipient_name,
                ]);
            } catch (\Throwable $e) {}

            // 7. Handover (BAST)
            $handover = Handover::updateOrCreate(
                [
                    'proposal_id' => $prop->id,
                    'handover_number' => 'BAST-HB/2026/0'.($idx + 1),
                ],
                [
                    'realization_package_id' => $package->id,
                    'handover_date' => now()->subDays(6)->toDateString(),
                    'giver_name' => 'Michael Karundeng',
                    'giver_position' => 'Ketua Pelaksana Pengadaan Organisasi',
                    'recipient_name' => 'Drs. Steven Kandouw, M.Si',
                    'recipient_position' => 'Kepala Badan Pengelola Keuangan Daerah',
                    'status' => 'completed',
                    'location' => $prop->organization->address,
                    'notes' => 'Berita Acara Serah Terima hasil belanja hibah telah diperiksa fisik dan berfungsi baik.',
                    'created_by' => $prop->applicant_id,
                ]
            );

            try {
                $qrService->generateFor($handover, QrType::HANDOVER, $prop->applicant, null, [
                    'handover_number' => $handover->handover_number,
                    'recipient' => $handover->recipient_name,
                ]);
            } catch (\Throwable $e) {}

            HandoverItem::updateOrCreate(
                [
                    'handover_id' => $handover->id,
                    'realization_item_id' => $rItem->id,
                ],
                [
                    'notes' => 'Kondisi barang 100% baru dan sesuai spek.',
                ]
            );
        }

        $this->command?->info('Disbursement plans, SP2D, realizations, receipts, and handovers seeded.');
    }
}
