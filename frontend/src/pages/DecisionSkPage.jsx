import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  getDecisions,
  getDecision,
  generateDecisionDocument,
  getDecisionPdfUrl,
} from '../api/decisions';
import DataTable from '../components/common/DataTable';
import StatusBadge from '../components/common/StatusBadge';
import LoadingSpinner from '../components/common/LoadingSpinner';
import Modal from '../components/common/Modal';
import {
  DocumentDuplicateIcon,
  PrinterIcon,
  SparklesIcon,
  CheckBadgeIcon,
  QrCodeIcon,
} from '@heroicons/react/24/outline';

export default function DecisionSkPage() {
  const { decisionId } = useParams();
  const navigate = useNavigate();

  const [decisions, setDecisions] = useState([]);
  const [selectedDecision, setSelectedDecision] = useState(null);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [successMsg, setSuccessMsg] = useState('');

  async function loadData() {
    setLoading(true);
    try {
      const res = await getDecisions();
      const list = res?.data || res || [];
      const arr = Array.isArray(list) ? list : list.data || [];
      setDecisions(arr);

      const targetId = decisionId || (arr.length > 0 ? arr[0].id : null);
      if (targetId) {
        const detailRes = await getDecision(targetId);
        setSelectedDecision(detailRes?.data || detailRes);
      }
    } catch (err) {
      console.error('Failed to load decisions', err);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadData();
  }, [decisionId]);

  async function handleGenerateDocument(id) {
    setActionLoading(true);
    try {
      await generateDecisionDocument(id);
      setSuccessMsg('Naskah dokumen SK Penetapan resmi ber-QR berhasil di-generate!');
      await loadData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menghasilkan dokumen SK.');
    } finally {
      setActionLoading(false);
    }
  }

  const columns = [
    {
      title: 'Nomor Surat Keputusan (SK)',
      key: 'decision_number',
      render: (val, row) => (
        <div>
          <p className="font-bold text-slate-900">{val || `SK/${row.fiscal_year || '2026'}/${row.id?.slice(0, 6)}`}</p>
          <p className="text-xs text-slate-500">{row.title || 'Penetapan Penerima Bantuan Hibah'}</p>
        </div>
      ),
    },
    {
      title: 'Tahun Anggaran',
      key: 'fiscal_year',
      render: (val) => (
        <span className="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
          TA {val || '2026'}
        </span>
      ),
    },
    {
      title: 'Total Pagu SK (Rp)',
      key: 'total_amount',
      render: (val) => (
        <span className="font-bold text-emerald-700">
          {val ? `Rp ${Number(val).toLocaleString('id-ID')}` : '—'}
        </span>
      ),
    },
    {
      title: 'Status SK',
      key: 'status',
      render: (val) => <StatusBadge status={val || 'approved'} />,
    },
    {
      title: 'Aksi',
      key: 'action',
      render: (_, row) => (
        <button
          type="button"
          onClick={() => {
            setSelectedDecision(row);
            navigate(`/decisions/${row.id}`);
          }}
          className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
        >
          Lihat Lampiran
        </button>
      ),
    },
  ];

  const recipients = selectedDecision?.recipients || selectedDecision?.items || [];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Manajemen SK Penetapan Hibah</h1>
          <p className="text-xs text-slate-500 mt-1">
            Penerbitan naskah Surat Keputusan (SK) resmi, lampiran daftar penerima, dan dokumen berkode QR traceability.
          </p>
        </div>

        {selectedDecision && (
          <div className="flex items-center gap-3">
            <button
              type="button"
              onClick={() => handleGenerateDocument(selectedDecision.id)}
              disabled={actionLoading}
              className="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-blue-700 transition disabled:opacity-50"
            >
              <SparklesIcon className="h-4 w-4" />
              <span>Generate Naskah SK</span>
            </button>

            <a
              href={`http://127.0.0.1:8000/api/v1/pdf/decisions/${selectedDecision.id}`}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition"
            >
              <PrinterIcon className="h-4 w-4 text-slate-500" />
              <span>Cetak SK PDF</span>
            </a>
          </div>
        )}
      </div>

      {successMsg && (
        <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-700">
          <CheckBadgeIcon className="h-5 w-5 shrink-0 text-emerald-600" />
          <span>{successMsg}</span>
        </div>
      )}

      {/* Decision SK List Table */}
      <DataTable
        columns={columns}
        data={decisions}
        loading={loading}
        searchPlaceholder="Cari nomor SK atau judul penetapan..."
        emptyTitle="Belum Ada SK Diterbitkan"
        emptyDescription="SK Penetapan diterbitkan setelah usulan disetujui oleh pimpinan."
      />

      {/* Selected Decision Detail Card */}
      {selectedDecision && (
        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
          <div className="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
              <span className="text-xs font-bold text-blue-600 font-mono">
                {selectedDecision.decision_number || 'Draft SK'}
              </span>
              <h2 className="text-base font-bold text-slate-900 mt-1">
                Lampiran Daftar Organisasi Penerima Hibah
              </h2>
            </div>
            <div className="flex items-center gap-2 text-xs font-semibold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
              <QrCodeIcon className="h-4 w-4" />
              <span>Terproteksi QR Traceability</span>
            </div>
          </div>

          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-slate-200 text-left text-xs">
              <thead className="bg-slate-50 font-semibold uppercase text-slate-500">
                <tr>
                  <th className="px-6 py-3.5">No</th>
                  <th className="px-6 py-3.5">Nama Lembaga / Organisasi</th>
                  <th className="px-6 py-3.5">Judul Usulan Bantuan</th>
                  <th className="px-6 py-3.5 text-right">Alokasi SK (Rp)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 bg-white">
                {recipients.length === 0 ? (
                  <tr>
                    <td colSpan={4} className="px-6 py-6 text-center text-slate-400">
                      Daftar penerima terintegrasi otomatis dari usulan yang disetujui pimpinan.
                    </td>
                  </tr>
                ) : (
                  recipients.map((rec, idx) => (
                    <tr key={rec.id || idx}>
                      <td className="px-6 py-4 font-bold text-slate-600">{idx + 1}</td>
                      <td className="px-6 py-4 font-bold text-slate-900">
                        {rec.organization_name || rec.organization?.name || 'Organisasi'}
                      </td>
                      <td className="px-6 py-4 text-slate-600">
                        {rec.proposal_title || rec.title || 'Usulan Kegiatan'}
                      </td>
                      <td className="px-6 py-4 text-right font-bold text-emerald-700">
                        Rp {Number(rec.approved_amount || rec.amount || 0).toLocaleString('id-ID')}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}

