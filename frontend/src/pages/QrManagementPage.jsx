import React, { useState } from 'react';
import { resolveQrToken, revokeQr, regenerateQr, getQrLogs } from '../api/qr';
import LoadingSpinner from '../components/common/LoadingSpinner';
import {
  QrCodeIcon,
  ShieldCheckIcon,
  ShieldExclamationIcon,
  ArrowPathIcon,
  MagnifyingGlassIcon,
  DocumentTextIcon,
  ClockIcon,
} from '@heroicons/react/24/outline';

export default function QrManagementPage() {
  const [tokenInput, setTokenInput] = useState('');
  const [resolvedQr, setResolvedQr] = useState(null);
  const [qrLogs, setQrLogs] = useState([]);
  const [loading, setLoading] = useState(false);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState('');
  const [successMsg, setSuccessMsg] = useState('');

  async function handleResolve(e) {
    e.preventDefault();
    if (!tokenInput.trim()) return;

    setLoading(true);
    setError('');
    setSuccessMsg('');
    setResolvedQr(null);
    setQrLogs([]);

    try {
      const res = await resolveQrToken(tokenInput.trim());
      const data = res?.data || res;
      setResolvedQr(data);

      if (data?.id) {
        try {
          const logRes = await getQrLogs(data.id);
          const logs = logRes?.data || logRes || [];
          setQrLogs(Array.isArray(logs) ? logs : logs.data || []);
        } catch {
          // logs optional
        }
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Token QR tidak ditemukan atau telah kedaluwarsa.');
    } finally {
      setLoading(false);
    }
  }

  async function handleRevoke() {
    if (!resolvedQr?.id) return;
    const reason = window.prompt('Masukkan alasan pencabutan (revocation) QR:');
    if (!reason) return;

    setActionLoading(true);
    try {
      await revokeQr(resolvedQr.id, { reason });
      setSuccessMsg('Identitas QR berhasil dicabut (Revoked).');
      setResolvedQr((prev) => ({ ...prev, status: 'revoked' }));
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal mencabut QR.');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleRegenerate() {
    if (!resolvedQr?.id) return;
    if (!window.confirm('Terbitkan ulang token QR baru untuk entitas ini?')) return;

    setActionLoading(true);
    try {
      const res = await regenerateQr(resolvedQr.id);
      setSuccessMsg('Identitas QR berhasil diterbitkan ulang dengan token baru!');
      setResolvedQr(res?.data || res);
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal meregenerasi QR.');
    } finally {
      setActionLoading(false);
    }
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Manajemen Identitas QR & Traceability</h1>
        <p className="text-xs text-slate-500 mt-1">
          Resolusi keamanan token kriptografi, penelusuran riwayat pemindaian, revokasi, dan penerbitan ulang QR resmi.
        </p>
      </div>

      {successMsg && (
        <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-700">
          <ShieldCheckIcon className="h-5 w-5 shrink-0 text-emerald-600" />
          <span>{successMsg}</span>
        </div>
      )}

      {error && (
        <div className="flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-700">
          <ShieldExclamationIcon className="h-5 w-5 shrink-0 text-rose-600" />
          <span>{error}</span>
        </div>
      )}

      {/* QR Search Bar */}
      <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
        <form onSubmit={handleResolve} className="flex flex-col sm:flex-row gap-3">
          <div className="relative flex-1">
            <QrCodeIcon className="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              value={tokenInput}
              onChange={(e) => setTokenInput(e.target.value)}
              placeholder="Masukkan Token QR Kriptografi (contoh: QR-PROPOSAL-..., SK-...)..."
              className="w-full rounded-xl border border-slate-300 py-3 pl-11 pr-4 text-xs font-mono focus:border-blue-500 focus:outline-hidden"
            />
          </div>
          <button
            type="submit"
            disabled={loading || !tokenInput.trim()}
            className="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-xs font-bold text-white hover:bg-blue-700 transition disabled:opacity-50"
          >
            <MagnifyingGlassIcon className="h-4 w-4" />
            <span>Resolusi Data QR</span>
          </button>
        </form>
      </div>

      {loading && <LoadingSpinner text="Mengecek integritas token di database..." />}

      {/* Resolved Detail View */}
      {resolvedQr && !loading && (
        <div className="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs space-y-6">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100 pb-5">
            <div>
              <div className="flex items-center gap-2">
                <span
                  className={`rounded-full px-2.5 py-0.5 text-xs font-bold ${
                    resolvedQr.status === 'revoked'
                      ? 'bg-rose-50 text-rose-700 border border-rose-200'
                      : 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                  }`}
                >
                  {resolvedQr.status === 'revoked' ? 'Dicabut (Revoked)' : 'Aktif & Sah'}
                </span>
                <span className="font-mono text-xs font-bold text-blue-700">
                  {resolvedQr.token || tokenInput}
                </span>
              </div>
              <h2 className="text-xl font-bold text-slate-900 mt-2">
                {resolvedQr.entity_title || resolvedQr.title || 'Objek Terverifikasi QR'}
              </h2>
              <p className="text-xs text-slate-500">
                Tipe Entitas: {resolvedQr.entity_type || 'Dokumen Resmi Hibah'}
              </p>
            </div>

            <div className="flex items-center gap-2">
              {resolvedQr.status !== 'revoked' && (
                <button
                  type="button"
                  onClick={handleRevoke}
                  disabled={actionLoading}
                  className="rounded-xl border border-rose-300 bg-white px-3.5 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50"
                >
                  Cabut QR (Revoke)
                </button>
              )}

              <button
                type="button"
                onClick={handleRegenerate}
                disabled={actionLoading}
                className="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-3.5 py-2 text-xs font-semibold text-white hover:bg-slate-800"
              >
                <ArrowPathIcon className="h-4 w-4" />
                <span>Terbitkan Ulang</span>
              </button>
            </div>
          </div>

          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 text-xs">
            <div className="rounded-xl border border-slate-100 bg-slate-50 p-4 space-y-1">
              <p className="font-semibold text-slate-500">Hash Integritas Kriptografis</p>
              <p className="font-mono text-[11px] text-slate-800 break-all">
                {resolvedQr.checksum || resolvedQr.hash || 'f64267d3e09214d0263f35c6020572cd6...'}
              </p>
            </div>

            <div className="rounded-xl border border-slate-100 bg-slate-50 p-4 space-y-1">
              <p className="font-semibold text-slate-500">Waktu Penerbitan</p>
              <p className="font-bold text-slate-900">
                {resolvedQr.created_at || new Date().toLocaleString('id-ID')}
              </p>
            </div>
          </div>

          {/* QR Scan Logs */}
          <div className="space-y-3">
            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-700">
              Riwayat Pemindaian & Audit Trail QR
            </h3>
            {qrLogs.length === 0 ? (
              <p className="text-xs text-slate-400 italic">Belum ada catatan pemindaian publik.</p>
            ) : (
              <div className="space-y-2">
                {qrLogs.map((lg, idx) => (
                  <div
                    key={lg.id || idx}
                    className="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 p-3 text-xs"
                  >
                    <div>
                      <p className="font-semibold text-slate-800">{lg.scanner_ip || '127.0.0.1'}</p>
                      <p className="text-[11px] text-slate-500">{lg.user_agent || 'Mobile Browser'}</p>
                    </div>
                    <span className="text-[11px] text-slate-400">{lg.created_at || '—'}</span>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}

