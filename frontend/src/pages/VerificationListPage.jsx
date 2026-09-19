import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { getProposals } from '../api/proposals';
import { startVerification, getVerifications } from '../api/verifications';
import DataTable from '../components/common/DataTable';
import StatusBadge from '../components/common/StatusBadge';
import { ClipboardDocumentCheckIcon, FunnelIcon } from '@heroicons/react/24/outline';

export default function VerificationListPage() {
  const navigate = useNavigate();
  const [proposals, setProposals] = useState([]);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('');

  async function loadProposals() {
    setLoading(true);
    try {
      const params = {};
      if (statusFilter) {
        params.status = statusFilter;
      }
      const res = await getProposals(params);
      const list = res?.data || res || [];
      const dataArr = Array.isArray(list) ? list : list.data || [];
      // Focus on proposals undergoing or ready for verification
      const filtered = statusFilter
        ? dataArr
        : dataArr.filter((p) =>
            ['submitted', 'verification', 'revision', 'verified'].includes(p.status)
          );
      setProposals(filtered);
    } catch (err) {
      console.error('Failed to load verification list', err);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadProposals();
  }, [statusFilter]);

  async function handleOpenVerification(row) {
    try {
      const verRes = await getVerifications(row.id);
      const verList = verRes?.data || verRes || [];
      const existing = Array.isArray(verList) && verList.length > 0 ? verList[0] : null;

      if (existing) {
        navigate(`/verification/${row.id}/${existing.id}`);
      } else {
        // Start new verification session
        const startRes = await startVerification(row.id);
        const newVer = startRes?.data || startRes;
        navigate(`/verification/${row.id}/${newVer.id}`);
      }
    } catch (err) {
      // If error or already exists, navigate to proposal or alert
      alert(err.response?.data?.message || 'Gagal membuka sesi verifikasi.');
    }
  }

  const columns = [
    {
      title: 'Nomor & Judul Proposal',
      key: 'title',
      render: (val, row) => (
        <div>
          <span className="font-mono text-xs text-blue-600 font-semibold">
            #{row.proposal_number || row.id?.slice(0, 8)}
          </span>
          <p className="font-bold text-slate-900">{val}</p>
          <p className="text-xs text-slate-500">{row.organization?.name || 'Organisasi Pemohon'}</p>
        </div>
      ),
    },
    {
      title: 'Program Hibah',
      key: 'grant_program',
      render: (_, row) => (
        <span className="text-xs text-slate-700">{row.grant_program?.name || 'Hibah Daerah'}</span>
      ),
    },
    {
      title: 'Nominal Diajukan',
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
          onClick={() => handleOpenVerification(row)}
          className="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-blue-700 transition"
        >
          <ClipboardDocumentCheckIcon className="h-4 w-4" />
          <span>Verifikasi Berkas</span>
        </button>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Verifikasi Administrasi & Berkas</h1>
          <p className="text-xs text-slate-500 mt-1">
            Pemeriksaan kelengkapan dokumen persyaratan pemohon, legalitas organisasi, dan kepatuhan administrasi.
          </p>
        </div>

        <div className="flex items-center gap-3">
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-700 focus:border-blue-500 focus:outline-hidden"
          >
            <option value="">Semua Tahap Verifikasi</option>
            <option value="submitted">Baru Diajukan</option>
            <option value="verification">Sedang Diverifikasi</option>
            <option value="revision">Dalam Masa Revisi</option>
            <option value="verified">Telah Lolos Verifikasi</option>
          </select>
        </div>
      </div>

      <DataTable
        columns={columns}
        data={proposals}
        loading={loading}
        searchPlaceholder="Cari nama lembaga, judul usulan, atau nomor proposal..."
        searchKey="title"
        emptyTitle="Tidak Ada Antrean Verifikasi"
        emptyDescription="Saat ini tidak ada proposal yang menunggu verifikasi administrasi."
      />
    </div>
  );
}

