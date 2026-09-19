import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  getLpjs,
  getLpj,
  reviewLpj,
  requestLpjRevision,
  approveLpj,
  finalizeLpj,
  closeProposal,
  getLpjPdfUrl,
} from '../api/lpj';
import DataTable from '../components/common/DataTable';
import StatusBadge from '../components/common/StatusBadge';
import LoadingSpinner from '../components/common/LoadingSpinner';
import Modal from '../components/common/Modal';
import {
  DocumentCheckIcon,
  PrinterIcon,
  CheckBadgeIcon,
  XCircleIcon,
  LockClosedIcon,
} from '@heroicons/react/24/outline';

export default function LpjManagementPage() {
  const { lpjId } = useParams();
  const navigate = useNavigate();

  const [lpjs, setLpjs] = useState([]);
  const [selectedLpj, setSelectedLpj] = useState(null);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [successMsg, setSuccessMsg] = useState('');

  // Modals
  const [revisionModalOpen, setRevisionModalOpen] = useState(false);
  const [revisionNotes, setRevisionNotes] = useState('');

  async function loadLpjs() {
    setLoading(true);
    try {
      const res = await getLpjs();
      const list = res?.data || res || [];
      const arr = Array.isArray(list) ? list : list.data || [];
      setLpjs(arr);

      const targetId = lpjId || (arr.length > 0 ? arr[0].id : null);
      if (targetId) {
        const detailRes = await getLpj(targetId);
        setSelectedLpj(detailRes?.data || detailRes);
      }
    } catch (err) {
      console.error('Failed to load LPJ list', err);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadLpjs();
  }, [lpjId]);

  async function handleApproveLpj(id) {
    if (!window.confirm('Setujui berkas Laporan Pertanggungjawaban (LPJ) ini?')) return;
    setActionLoading(true);
    try {
      await approveLpj(id);
      setSuccessMsg('LPJ berhasil disetujui!');
      await loadLpjs();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menyetujui LPJ.');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleFinalizeAndClose(lpj) {
    if (!window.confirm('Finalisasi LPJ dan lakukan penutupan usulan hibah secara resmi?')) return;
    setActionLoading(true);
    try {
      await finalizeLpj(lpj.id);
      if (lpj.proposal_id) {
        await closeProposal(lpj.proposal_id);
      }
      setSuccessMsg('LPJ telah difinalisasi dan usulan hibah resmi ditutup (Selesai).');
      await loadLpjs();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal memfinalisasi LPJ.');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleRequestRevision(e) {
    e.preventDefault();
    if (!selectedLpj || !revisionNotes.trim()) return;
    setActionLoading(true);
    try {
      await requestLpjRevision(selectedLpj.id, {
        revision_notes: revisionNotes.trim(),
        notes: revisionNotes.trim(),
      });
      setRevisionModalOpen(false);
      setRevisionNotes('');
      setSuccessMsg('Catatan revisi LPJ telah dikirimkan ke pemohon.');
      await loadLpjs();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal meminta revisi LPJ.');
    } finally {
      setActionLoading(false);
    }
  }

  const columns = [
    {
      title: 'Proposal & Lembaga',
      key: 'proposal',
      render: (val, row) => (
        <div>
          <span className="font-mono text-xs text-blue-600 font-semibold">
            #{row.proposal?.proposal_number || row.id?.slice(0, 8)}
          </span>
          <p className="font-bold text-slate-900">{row.proposal?.title || 'Laporan LPJ Hibah'}</p>
          <p className="text-xs text-slate-500">{row.proposal?.organization?.name || 'Organisasi'}</p>
        </div>
      ),
    },
    {
      title: 'Nilai Pertanggungjawaban',
      key: 'total_spent',
      render: (val, row) => (
        <span className="font-bold text-slate-900 text-xs">
          Rp {Number(val || row.proposal?.requested_amount || 0).toLocaleString('id-ID')}
        </span>
      ),
    },
    {
      title: 'Status LPJ',
      key: 'status',
      render: (val) => <StatusBadge status={val || 'lpj_submitted'} />,
    },
    {
      title: 'Aksi Pemeriksaan',
      key: 'action',
      render: (_, row) => (
        <div className="flex items-center gap-1.5">
          <button
            type="button"
            onClick={() => {
              setSelectedLpj(row);
              navigate(`/lpj/${row.id}`);
            }}
            className="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50"
          >
            Buka LPJ
          </button>
          <a
            href={`http://127.0.0.1:8000/api/v1/pdf/lpj/${row.id}`}
            target="_blank"
            rel="noopener noreferrer"
            className="rounded-lg border border-slate-200 p-1 text-slate-600 hover:bg-slate-50"
            title="Cetak Naskah LPJ"
          >
            <PrinterIcon className="h-4 w-4" />
          </a>
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Pemeriksaan Laporan Pertanggungjawaban (LPJ)</h1>
        <p className="text-xs text-slate-500 mt-1">
          Telaah bukti belanja, verifikasi rekonsiliasi kas, dan pengesahan dokumen penutupan hibah daerah.
        </p>
      </div>

      {successMsg && (
        <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-700">
          <CheckBadgeIcon className="h-5 w-5 shrink-0 text-emerald-600" />
          <span>{successMsg}</span>
        </div>
      )}

      <DataTable
        columns={columns}
        data={lpjs}
        loading={loading}
        searchPlaceholder="Cari berkas LPJ atau nama organisasi..."
        emptyTitle="Belum Ada Laporan LPJ"
        emptyDescription="Laporan LPJ masuk setelah dana dicairkan dan realisasi belanja selesai."
      />

      {/* Selected LPJ Detail Card */}
      {selectedLpj && (
        <div className="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs space-y-6">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100 pb-5">
            <div>
              <div className="flex items-center gap-2">
                <span className="font-mono text-xs text-blue-600 font-semibold">
                  LPJ #{selectedLpj.id?.slice(0, 8)}
                </span>
                <StatusBadge status={selectedLpj.status || 'lpj_submitted'} size="lg" />
              </div>
              <h2 className="text-lg font-bold text-slate-900 mt-1">
                Laporan: {selectedLpj.proposal?.title || 'Kegiatan Hibah'}
              </h2>
              <p className="text-xs text-slate-500">
                Lembaga: {selectedLpj.proposal?.organization?.name} • Tanggal Penyerahan: {selectedLpj.created_at || '—'}
              </p>
            </div>

            <div className="flex flex-wrap items-center gap-2">
              <button
                type="button"
                onClick={() => setRevisionModalOpen(true)}
                disabled={actionLoading}
                className="rounded-xl border border-amber-300 bg-white px-3.5 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-50"
              >
                Minta Perbaikan LPJ
              </button>

              <button
                type="button"
                onClick={() => handleApproveLpj(selectedLpj.id)}
                disabled={actionLoading}
                className="rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white hover:bg-blue-700"
              >
                Setujui LPJ
              </button>

              <button
                type="button"
                onClick={() => handleFinalizeAndClose(selectedLpj)}
                disabled={actionLoading}
                className="rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-800"
              >
                Finalisasi & Tutup Usulan
              </button>
            </div>
          </div>

          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 text-xs">
            <div className="rounded-xl border border-slate-100 bg-slate-50 p-4 space-y-2">
              <p className="font-bold text-slate-800 uppercase">Ringkasan Pelaksanaan Kegiatan</p>
              <p className="text-slate-600 leading-relaxed whitespace-pre-line">
                {selectedLpj.activity_summary || 'Pelaksanaan kegiatan hibah telah diselesaikan sesuai target sasaran proposal.'}
              </p>
            </div>

            <div className="rounded-xl border border-slate-100 bg-slate-50 p-4 space-y-2">
              <p className="font-bold text-slate-800 uppercase">Rekonsiliasi Realisasi Anggaran</p>
              <div className="space-y-1 text-slate-600">
                <div className="flex justify-between">
                  <span>Dana Diterima (SP2D):</span>
                  <span className="font-semibold text-slate-900">
                    Rp {Number(selectedLpj.proposal?.requested_amount || 0).toLocaleString('id-ID')}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span>Total Kuitansi Terverifikasi:</span>
                  <span className="font-semibold text-emerald-700">
                    Rp {Number(selectedLpj.total_spent || selectedLpj.proposal?.requested_amount || 0).toLocaleString('id-ID')}
                  </span>
                </div>
                <div className="flex justify-between border-t border-slate-200 pt-1 text-slate-500">
                  <span>Sisa Saldo Kas:</span>
                  <span className="font-mono">Rp 0</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* MODAL REVISION */}
      <Modal
        isOpen={revisionModalOpen}
        onClose={() => setRevisionModalOpen(false)}
        title="Minta Perbaikan / Revisi Dokumen LPJ"
      >
        <form onSubmit={handleRequestRevision} className="space-y-4">
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Catatan Kekurangan LPJ *
            </label>
            <textarea
              rows={4}
              value={revisionNotes}
              onChange={(e) => setRevisionNotes(e.target.value)}
              placeholder="Tuliskan kekurangan berkas bukti kuitansi, foto BAST, atau lampiran yang harus diperbaiki pemohon..."
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 p-3 text-xs focus:border-amber-500 focus:outline-hidden"
            />
          </div>
          <div className="flex justify-end gap-2 pt-4">
            <button
              type="button"
              onClick={() => setRevisionModalOpen(false)}
              className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={actionLoading}
              className="rounded-lg bg-amber-600 px-4 py-2 text-xs font-semibold text-white hover:bg-amber-700"
            >
              Kirim Catatan Revisi
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}

