@extends('pdf._layout')

@section('title', 'Berita Acara Survei - ' . ($survey->survey_number ?: 'Survei'))
@section('doc-title', 'Berita Acara Hasil Survei Lapangan')
@section('doc-number', 'Nomor: ' . ($survey->survey_number ?: '-'))

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
        <td class="info-label">Lokasi Survei</td>
        <td>: {{ $survey->location_name ?? '-' }} ({{ $survey->location_address ?? '-' }})</td>
    </tr>
    <tr>
        <td class="info-label">Status Survei</td>
        <td>: {{ $survey->status->label() }}</td>
    </tr>
    <tr>
        <td class="info-label">Hasil Rekomendasi</td>
        <td>: {{ $survey->result?->label() ?? 'Belum Selesai' }}</td>
    </tr>
</table>

@if($survey->summary)
<p><strong>Ringkasan Hasil Survei:</strong><br>{{ $survey->summary }}</p>
@endif

@if($survey->recommendation)
<p><strong>Rekomendasi Petugas:</strong><br>{{ $survey->recommendation }}</p>
@endif

<div class="signature-section">
    <div class="signature-box">
        <p>Petugas Surveyor Lapangan,</p>
        <br><br><br>
        <p><strong>{{ $survey->surveyor?->name ?? 'Tim Survei' }}</strong></p>
    </div>
</div>
@endsection

