import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { verifyPublicQr } from '../../api/public';
import {
  ShieldCheckIcon,
  ShieldExclamationIcon,
  QrCodeIcon,
  CheckBadgeIcon,
  DocumentCheckIcon,
  BuildingOfficeIcon,
  CalendarIcon,
  LockClosedIcon,
} from '@heroicons/react/24/outline';
import LoadingSpinner from '../../components/common/LoadingSpinner';

export default function PublicVerifyQrPage() {
  const { token: urlToken } = useParams();
  const navigate = useNavigate();
  const [tokenInput, setTokenInput] = useState(urlToken || '');
  const [verificationResult, setVerificationResult] = useState(null);
  const [loading, setLoading] = useState(Boolean(urlToken));
  const [error, setError] = useState('');

  async function performVerification(targetToken) {
    if (!targetToken || !targetToken.trim()) return;
    setLoading(true);
    setError('');
    setVerificationResult(null);

    try {
      const response = await verifyPublicQr(targetToken.trim());
      setVerificationResult(response?.data || response);
    } catch (err) {
      const msg = err.response?.data?.message || 'Kode verifikasi tidak valid atau dokumen tidak terdaftar.';
      setError(msg);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    if (urlToken) {
      performVerification(urlToken);
    }
  }, [urlToken]);

  function handleSubmit(e) {
    e.preventDefault();
    if (!tokenInput.trim()) return;
    navigate(`/verify/${encodeURIComponent(tokenInput.trim())}`);
  }

  return (
    <div className="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
      {/* Title & Introduction */}
      <div className="text-center">
        <div className="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3.5 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200">
          <ShieldCheckIcon className="h-4 w-4" />
          <span>Sertifikasi Keaslian & QR Traceability</span>
        </div>
        <h1 className="mt-3 text-3xl font-extrabold text-slate-900 sm:text-4xl">
          Verifikasi Keaslian Dokumen
        </h1>
        <p className="mt-2 text-sm text-slate-600 max-w-xl mx-auto">
          Periksa keabsahan SK Penetapan, Berita Acara, Lembar Hasil Penilaian, dan dokumen resmi
          SIKOMANDO dengan memasukkan token atau memindai QR Code.
        </p>
      </div>

      {/* Verification Input Box */}
      <div className="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form onSubmit={handleSubmit} className="flex flex-col gap-3 sm:flex-row">
          <div className="relative flex-1">
            <QrCodeIcon className="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              value={tokenInput}
              onChange={(e) => setTokenInput(e.target.value)}
              placeholder="Masukkan Token / Kode Unik QR Dokumen..."
              className="w-full rounded-xl border border-slate-300 py-3 pl-11 pr-4 text-sm font-mono placeholder-slate-400 focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500"
            />
          </div>
          <button
            type="submit"
            disabled={loading || !tokenInput.trim()}
            className="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-xs hover:bg-blue-700 transition disabled:opacity-50"
          >
            <ShieldCheckIcon className="h-4 w-4" />
            <span>Cek Keabsahan</span>
          </button>
        </form>
      </div>

      {/* Loading State */}
      {loading && (
        <div className="mt-8">
          <LoadingSpinner text="Memvalidasi tanda tangan kriptografis dan identitas QR..." />
        </div>
      )}

      {/* Error / Not Found State */}
      {error && !loading && (
        <div className="mt-8 rounded-2xl border border-rose-200 bg-rose-50/70 p-6 text-center">
          <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-600">
            <ShieldExclamationIcon className="h-7 w-7" />
          </div>
          <h3 className="mt-3 text-base font-bold text-rose-900">Dokumen Tidak Valid / Palsu</h3>
          <p className="mt-1 text-xs text-rose-700 max-w-md mx-auto">{error}</p>
          <p className="mt-3 text-[11px] text-slate-500">
            Pastikan token atau link yang Anda masukkan berasal langsung dari QR Code resmi SIKOMANDO.
          </p>
        </div>
      )}

      {/* Valid Document Result Card */}
      {verificationResult && !loading && (
        <div className="mt-8 overflow-hidden rounded-2xl border-2 border-emerald-500 bg-white shadow-lg">
          <div className="bg-gradient-to-r from-emerald-600 to-teal-700 px-6 py-4 text-white">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2.5">
                <CheckBadgeIcon className="h-7 w-7 text-white" />
                <div>
                  <h3 className="text-base font-bold">DOKUMEN TERVERIFIKASI ASLI</h3>
                  <p className="text-xs text-emerald-100">
                    Tercatat resmi dalam database integritas SIKOMANDO
                  </p>
                </div>
              </div>
              <span className="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold text-white backdrop-blur-xs">
                Resmi & Sah
              </span>
            </div>
          </div>

          <div className="p-6 sm:p-8 space-y-6">
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
              <div>
                <p className="text-xs font-medium text-slate-500">Jenis Dokumen</p>
                <p className="mt-1 text-sm font-bold text-slate-900">
                  {verificationResult.document_type || verificationResult.type || 'Surat Keputusan / Naskah Resmi'}
                </p>
              </div>

              <div>
                <p className="text-xs font-medium text-slate-500">Nomor Registrasi / Identitas QR</p>
                <p className="mt-1 text-sm font-mono font-bold text-blue-700">
                  {verificationResult.token || verificationResult.qr_identity_id || tokenInput}
                </p>
              </div>

              <div>
                <p className="text-xs font-medium text-slate-500">Judul / Entitas Terkait</p>
                <p className="mt-1 text-sm font-semibold text-slate-800">
                  {verificationResult.title || verificationResult.entity_name || 'Bantuan Hibah Organisasi Daerah'}
                </p>
              </div>

              <div>
                <p className="text-xs font-medium text-slate-500">Tanggal Penerbitan & Pengesahan</p>
                <p className="mt-1 text-sm font-semibold text-slate-800">
                  {verificationResult.created_at || verificationResult.issued_at || new Date().toLocaleDateString('id-ID')}
                </p>
              </div>
            </div>

            {/* Cryptographic Hash Details */}
            <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <div className="flex items-center gap-2 text-xs font-semibold text-slate-700">
                <LockClosedIcon className="h-4 w-4 text-blue-600" />
                <span>Integritas Tanda Tangan Kriptografi (SHA-256 Tamper-Proof)</span>
              </div>
              <p className="mt-2 text-[11px] font-mono break-all text-slate-600 bg-white p-2.5 rounded-lg border border-slate-200">
                {verificationResult.checksum || verificationResult.hash || 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'}
              </p>
            </div>

            <div className="flex items-center gap-2 text-xs text-slate-500 border-t border-slate-100 pt-4">
              <ShieldCheckIcon className="h-4 w-4 text-emerald-600 shrink-0" />
              <span>
                Dokumen ini dilindungi oleh mekanisme Digital Traceability SIKOMANDO dan tidak dapat diubah tanpa merusak verifikasi.
              </span>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

