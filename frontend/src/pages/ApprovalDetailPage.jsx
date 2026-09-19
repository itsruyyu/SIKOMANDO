import React, { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import {
  getApproval,
  approveApproval,
  rejectApproval,
  reviewApproval,
} from '../api/approvals';
import LoadingSpinner from '../components/common/LoadingSpinner';
import StatusBadge from '../components/common/StatusBadge';
import Modal from '../components/common/Modal';
import {
  ArrowLeftIcon,
  CheckCircleIcon,
  XCircleIcon,
  ClockIcon,
  BanknotesIcon,
  BuildingOffice2Icon,
  ShieldCheckIcon,
  ExclamationCircleIcon,
} from '@heroicons/react/24/outline';

export default function ApprovalDetailPage() {
  const { approvalId } = useParams();
  const navigate = useNavigate();

  const [approval, setApproval] = useState(null);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState('');
  const [successMsg, setSuccessMsg] = useState('');

  // Modals
  const [approveModalOpen, setApproveModalOpen] = useState(false);
  const [approvedAmount, setApprovedAmount] = useState('');
  const [approvalNotes, setApprovalNotes] = useState('');

  const [rejectModalOpen, setRejectModalOpen] = useState(false);
  const [rejectReason, setRejectReason] = useState('');

  async function loadData() {
    try {
      const res = await getApproval(approvalId);
      const data = res?.data || res;
      setApproval(data);
      if (!approvedAmount) {
        setApprovedAmount(data.recommended_amount || data.proposal?.requested_amount || '');
      }
    } catch (err) {
      setError('Gagal memuat berkas persetujuan pimpinan.');
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadData();
  }, [approvalId]);

  async function handleApprove(e) {
    e.preventDefault();
    setActionLoading(true);
    setError('');
    try {
      await approveApproval(approvalId, {
        approved_amount: Number(approvedAmount),
        notes: approvalNotes,
      });
      setApproveModalOpen(false);
      setSuccessMsg('Persetujuan pimpinan berhasil diberikan! Usulan siap untuk penerbitan SK.');
      await loadData();
    } catch (err) {
      setError(err.response?.data?.message || 'Gagal memberikan persetujuan.');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleReject(e) {
    e.preventDefault();
    if (!rejectReason.trim()) return;
    setActionLoading(true);
    setError('');
    try {
      await rejectApproval(approvalId, {
        rejection_reason: rejectReason.trim(),
        notes: rejectReason.trim(),
      });
      setRejectModalOpen(false);
      setSuccessMsg('Usulan telah ditolak secara resmi.');
      await loadData();
    } catch (err) {
      setError(err.response?.data?.message || 'Gagal menolak usulan.');
    } finally {
      setActionLoading(false);
    }
  }

  if (loading) {
    return <LoadingSpinner text="Memuat lembar persetujuan pimpinan..." />;
  }

  const proposal = approval?.proposal;
  const isDecided = ['approved', 'rejected'].includes(approval?.status);

  return (
    <div className="space-y-6">
      <Link
        to="/approvals"
        className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition"
      >
        <ArrowLeftIcon className="h-3.5 w-3.5" /> Kembali ke Daftar Persetujuan
      </Link>

      {/* Header */}
      <div className="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div className="flex items-center gap-2">
              <span className="font-mono text-xs font-semibold text-blue-600">
                #{proposal?.proposal_number || approval?.id?.slice(0, 8)}
              </span>
              <StatusBadge status={approval?.status || 'approval'} size="lg" />
            </div>
            <h1 className="mt-1.5 text-xl font-bold text-slate-900 sm:text-2xl">
              Lembar Persetujuan: {proposal?.title}
            </h1>
            <p className="mt-1 text-xs text-slate-500">
              Pemohon: <span className="font-semibold text-slate-700">{proposal?.organization?.name}</span> •
              Program: <span className="font-semibold text-slate-700">{proposal?.grant_program?.name}</span>
            </p>
          </div>

          {!isDecided && (
            <div className="flex items-center gap-3">
              <button
                type="button"
                onClick={() => setRejectModalOpen(true)}
                className="inline-flex items-center gap-1.5 rounded-xl border border-rose-300 bg-white px-4 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 transition"
              >
                <XCircleIcon className="h-4 w-4" />
                <span>Tolak Usulan</span>
              </button>

              <button
                type="button"
                onClick={() => setApproveModalOpen(true)}
                className="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-700 transition"
              >
                <CheckCircleIcon className="h-4 w-4" />
                <span>Setujui Usulan Hibah</span>
              </button>
            </div>
          )}
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

      {/* Summary Reviewer Cards */}
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
          <p className="text-xs font-semibold uppercase text-slate-500">RAB Dimohon Awal</p>
          <p className="mt-2 text-xl font-bold text-slate-900">
            Rp {Number(proposal?.requested_amount || 0).toLocaleString('id-ID')}
          </p>
          <p className="text-[11px] text-slate-400 mt-1">Diajukan oleh pemohon</p>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
          <p className="text-xs font-semibold uppercase text-slate-500">Rekomendasi Hasil Penilaian</p>
          <p className="mt-2 text-xl font-bold text-indigo-700">
            Rp {Number(approval?.recommended_amount || proposal?.requested_amount || 0).toLocaleString('id-ID')}
          </p>
          <p className="text-[11px] text-slate-400 mt-1">Setelah evaluasi teknis & survei</p>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
          <p className="text-xs font-semibold uppercase text-slate-500">Alokasi Disetujui Pimpinan</p>
          <p className="mt-2 text-2xl font-black text-emerald-600">
            {approval?.approved_amount
              ? `Rp ${Number(approval.approved_amount).toLocaleString('id-ID')}`
              : 'Menunggu Keputusan'}
          </p>
          <p className="text-[11px] text-slate-400 mt-1">Nominal dasar penerbitan SK</p>
        </div>
      </div>

      {/* MODAL APPROVE */}
      <Modal
        isOpen={approveModalOpen}
        onClose={() => setApproveModalOpen(false)}
        title="Persetujuan Usulan Bantuan Hibah oleh Pimpinan"
      >
        <form onSubmit={handleApprove} className="space-y-4">
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Nominal Hibah yang Disetujui (Rp) *
            </label>
            <input
              type="number"
              value={approvedAmount}
              onChange={(e) => setApprovedAmount(e.target.value)}
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2.5 px-3 text-sm font-bold text-slate-900 focus:border-emerald-500 focus:outline-hidden"
            />
          </div>

          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Arahan / Catatan Pimpinan
            </label>
            <textarea
              rows={4}
              value={approvalNotes}
              onChange={(e) => setApprovalNotes(e.target.value)}
              placeholder="Tuliskan arahan pelaksanaan atau instruksi khusus pemanfaatan hibah..."
              className="mt-1.5 w-full rounded-lg border border-slate-300 p-3 text-xs focus:border-emerald-500 focus:outline-hidden"
            />
          </div>

          <div className="flex justify-end gap-2 pt-4">
            <button
              type="button"
              onClick={() => setApproveModalOpen(false)}
              className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={actionLoading}
              className="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
            >
              {actionLoading ? 'Menyimpan...' : 'Setujui & Teruskan ke SK'}
            </button>
          </div>
        </form>
      </Modal>

      {/* MODAL REJECT */}
      <Modal
        isOpen={rejectModalOpen}
        onClose={() => setRejectModalOpen(false)}
        title="Tolak Usulan Bantuan Hibah"
      >
        <form onSubmit={handleReject} className="space-y-4">
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Alasan Penolakan Resmi *
            </label>
            <textarea
              rows={4}
              value={rejectReason}
              onChange={(e) => setRejectReason(e.target.value)}
              placeholder="Jelaskan alasan penolakan usulan secara resmi..."
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 p-3 text-xs focus:border-rose-500 focus:outline-hidden"
            />
          </div>

          <div className="flex justify-end gap-2 pt-4">
            <button
              type="button"
              onClick={() => setRejectModalOpen(false)}
              className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={actionLoading}
              className="rounded-lg bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-700 disabled:opacity-50"
            >
              Tolak Usulan
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
