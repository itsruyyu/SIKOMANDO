import React, { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import {
  getMonitoringRecord,
  checkMonitoringItem,
  completeMonitoringRecord,
} from '../api/monitoring';
import { getProposal } from '../api/proposals';
import LoadingSpinner from '../components/common/LoadingSpinner';
import StatusBadge from '../components/common/StatusBadge';
import {
  ArrowLeftIcon,
  CheckCircleIcon,
  QrCodeIcon,
  ExclamationCircleIcon,
  DocumentCheckIcon,
} from '@heroicons/react/24/outline';

export default function MonitoringDetailPage() {
  const { proposalId, recordId } = useParams();
  const navigate = useNavigate();

  const [record, setRecord] = useState(null);
  const [proposal, setProposal] = useState(null);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState('');
  const [successMsg, setSuccessMsg] = useState('');

  async function loadData() {
    try {
      const [recRes, propRes] = await Promise.allSettled([
        getMonitoringRecord(recordId),
        getProposal(proposalId),
      ]);

      if (recRes.status === 'fulfilled') setRecord(recRes.value?.data || recRes.value);
      if (propRes.status === 'fulfilled') setProposal(propRes.value?.data || propRes.value);
    } catch (err) {
      setError('Gagal memuat lembar kerja monev.');
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadData();
  }, [proposalId, recordId]);

  async function handleCheckItem(item, condition) {
    try {
      await checkMonitoringItem(recordId, {
        item_id: item.id,
        condition: condition, // good | damaged | missing
        notes: `Kondisi fisik barang: ${condition}`,
      });
      await loadData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal merekam inspeksi item.');
    }
  }

  async function handleCompleteMonev() {
    if (!window.confirm('Selesaikan laporan hasil monitoring dan evaluasi lapangan?')) return;
    setActionLoading(true);
    try {
      await completeMonitoringRecord(recordId, {
        result: 'satisfactory',
        conclusion: 'Pemanfaatan hibah berjalan optimal dan terawat dengan baik.',
      });
      setSuccessMsg('Agenda monev lapangan berhasil diselesaikan secara resmi!');
      await loadData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal merampungkan monev.');
    } finally {
      setActionLoading(false);
    }
  }

  if (loading) {
    return <LoadingSpinner text="Memuat instrumen monev lapangan..." />;
  }

  const items = record?.items || record?.inspected_items || [];
  const isCompleted = record?.status === 'completed';

  return (
    <div className="space-y-6">
      <Link
        to="/monitoring"
        className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition"
      >
        <ArrowLeftIcon className="h-3.5 w-3.5" /> Kembali ke Daftar Monev
      </Link>

      {/* Header */}
      <div className="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div className="flex items-center gap-2">
              <span className="font-mono text-xs font-semibold text-blue-600">
                Monev #{record?.id?.slice(0, 8)}
              </span>
              <StatusBadge status={record?.status || 'implementation'} />
            </div>
            <h1 className="mt-1.5 text-xl font-bold text-slate-900 sm:text-2xl">
              Lembar Cek Fisik Monev: {proposal?.title}
            </h1>
            <p className="mt-1 text-xs text-slate-500">
              Lembaga Penerima: <span className="font-semibold text-slate-700">{proposal?.organization?.name}</span> •
              Lokasi: <span className="font-semibold text-slate-700">{proposal?.organization?.address || 'Sekretariat'}</span>
            </p>
          </div>

          {!isCompleted && (
            <button
              type="button"
              onClick={handleCompleteMonev}
              disabled={actionLoading}
              className="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-slate-800 transition"
            >
              <DocumentCheckIcon className="h-4 w-4" />
              <span>Rampungkan Monev</span>
            </button>
          )}
        </div>
      </div>

      {successMsg && (
        <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-700">
          <CheckCircleIcon className="h-5 w-5 shrink-0 text-emerald-600" />
          <span>{successMsg}</span>
        </div>
      )}

      {/* Items Table */}
      <div className="rounded-2xl border border-slate-200 bg-white shadow-xs overflow-hidden">
        <div className="border-b border-slate-200 px-6 py-4">
          <h2 className="text-base font-bold text-slate-900">Pemeriksaan Fisik Barang & Sarana Bantuan (QR Traceability)</h2>
          <p className="text-xs text-slate-500">Periksa keberadaan dan kondisi fisik setiap barang yang telah dibeli menggunakan dana hibah.</p>
        </div>

        {items.length === 0 ? (
          <div className="p-8 text-center text-xs text-slate-500">
            Daftar item fisik barang terintegrasi otomatis dari paket realisasi usulan pemohon.
          </div>
        ) : (
          <table className="min-w-full divide-y divide-slate-200 text-left text-xs">
            <thead className="bg-slate-50 font-semibold uppercase text-slate-500">
              <tr>
                <th className="px-6 py-3.5">Uraian Barang / Aset</th>
                <th className="px-6 py-3.5">Kode Identitas QR</th>
                <th className="px-6 py-3.5">Kondisi Faktual</th>
                {!isCompleted && <th className="px-6 py-3.5 text-right">Aksi Pemeriksaan</th>}
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 bg-white">
              {items.map((item, idx) => (
                <tr key={item.id || idx}>
                  <td className="px-6 py-4 font-bold text-slate-800">
                    {item.item_name || `Aset Fisik #${idx + 1}`}
                  </td>
                  <td className="px-6 py-4">
                    <span className="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 font-mono text-[11px] font-bold text-blue-700 border border-blue-200">
                      <QrCodeIcon className="h-3.5 w-3.5" />
                      {item.qr_token || item.token || `QR-${item.id?.slice(0, 6)}`}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    <span
                      className={`rounded-full px-2.5 py-0.5 font-semibold text-[11px] ${
                        item.condition === 'good'
                          ? 'bg-emerald-50 text-emerald-700'
                          : item.condition === 'damaged'
                          ? 'bg-amber-50 text-amber-700'
                          : 'bg-slate-100 text-slate-600'
                      }`}
                    >
                      {item.condition === 'good'
                        ? 'Baik & Berfungsi'
                        : item.condition === 'damaged'
                        ? 'Rusak Ringan'
                        : 'Belum Dicek'}
                    </span>
                  </td>
                  {!isCompleted && (
                    <td className="px-6 py-4 text-right space-x-2">
                      <button
                        type="button"
                        onClick={() => handleCheckItem(item, 'good')}
                        className="rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-emerald-700 hover:bg-emerald-100 font-semibold"
                      >
                        Kondisi Baik
                      </button>
                      <button
                        type="button"
                        onClick={() => handleCheckItem(item, 'damaged')}
                        className="rounded-md border border-amber-200 bg-amber-50 px-2.5 py-1 text-amber-700 hover:bg-amber-100 font-semibold"
                      >
                        Rusak
                      </button>
                    </td>
                  )}
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
}

