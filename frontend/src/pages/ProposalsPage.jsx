import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { getProposals } from '../api/proposals';
import DataTable from '../components/common/DataTable';
import StatusBadge from '../components/common/StatusBadge';
import { PlusIcon, FunnelIcon } from '@heroicons/react/24/outline';

export default function ProposalsPage() {
  const navigate = useNavigate();
  const [proposals, setProposals] = useState([]);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('');

  useEffect(() => {
    async function loadProposals() {
      setLoading(true);
      try {
        const params = {};
        if (statusFilter) params.status = statusFilter;

        const response = await getProposals(params);
        const list = response?.data || response || [];
        setProposals(Array.isArray(list) ? list : list.data || []);
      } catch (err) {
        console.error('Failed to load proposals', err);
      } finally {
        setLoading(false);
      }
    }

    loadProposals();
  }, [statusFilter]);

  const columns = [
    {
      title: 'Nomor & Judul Usulan',
      key: 'title',
      render: (val, row) => (
        <div>
          <span className="font-mono text-xs font-semibold text-blue-600">
            #{row.proposal_number || row.id?.slice(0, 8)}
          </span>
          <p className="font-bold text-slate-900">{val || 'Proposal Tanpa Judul'}</p>
          <p className="text-xs text-slate-500">{row.organization?.name || 'Organisasi Pemohon'}</p>
        </div>
      ),
    },
    {
      title: 'Program Hibah',
      key: 'grant_program',
      render: (val, row) => (
        <span className="text-xs text-slate-700">
          {row.grant_program?.name || 'Program Hibah Daerah'}
        </span>
      ),
    },
    {
      title: 'Pagu Usulan (Rp)',
      key: 'requested_amount',
      render: (val) => (
        <span className="font-semibold text-slate-900">
          {val ? `Rp ${Number(val).toLocaleString('id-ID')}` : '—'}
        </span>
      ),
    },
    {
      title: 'Status Usulan',
      key: 'status',
      render: (val) => <StatusBadge status={val} />,
    },
    {
      title: 'Aksi',
      key: 'action',
      render: (_, row) => (
        <button
          type="button"
          onClick={(e) => {
            e.stopPropagation();
            navigate(`/proposals/${row.id}`);
          }}
          className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
        >
          Lihat Detail
        </button>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Daftar Usulan Hibah</h1>
          <p className="text-xs text-slate-500 mt-1">
            Kelola pengajuan usulan hibah organisasi, dokumen persyaratan, dan pantau status penilaian.
          </p>
        </div>

        <div className="flex items-center gap-3">
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-700 focus:border-blue-500 focus:outline-hidden"
          >
            <option value="">Semua Status Usulan</option>
            <option value="draft">Draft</option>
            <option value="submitted">Diajukan</option>
            <option value="verification">Verifikasi</option>
            <option value="revision">Perlu Revisi</option>
            <option value="verified">Terverifikasi</option>
            <option value="evaluation">Evaluasi Teknis</option>
            <option value="survey">Survei Lapangan</option>
            <option value="approval">Persetujuan Pimpinan</option>
            <option value="approved">Disetujui</option>
            <option value="disbursed">Dana Disalurkan</option>
            <option value="implementation">Pelaksanaan</option>
            <option value="completed">Selesai</option>
          </select>

          <Link
            to="/proposals/create"
            className="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-blue-700 transition"
          >
            <PlusIcon className="h-4 w-4 stroke-2" />
            <span>Ajukan Usulan Baru</span>
          </Link>
        </div>
      </div>

      <DataTable
        columns={columns}
        data={proposals}
        loading={loading}
        searchPlaceholder="Cari judul proposal, nomor, atau organisasi..."
        searchKey="title"
        onRowClick={(row) => navigate(`/proposals/${row.id}`)}
        emptyTitle="Belum Ada Usulan"
        emptyDescription="Tidak ada usulan proposal yang sesuai dengan filter yang dipilih."
      />
    </div>
  );
}