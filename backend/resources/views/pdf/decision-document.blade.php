@extends('pdf._layout')

@section('title', 'Surat Keputusan - ' . ($decision->decision_number ?: 'SK'))
@section('doc-title', 'Surat Keputusan Penetapan Penerima Hibah')
@section('doc-number', 'Nomor: ' . ($decision->decision_number ?: '-'))

@section('content')
<p style="text-align: justify;">
    Berdasarkan hasil verifikasi administrasi, evaluasi kelayakan, dan survei lapangan yang telah dilaksanakan,
    dengan ini menetapkan keputusan resmi terhadap usulan proposal bantuan hibah sebagai berikut:
</p>

<table class="info-table">
    <tr>
        <td class="info-label">Program Hibah</td>
        <td>: {{ $proposal->grantProgram?->name ?? '-' }} (TA {{ $proposal->grantProgram?->fiscal_year ?? '-' }})</td>
    </tr>
    <tr>
        <td class="info-label">Judul Usulan</td>
        <td>: {{ $proposal->title }}</td>
    </tr>
    <tr>
        <td class="info-label">Lembaga Penerima</td>
        <td>: {{ $proposal->organization?->name ?? '-' }}</td>
    </tr>
    <tr>
        <td class="info-label">Keputusan</td>
        <td>: <strong>{{ $decision->result?->label() ?? '-' }}</strong></td>
    </tr>
    <tr>
        <td class="info-label">Nominal Usulan</td>
        <td>: Rp {{ number_format((float) $proposal->requested_amount, 0, ',', '.') }}</td>
    </tr>
    @if($decision->approved_amount)
    <tr>
        <td class="info-label">Nominal Disetujui</td>
        <td>: <strong>Rp {{ number_format((float) $decision->approved_amount, 0, ',', '.') }}</strong></td>
    </tr>
    @endif
    <tr>
        <td class="info-label">Tanggal Penetapan</td>
        <td>: {{ $decision->decided_at?->format('d F Y') ?? now()->format('d F Y') }}</td>
    </tr>
</table>

@if($decision->notes)
<p><strong>Catatan Keputusan:</strong><br>{{ $decision->notes }}</p>
@endif

<div class="signature-section">
    <div class="signature-box">
        <p>Pejabat Yang Menetapkan,</p>
        <br><br><br>
        <p><strong>{{ $decision->decider?->name ?? 'Pejabat Berwenang' }}</strong></p>
    </div>
</div>
@endsection

