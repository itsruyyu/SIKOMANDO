@extends('pdf._layout')

@section('title', 'Laporan Pertanggungjawaban - ' . ($lpj->lpj_number ?: 'LPJ'))
@section('doc-title', 'Laporan Pertanggungjawaban Dana Hibah (LPJ)')
@section('doc-number', 'Nomor: ' . ($lpj->lpj_number ?: '-'))

@section('content')
<table class="info-table">
    <tr>
        <td class="info-label">Proposal</td>
        <td>: {{ $proposal->title }} ({{ $proposal->proposal_number ?: '-' }})</td>
    </tr>
    <tr>
        <td class="info-label">Organisasi</td>
        <td>: {{ $proposal->organization?->name ?? '-' }}</td>
    </tr>
    <tr>
        <td class="info-label">Status LPJ</td>
        <td>: {{ $lpj->status->label() }}</td>
    </tr>
    <tr>
        <td class="info-label">Dana Diterima</td>
        <td>: Rp {{ number_format((float) $lpj->received_amount, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td class="info-label">Dana Digunakan</td>
        <td>: Rp {{ number_format((float) $lpj->spent_amount, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td class="info-label">Sisa Saldo</td>
        <td>: Rp {{ number_format((float) ($lpj->received_amount - $lpj->spent_amount), 0, ',', '.') }}</td>
    </tr>
</table>

@if($lpj->activity_report)
<p><strong>Laporan Pelaksanaan Kegiatan:</strong><br>{{ $lpj->activity_report }}</p>
@endif

@if($lpj->items->isNotEmpty())
<h4 style="margin-top: 15px; margin-bottom: 5px;">Rincian Realisasi Penggunaan Anggaran</h4>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 30px;">No</th>
            <th>Item Kegiatan</th>
            <th class="text-right">Anggaran</th>
            <th class="text-right">Realisasi</th>
            <th class="text-right">Selisih</th>
        </tr>
    </thead>
    <tbody>
        @foreach($lpj->items as $index => $item)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td>{{ $item->item_name }}</td>
            <td class="text-right">Rp {{ number_format((float) $item->allocated_amount, 0, ',', '.') }}</td>
            <td class="text-right">Rp {{ number_format((float) $item->realized_amount, 0, ',', '.') }}</td>
            <td class="text-right">Rp {{ number_format((float) ($item->allocated_amount - $item->realized_amount), 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="signature-section">
    <div class="signature-box">
        <p>Pimpinan Organisasi Penerima,</p>
        <br><br><br>
        <p><strong>{{ $proposal->organization?->name ?? 'Penerima Hibah' }}</strong></p>
    </div>
</div>
@endsection

