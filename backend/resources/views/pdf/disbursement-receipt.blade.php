@extends('pdf._layout')

@section('title', 'Bukti Pencairan - ' . ($disbursement->disbursement_number ?: 'Pencairan'))
@section('doc-title', 'Tanda Bukti Penyaluran Dana Hibah')
@section('doc-number', 'Nomor: ' . ($disbursement->disbursement_number ?: '-'))

@section('content')
<table class="info-table">
    <tr>
        <td class="info-label">Proposal</td>
        <td>: {{ $proposal->title }} ({{ $proposal->proposal_number ?: '-' }})</td>
    </tr>
    <tr>
        <td class="info-label">Penerima Manfaat</td>
        <td>: {{ $proposal->organization?->name ?? '-' }}</td>
    </tr>
    <tr>
        <td class="info-label">Tahap Pencairan</td>
        <td>: Tahap ke-{{ $disbursement->stage_number ?? 1 }}</td>
    </tr>
    <tr>
        <td class="info-label">Nominal Disetujui</td>
        <td>: Rp {{ number_format((float) $disbursement->approved_amount, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td class="info-label">Nominal Dicairkan</td>
        <td>: <strong>Rp {{ number_format((float) ($disbursement->paid_amount ?: $disbursement->approved_amount), 0, ',', '.') }}</strong></td>
    </tr>
    <tr>
        <td class="info-label">Status Pencairan</td>
        <td>: {{ $disbursement->status->label() }}</td>
    </tr>
    @if($disbursement->paid_date)
    <tr>
        <td class="info-label">Tanggal Penyaluran</td>
        <td>: {{ $disbursement->paid_date->format('d/m/Y') }}</td>
    </tr>
    @endif
    @if($disbursement->bank_name)
    <tr>
        <td class="info-label">Rekening Tujuan</td>
        <td>: {{ $disbursement->bank_name }} - {{ $disbursement->bank_account_holder }} ({{ $disbursement->bank_account_number }})</td>
    </tr>
    @endif
</table>

@if($disbursement->transactions->isNotEmpty())
<h4 style="margin-top: 15px; margin-bottom: 5px;">Rincian Transaksi Penyaluran</h4>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 30px;">No</th>
            <th>No. Referensi</th>
            <th class="text-right">Nominal</th>
            <th>Tanggal</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($disbursement->transactions as $index => $trx)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td>{{ $trx->reference_number ?: '-' }}</td>
            <td class="text-right">Rp {{ number_format((float) $trx->amount, 0, ',', '.') }}</td>
            <td>{{ $trx->transaction_date?->format('d/m/Y') ?? '-' }}</td>
            <td>{{ $trx->status ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="signature-section">
    <div class="signature-box">
        <p>Bendahara / Pejabat Keuangan,</p>
        <br><br><br>
        <p><strong>Bagian Keuangan SIKOMANDO</strong></p>
    </div>
</div>
@endsection

