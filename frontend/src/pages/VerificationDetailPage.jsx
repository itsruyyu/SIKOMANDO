import React, { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import {
  getVerification,
  updateVerificationItem,
  completeVerification,
  getVerificationPdfUrl,
} from '../api/verifications';
import { getProposal, createProposalRevision } from '../api/proposals';
import LoadingSpinner from '../components/common/LoadingSpinner';
import StatusBadge from '../components/common/StatusBadge';
import Modal from '../components/common/Modal';
import {
  ArrowLeftIcon,
  CheckCircleIcon,
  XCircleIcon,
  ArrowPathIcon,
  PrinterIcon,
  ExclamationCircleIcon,
  DocumentCheckIcon,
} from '@heroicons/react/24/outline';

export default function VerificationDetailPage() {
  const { proposalId, verificationId } = useParams();
  const navigate = useNavigate();

  const [verification, setVerification] = useState(null);
  const [proposal, setProposal] = useState(null);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState('');
  const [successMsg, setSuccessMsg] = useState('');

  // Complete / Decision Modal
  const [decisionModalOpen, setDecisionModalOpen] = useState(false);
  const [decisionStatus, setDecisionStatus] = useState('passed'); // passed | rejected | revision_required
  const [decisionNotes, setDecisionNotes] = useState('');

  async function loadData() {
    try {
      const [verRes, propRes] = await Promise.allSettled([
        getVerification(proposalId, verificationId),
        getProposal(proposalId),
      ]);

      if (verRes.status === 'fulfilled') {
        setVerification(verRes.value?.data || verRes.value);
      }
      if (propRes.status === 'fulfilled') {
        setProposal(propRes.value?.data || propRes.value);
      }
    } catch (err) {
      setError('Gagal memuat lembar verifikasi.');
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadData();
  }, [proposalId, verificationId]);

  async function handleToggleItem(item, newStatus) {
    try {
      await updateVerificationItem(proposalId, verificationId, item.id, {
        status: newStatus,
        is_valid: newStatus === 'valid',
      });
      await loadData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal memperbarui item checklist.');
    }
  }

  async function handleCompleteDecision(e) {
    e.preventDefault();
    setActionLoading(true);
    setError('');

    try {
      await completeVerification(proposalId, verificationId, {
        status: decisionStatus,
        result: decisionStatus,
        notes: decisionNotes,
      });

      // If revision required, also create revision entry
      if (decisionStatus === 'revision_required') {
        await createProposalRevision(proposalId, {
          notes: decisionNotes || 'Perbaiki kelengkapan berkas yang belum memenuhi syarat administrasi.',
          deadline_days: 7,
        });
      }

      setDecisionModalOpen(false);
      setSuccessMsg('Keputusan verifikasi administrasi berhasil disimpan!');
      await loadData();
    } catch (err) {
      setError(err.response?.data?.message || 'Gagal menyimpan keputusan verifikasi.');
    } finally {
      setActionLoading(false);
    }
  }

  if (loading) {
    return <LoadingSpinner text="Memuat lembar kerja verifikasi administrasi..." />;
  }

  const items = verification?.items || verification?.checklist_items || [];
  const isCompleted = verification?.status === 'completed' || verification?.status === 'passed';

  return (
    <div className="space-y-6">
      <Link
        to="/verification"
        className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition"
      >
        <ArrowLeftIcon className="h-3.5 w-3.5" /> Kembali ke Daftar Verifikasi
      </Link>

      {/* Header Info */}
      <div className="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div className="flex items-center gap-2">
              <span className="font-mono text-xs font-semibold text-blue-600">
                #{proposal?.proposal_number || proposalId?.slice(0, 8)}
              </span>
              <StatusBadge status={verification?.status || 'verification'} />
            </div>
            <h1 className="mt-1.5 text-xl font-bold text-slate-900 sm:text-2xl">
              Lembar Verifikasi Administrasi: {proposal?.title}
            </h1>
            <p className="mt-1 text-xs text-slate-500">
              Lembaga Pemohon: <span className="font-semibold text-slate-700">{proposal?.organization?.name}</span> •
              Program: <span className="font-semibold text-slate-700">{proposal?.grant_program?.name}</span>
            </p>
          </div>

          <div className="flex items-center gap-3">
            <a
              href={getVerificationPdfUrl(proposalId, verificationId)}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition"
            >
              <PrinterIcon className="h-4 w-4 text-slate-500" />
              <span>Cetak BAP Verifikasi</span>
            </a>

            {!isCompleted && (
              <button
                type="button"
                onClick={() => setDecisionModalOpen(true)}
                className="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-blue-700 transition"
              >
                <DocumentCheckIcon className="h-4 w-4" />
                <span>Simpan Keputusan Final</span>
              </button>
            )}
          </div>
        </div>
      </div>

      {successMsg && (
        <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-700">
          <CheckCircleIcon className="h-5 w-5 shrink-0 text-emerald-600" />
          <span>{successMsg}</span>
        </div>
      )}

      {error && (
        <div className="flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-700">
          <ExclamationCircleIcon className="h-5 w-5 shrink-0 text-rose-600" />
          <span>{error}</span>
        </div>
      )}

      {/* Checklist Table */}
      <div className="rounded-2xl border border-slate-200 bg-white shadow-xs overflow-hidden">
        <div className="border-b border-slate-200 px-6 py-4">
          <h2 className="text-base font-bold text-slate-900">Checklist Kelengkapan & Keabsahan Dokumen</h2>
          <p className="text-xs text-slate-500">Periksa berkas persyaratan satu per satu sesuai standar verifikasi.</p>
        </div>

        {items.length === 0 ? (
          <div className="p-8 text-center text-xs text-slate-500">
            Daftar instrumen verifikasi administrasi terpasang otomatis dari template kebijakan sistem.
          </div>
        ) : (
          <table className="min-w-full divide-y divide-slate-200 text-left text-xs">
            <thead className="bg-slate-50 font-semibold uppercase text-slate-500">
              <tr>
                <th className="px-6 py-3.5">Kriteria / Syarat Berkas</th>
                <th className="px-6 py-3.5">Keterangan Standar</th>
                <th className="px-6 py-3.5">Status Validasi</th>
                {!isCompleted && <th className="px-6 py-3.5 text-right">Aksi Verifikator</th>}
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 bg-white">
              {items.map((item, idx) => (
                <tr key={item.id || idx}>
                  <td className="px-6 py-4 font-bold text-slate-800">
                    {item.criteria_name || item.name || `Kriteria #${idx + 1}`}
                  </td>
                  <td className="px-6 py-4 text-slate-500 max-w-xs">
                    {item.description || 'Memenuhi ketentuan keaslian & keterbacaan berkas.'}
                  </td>
                  <td className="px-6 py-4">
                    {item.status === 'valid' || item.is_valid ? (
                      <span className="inline-flex items-center gap-1 text-emerald-600 font-semibold">
                        <CheckCircleIcon className="h-4 w-4" /> Memenuhi Syarat
                      </span>
                    ) : item.status === 'invalid' ? (
                      <span className="inline-flex items-center gap-1 text-rose-600 font-semibold">
                        <XCircleIcon className="h-4 w-4" /> Tidak Sesuai
                      </span>
                    ) : (
                      <span className="text-slate-400 font-medium">Belum Diperiksa</span>
                    )}
                  </td>
                  {!isCompleted && (
                    <td className="px-6 py-4 text-right space-x-2">
                      <button
                        type="button"
                        onClick={() => handleToggleItem(item, 'valid')}
                        className="rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-emerald-700 hover:bg-emerald-100 font-semibold transition"
                      >
                        Sah / Valid
                      </button>
                      <button
                        type="button"
                        onClick={() => handleToggleItem(item, 'invalid')}
                        className="rounded-md border border-rose-200 bg-rose-50 px-2.5 py-1 text-rose-700 hover:bg-rose-100 font-semibold transition"
                      >
                        Tidak Sah
                      </button>
                    </td>
                  )}
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {/* Decision Modal */}
      <Modal
        isOpen={decisionModalOpen}
        onClose={() => setDecisionModalOpen(false)}
        title="Tetapkan Hasil Keputusan Verifikasi Administrasi"
      >
        <form onSubmit={handleCompleteDecision} className="space-y-4">
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Hasil Kesimpulan Verifikasi *
            </label>
            <select
              value={decisionStatus}
              onChange={(e) => setDecisionStatus(e.target.value)}
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2.5 px-3 text-xs font-semibold focus:border-blue-500 focus:outline-hidden"
            >
              <option value="passed">Lolos Verifikasi Administrasi (Lanjut ke Evaluasi Teknis)</option>
              <option value="revision_required">Perlu Perbaikan / Revisi Berkas oleh Pemohon</option>
              <option value="rejected">Tolak Usulan (Tidak Memenuhi Syarat Wajib)</option>
            </select>
          </div>

          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Catatan / Berita Acara Verifikator
            </label>
            <textarea
              rows={4}
              value={decisionNotes}
              onChange={(e) => setDecisionNotes(e.target.value)}
              placeholder="Tuliskan catatan detail hasil telaah berkas atau perbaikan yang harus dipenuhi..."
              className="mt-1.5 w-full rounded-lg border border-slate-300 p-3 text-xs focus:border-blue-500 focus:outline-hidden"
            />
          </div>

          <div className="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
            <button
              type="button"
              onClick={() => setDecisionModalOpen(false)}
              className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={actionLoading}
              className="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
            >
              {actionLoading ? 'Menyimpan...' : 'Simpan & Selesaikan'}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}

