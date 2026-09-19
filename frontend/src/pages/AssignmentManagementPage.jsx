import React, { useEffect, useState } from 'react';
import {
  getAssignments,
  createAssignment,
  revokeAssignment,
  getWorkloadStats,
} from '../api/assignments';
import { getProposals } from '../api/proposals';
import { getUsersByRole } from '../api/users';
import DataTable from '../components/common/DataTable';
import LoadingSpinner from '../components/common/LoadingSpinner';
import Modal from '../components/common/Modal';
import {
  UsersIcon,
  UserPlusIcon,
  ShieldCheckIcon,
  TrashIcon,
  CheckBadgeIcon,
} from '@heroicons/react/24/outline';

export default function AssignmentManagementPage() {
  const [assignments, setAssignments] = useState([]);
  const [proposals, setProposals] = useState([]);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [successMsg, setSuccessMsg] = useState('');

  // Assign modal
  const [assignModalOpen, setAssignModalOpen] = useState(false);
  const [selectedProposalId, setSelectedProposalId] = useState('');
  const [assignmentRole, setAssignmentRole] = useState('VERIFIKATOR');
  const [selectedUserId, setSelectedUserId] = useState('');
  const [usersByRole, setUsersByRole] = useState([]);

  async function loadData() {
    setLoading(true);
    try {
      const [assignRes, propRes] = await Promise.allSettled([
        getAssignments(),
        getProposals(),
      ]);

      if (assignRes.status === 'fulfilled') {
        const aList = assignRes.value?.data || assignRes.value || [];
        setAssignments(Array.isArray(aList) ? aList : aList.data || []);
      }
      if (propRes.status === 'fulfilled') {
        const pList = propRes.value?.data || propRes.value || [];
        setProposals(Array.isArray(pList) ? pList : pList.data || []);
      }
    } catch (err) {
      console.error('Failed to load assignments', err);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadData();
  }, []);

  useEffect(() => {
    async function loadUsers() {
      try {
        const res = await getUsersByRole(assignmentRole);
        const list = res?.data || res || [];
        setUsersByRole(Array.isArray(list) ? list : list.data || []);
      } catch {
        setUsersByRole([]);
      }
    }
    if (assignModalOpen) {
      loadUsers();
    }
  }, [assignmentRole, assignModalOpen]);

  async function handleAssignSubmit(e) {
    e.preventDefault();
    setActionLoading(true);
    try {
      await createAssignment({
        proposal_id: selectedProposalId,
        user_id: selectedUserId,
        assignment_type: assignmentRole,
        role: assignmentRole,
      });
      setAssignModalOpen(false);
      setSelectedProposalId('');
      setSelectedUserId('');
      setSuccessMsg('Penugasan penilai berhasil ditetapkan!');
      await loadData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal membuat penugasan.');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleRevoke(id) {
    if (!window.confirm('Cabut penugasan penilai dari usulan ini?')) return;
    setActionLoading(true);
    try {
      await revokeAssignment(id);
      setSuccessMsg('Penugasan berhasil dicabut.');
      await loadData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal mencabut penugasan.');
    } finally {
      setActionLoading(false);
    }
  }

  const columns = [
    {
      title: 'Nama Penilai / Petugas',
      key: 'user',
      render: (val, row) => (
        <div>
          <p className="font-bold text-slate-900">{row.user?.name || row.reviewer_name || 'Petugas Penilai'}</p>
          <p className="text-xs text-slate-500">{row.user?.email || '—'}</p>
        </div>
      ),
    },
    {
      title: 'Peran Penugasan',
      key: 'role',
      render: (val, row) => (
        <span className="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 border border-blue-200">
          {val || row.assignment_type || 'VERIFIKATOR'}
        </span>
      ),
    },
    {
      title: 'Proposal Ditugaskan',
      key: 'proposal',
      render: (val, row) => (
        <div>
          <span className="font-mono text-[11px] text-blue-600 font-semibold">
            #{row.proposal?.proposal_number || row.proposal_id?.slice(0, 8)}
          </span>
          <p className="font-medium text-slate-900 text-xs">{row.proposal?.title || 'Usulan Bantuan'}</p>
        </div>
      ),
    },
    {
      title: 'Status Penugasan',
      key: 'status',
      render: (val) => (
        <span className="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
          {val || 'Aktif'}
        </span>
      ),
    },
    {
      title: 'Aksi',
      key: 'action',
      render: (_, row) => (
        <button
          type="button"
          onClick={() => handleRevoke(row.id)}
          disabled={actionLoading}
          className="rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition"
        >
          Cabut Tugas
        </button>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Distribusi & Penugasan Tim Penilai</h1>
          <p className="text-xs text-slate-500 mt-1">
            Penetapan verifikator administrasi, evaluator teknis, dan surveyor lapangan per usulan proposal.
          </p>
        </div>

        <button
          type="button"
          onClick={() => setAssignModalOpen(true)}
          className="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-blue-700 transition"
        >
          <UserPlusIcon className="h-4 w-4" />
          <span>+ Buat Penugasan Baru</span>
        </button>
      </div>

      {successMsg && (
        <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-700">
          <CheckBadgeIcon className="h-5 w-5 shrink-0 text-emerald-600" />
          <span>{successMsg}</span>
        </div>
      )}

      <DataTable
        columns={columns}
        data={assignments}
        loading={loading}
        searchPlaceholder="Cari nama petugas penilai atau nomor usulan..."
        emptyTitle="Belum Ada Penugasan"
        emptyDescription="Klik tombol '+ Buat Penugasan Baru' untuk menugaskan verifikator ke proposal."
      />

      {/* MODAL PENUGASAN */}
      <Modal
        isOpen={assignModalOpen}
        onClose={() => setAssignModalOpen(false)}
        title="Tetapkan Penugasan Penilai Proposal"
      >
        <form onSubmit={handleAssignSubmit} className="space-y-4">
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Pilih Usulan Proposal *
            </label>
            <select
              value={selectedProposalId}
              onChange={(e) => setSelectedProposalId(e.target.value)}
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-xs focus:border-blue-500 focus:outline-hidden"
            >
              <option value="">-- Pilih Proposal Usulan --</option>
              {proposals.map((p) => (
                <option key={p.id} value={p.id}>
                  #{p.proposal_number || p.id?.slice(0, 6)} - {p.title}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Peran Penugasan *
            </label>
            <select
              value={assignmentRole}
              onChange={(e) => setAssignmentRole(e.target.value)}
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-xs font-semibold focus:border-blue-500 focus:outline-hidden"
            >
              <option value="VERIFIKATOR">Verifikator Administrasi</option>
              <option value="EVALUATOR">Evaluator Teknis</option>
              <option value="SURVEYOR">Surveyor Lapangan</option>
            </select>
          </div>

          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Pilih Nama Pegawai / Petugas *
            </label>
            <select
              value={selectedUserId}
              onChange={(e) => setSelectedUserId(e.target.value)}
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-xs focus:border-blue-500 focus:outline-hidden"
            >
              <option value="">-- Pilih Pegawai Sesuai Peran --</option>
              {usersByRole.map((u) => (
                <option key={u.id} value={u.id}>
                  {u.name} ({u.email})
                </option>
              ))}
            </select>
          </div>

          <div className="flex justify-end gap-2 pt-4">
            <button
              type="button"
              onClick={() => setAssignModalOpen(false)}
              className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={actionLoading || !selectedProposalId || !selectedUserId}
              className="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
            >
              Tetapkan Tugas
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}

