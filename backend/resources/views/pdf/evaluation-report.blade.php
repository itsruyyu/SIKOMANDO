@extends('pdf._layout')

@section('title', 'Laporan Evaluasi - ' . ($evaluation->evaluation_number ?: 'Evaluasi'))
@section('doc-title', 'Hasil Evaluasi Kelayakan Proposal')
@section('doc-number', 'Nomor: ' . ($evaluation->evaluation_number ?: '-'))

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
        <td class="info-label">Status Evaluasi</td>
        <td>: {{ $evaluation->status->label() }}</td>
    </tr>
    <tr>
        <td class="info-label">Hasil</td>
        <td>: {{ $evaluation->result?->label() ?? 'Belum Selesai' }}</td>
    </tr>
    @if($evaluation->final_score !== null)
    <tr>
        <td class="info-label">Skor Akhir</td>
        <td>: <strong>{{ number_format((float) $evaluation->final_score, 2) }}</strong></td>
    </tr>
    @endif
</table>

@if($evaluation->summary)
<p><strong>Kesimpulan Evaluasi:</strong><br>{{ $evaluation->summary }}</p>
@endif

@if($evaluation->items->isNotEmpty())
<h4 style="margin-top: 15px; margin-bottom: 5px;">Rincian Penilaian Kriteria</h4>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 30px;">No</th>
            <th>Kriteria Penilaian</th>
            <th class="text-right" style="width: 70px;">Bobot</th>
            <th class="text-right" style="width: 70px;">Skor</th>
            <th>Catatan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($evaluation->items as $index => $item)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td>{{ $item->criteria?->name ?? 'Kriteria' }}</td>
            <td class="text-right">{{ $item->weight ?? '-' }}</td>
            <td class="text-right">{{ $item->score ?? '-' }}</td>
            <td>{{ $item->notes ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="signature-section">
    <div class="signature-box">
        <p>Petugas Evaluator,</p>
        <br><br><br>
        <p><strong>{{ $evaluation->evaluator?->name ?? 'Tim Evaluasi' }}</strong></p>
    </div>
</div>
@endsection

