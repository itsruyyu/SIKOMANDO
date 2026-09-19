@extends('pdf._layout')

@section('title', 'Surat Keputusan - ' . ($decision->decision_number ?: 'SK'))
@section('doc-title', 'Surat Keputusan Penetapan Penerima Hibah')
@section('doc-number', 'Nomor: ' . ($decision->decision_number ?: '-'))

@section('content')
<p style="text-align: justify; margin-bottom: 12px;">
    Berdasarkan hasil verifikasi administrasi kelayakan berkas, evaluasi teknis dan skoring pertimbangan TAPD, serta hasil survei peninjauan lapangan yang telah dituangkan dalam Berita Acara, dengan ini menetapkan keputusan resmi Pemerintah Provinsi Sulawesi Utara terhadap permohonan belanja hibah daerah sebagai berikut:
</p>

<table class="info-table" style="margin-bottom: 16px;">
    <tr>
        <td class="info-label">Program Hibah</td>
        <td>: {{ $proposal->grantProgram?->name ?? '-' }} (T.A. {{ $proposal->grantProgram?->fiscal_year ?? date('Y') }})</td>
    </tr>
    <tr>
        <td class="info-label">Judul Kegiatan / Usulan</td>
        <td>: <strong>{{ $proposal->title }}</strong></td>
    </tr>
    <tr>
        <td class="info-label">Lembaga / Badan Penerima</td>
        <td>: {{ $proposal->organization?->name ?? '-' }}</td>
    </tr>
    <tr>
        <td class="info-label">Alamat Organisasi</td>
        <td>: {{ $proposal->organization?->address ?? 'Provinsi Sulawesi Utara' }}</td>
    </tr>
    <tr>
        <td class="info-label">Status Keputusan</td>
        <td>: <span style="font-weight: bold; color: {{ ($decision->result?->value === 'rejected' || $decision->result?->value === 'cancelled') ? '#dc2626' : '#15803d' }};">
            {{ $decision->result?->label() ?? 'DISETUJUI' }}
        </span></td>
    </tr>
    <tr>
        <td class="info-label">Nominal Usulan</td>
        <td>: Rp {{ number_format((float) ($requestedAmount ?? $proposal->requested_amount ?? 0), 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td class="info-label">Nominal Penetapan (SK)</td>
        <td>: <strong style="font-size: 11pt; color: #1e3a8a;">Rp {{ number_format((float) ($approvedAmount ?? $decision->approved_amount ?? 0), 0, ',', '.') }}</strong></td>
    </tr>
    <tr>
        <td class="info-label">Terbilang</td>
        <td>: <em style="font-size: 9pt; color: #334155;">{{ $terbilang ?? '-' }} Rupiah</em></td>
    </tr>
    <tr>
        <td class="info-label">Tanggal Penetapan</td>
        <td>: {{ $decision->decision_date?->format('d F Y') ?? ($decision->issued_at?->format('d F Y') ?? now()->format('d F Y')) }}</td>
    </tr>
</table>

@if($decision->notes)
<div style="background-color: #f8fafc; border-left: 3px solid #3b82f6; padding: 6px 12px; margin-bottom: 20px; font-size: 9pt;">
    <strong>Catatan / Ketentuan Khusus:</strong><br>
    {{ $decision->notes }}
</div>
@endif

<!-- DUAL QR CODE SIGNATURE AND VERIFICATION FOOTER -->
<div class="signature-section" style="margin-top: 25px;">
    <table style="width: 100%; border-collapse: collapse; border: none;">
        <tr>
            <!-- SISI KIRI: QR Surat (Keabsahan Dokumen SK) -->
            <td style="width: 50%; vertical-align: top; text-align: left; padding-right: 15px;">
                <div style="border: 1px solid #cbd5e0; padding: 8px 10px; border-radius: 6px; background-color: #f8fafc; width: 220px;">
                    <div style="font-size: 8pt; font-weight: bold; margin-bottom: 4px; color: #1e293b; text-transform: uppercase; letter-spacing: 0.3px;">
                        Keabsahan Dokumen SK
                    </div>
                    @if(!empty($qrSuratBase64))
                        <div style="text-align: center; margin: 4px 0;">
                            <img src="{{ $qrSuratBase64 }}" width="90" height="90" style="display: block; margin: 0 auto;" alt="QR Dokumen SK" />
                        </div>
                    @endif
                    <div style="margin-top: 4px; font-size: 7.5pt; font-family: monospace; color: #334155; line-height: 1.3;">
                        <strong>No:</strong> {{ $decision->decision_number ?: '-' }}
                    </div>
                    <div style="font-size: 6.5pt; color: #2563eb; margin-top: 3px; word-break: break-all;">
                        <strong>Verifikasi:</strong> {{ $verificationUrl ?? url('/verify/' . ($qr?->token ?? '')) }}
                    </div>
                    @if(isset($qr) && $qr)
                    <div style="font-size: 6.5pt; color: #64748b; margin-top: 2px;">
                        Token: {{ substr($qr->token, 0, 20) }}...
                    </div>
                    @endif
                </div>
            </td>

            <!-- SISI KANAN: Tanda Tangan Pejabat Pengesah -->
            <td style="width: 50%; vertical-align: top; text-align: center; padding-left: 15px;">
                <p style="margin: 0; font-size: 9pt;">Ditetapkan di Manado,</p>
                <p style="margin: 2px 0 0 0; font-size: 9pt;">Pada tanggal {{ $decision->decision_date?->format('d F Y') ?? ($decision->issued_at?->format('d F Y') ?? now()->format('d F Y')) }}</p>
                <p style="font-weight: bold; margin: 6px 0 6px 0; font-size: 9.5pt; text-transform: uppercase; letter-spacing: 0.5px;">GUBERNUR SULAWESI UTARA</p>
                
                @if(!empty($qrTtdBase64))
                    <div style="margin: 4px auto;">
                        <img src="{{ $qrTtdBase64 }}" width="90" height="90" style="display: block; margin: 0 auto;" alt="QR Tanda Tangan Digital" />
                    </div>
                    <div style="font-size: 7pt; color: #059669; font-weight: bold; margin-bottom: 4px;">
                        [ Tanda Tangan Elektronik Tersertifikasi ]
                    </div>
                @else
                    <div style="height: 90px;"></div>
                @endif

                <p style="font-weight: bold; text-decoration: underline; margin: 4px 0 2px 0; font-size: 9.5pt; color: #0f172a;">
                    {{ $decision->decider?->name ?? 'Mayjen TNI (Purn) Yulius Selvanus, SE.' }}
                </p>
                <p style="margin: 0; font-size: 8pt; color: #475569;">
                    Kepala Daerah Provinsi Sulawesi Utara
                </p>
            </td>
        </tr>
    </table>
</div>
@endsection
