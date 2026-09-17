@extends('pdf._layout')

@section('title', 'Ringkasan Proposal - ' . ($proposal->proposal_number ?: $proposal->title))
@section('doc-title', 'Ringkasan Usulan Proposal Hibah')
@section('doc-number', 'Nomor: ' . ($proposal->proposal_number ?: '-'))

@section('content')
<table class="info-table">
    <tr>
        <td class="info-label">Judul Proposal</td>
        <td>: {{ $proposal->title }}</td>
    </tr>
    <tr>
        <td class="info-label">Program Hibah</td>
        <td>: {{ $proposal->grantProgram?->name ?? '-' }} (TA {{ $proposal->grantProgram?->fiscal_year ?? '-' }})</td>
    </tr>
    <tr>
        <td class="info-label">Organisasi Pemohon</td>
        <td>: {{ $proposal->organization?->name ?? '-' }}</td>
    </tr>
    <tr>
        <td class="info-label">Status Proposal</td>
        <td>: {{ $proposal->status->label() }}</td>
    </tr>
    <tr>
        <td class="info-label">Nominal Diajukan</td>
        <td>: Rp {{ number_format((float) $proposal->requested_amount, 0, ',', '.') }}</td>
    </tr>
    @if($proposal->approved_amount)
    <tr>
        <td class="info-label">Nominal Disetujui</td>
        <td>: Rp {{ number_format((float) $proposal->approved_amount, 0, ',', '.') }}</td>
    </tr>
    @endif
</table>

@if($proposal->background)
<p><strong>Latar Belakang:</strong><br>{{ $proposal->background }}</p>
@endif

@if($proposal->objectives)
<p><strong>Tujuan:</strong><br>{{ $proposal->objectives }}</p>
@endif

@if($proposal->budgetItems->isNotEmpty())
<h4 style="margin-top: 15px; margin-bottom: 5px;">Rencana Anggaran Biaya (RAB)</h4>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 30px;">No</th>
            <th>Item / Kegiatan</th>
            <th class="text-right" style="width: 80px;">Volume</th>
            <th style="width: 60px;">Satuan</th>
            <th class="text-right" style="width: 100px;">Harga Satuan</th>
            <th class="text-right" style="width: 110px;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($proposal->budgetItems as $index => $item)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td>{{ $item->item_name }}</td>
            <td class="text-right">{{ $item->quantity }}</td>
            <td>{{ $item->unit }}</td>
            <td class="text-right">Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
            <td class="text-right">Rp {{ number_format((float) $item->total_price, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th colspan="5" class="text-right">Total Anggaran:</th>
            <th class="text-right">Rp {{ number_format((float) $proposal->budgetItems->sum('total_price'), 0, ',', '.') }}</th>
        </tr>
    </tfoot>
</table>
@endif
@endsection

