@extends('pdf._layout')

@section('title', 'Laporan Verifikasi - ' . ($verification->verification_number ?: 'Verifikasi'))
@section('doc-title', 'Berita Acara Verifikasi Administrasi')
@section('doc-number', 'Nomor: ' . ($verification->verification_number ?: '-'))

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
        <td class="info-label">Status Verifikasi</td>
        <td>: {{ $verification->status->label() }}</td>
    </tr>
    <tr>
        <td class="info-label">Hasil Verifikasi</td>
        <td>: {{ $verification->result?->label() ?? 'Belum Selesai' }}</td>
    </tr>
    @if($verification->completed_at)
    <tr>
        <td class="info-label">Tanggal Selesai</td>
        <td>: {{ $verification->completed_at->format('d/m/Y H:i') }}</td>
    </tr>
    @endif
</table>

@if($verification->summary)
<p><strong>Ringkasan:</strong><br>{{ $verification->summary }}</p>
@endif

@if($verification->items->isNotEmpty())
<h4 style="margin-top: 15px; margin-bottom: 5px;">Daftar Kelengkapan Dokumen Persyaratan</h4>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 30px;">No</th>
            <th>Persyaratan</th>
            <th style="width: 80px;">Status</th>
            <th>Catatan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($verification->items as $index => $item)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td>{{ $item->requirement?->name ?? 'Persyaratan' }}</td>
            <td class="text-center">{{ $item->status ?? '-' }}</td>
            <td>{{ $item->notes ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="signature-section">
    <div class="signature-box">
        <p>Petugas Verifikator,</p>
        <br><br><br>
        <p><strong>{{ $verification->verifier?->name ?? 'Tim Verifikasi' }}</strong></p>
    </div>
</div>
@endsection

