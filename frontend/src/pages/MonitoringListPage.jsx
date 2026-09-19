import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { getProposals } from '../api/proposals';
import { getProposalMonitoringRecords, createProposalMonitoringRecord } from '../api/monitoring';
import DataTable from '../components/common/DataTable';
import StatusBadge from '../components/common/StatusBadge';
import { EyeIcon } from '@heroicons/react/24/outline';

export default function MonitoringListPage() {
  const navigate = useNavigate();
  const [proposals, setProposals] = useState([]);
  const [loading, setLoading] = useState(true);

  async function loadProposals() {
    setLoading(true);
    try {
      const res = await getProposals();
      const list = res?.data || res || [];
      const dataArr = Array.isArray(list) ? list : list.data || [];
      // Focus on disbursed, implementation, and completed proposals for monev
      const filtered = dataArr.filter((p) =>
        ['disbursed', 'implementation', 'lpj_submitted', 'lpj_verified', 'completed'].includes(p.status)
      );
      setProposals(filtered);
    } catch (err) {
      console.error('Failed to load monitoring proposals', err);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadProposals();
  }, []);

  async function handleOpenMonitoring(row) {
    try {
      const recRes = await getProposalMonitoringRecords(row.id);
      const records = recRes?.data || recRes || [];
      const existing = Array.isArray(records) && records.length > 0 ? records[0] : null;

      if (existing) {
        navigate(`/monitoring/${row.id}/${existing.id}`);
      } else {
        const createRes = await createProposalMonitoringRecord(row.id, {
          title: `Monev Lapangan Pasca Penyaluran - ${row.title}`,
        });
        const newRec = createRes?.data || createRes;
        navigate(`/monitoring/${row.id}/${newRec.id}`);
      }
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal membuka sesi monitoring.');
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
          <p className="text-xs text-slate-500">{row.organization?.name || 'Organisasi Penerima'}</p>
        </div>
      ),
    },
    {
      title: 'Alokasi Realisasi (Rp)',
      key: 'requested_amount',
      render: (val) => (
        <span className="font-semibold text-slate-900">
          {val ? `Rp ${Number(val).toLocaleString('id-ID')}` : '—'}
        </span>
      ),
    },
    {
      title: 'Status Hibah',
      key: 'status',
      render: (val) => <StatusBadge status={val} />,
    },
    {
      title: 'Aksi',
      key: 'action',
      render: (_, row) => (
        <button
          type="button"
          onClick={() => handleOpenMonitoring(row)}
          className="inline-flex items-center gap-1 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-slate-800 transition"
        >
          <EyeIcon className="h-4 w-4" />
          <span>Buka Monev</span>
        </button>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Monitoring & Evaluasi Lapangan (Monev)</h1>
        <p className="text-xs text-slate-500 mt-1">
          Inspeksi fisik pasca penyaluran dana hibah untuk memastikan kemanfaatan barang dan keberlanjutan hasil.
        </p>
      </div>

      <DataTable
        columns={columns}
        data={proposals}
        loading={loading}
        searchPlaceholder="Cari proposal penerima hibah untuk di-monev..."
        emptyTitle="Tidak Ada Usulan untuk Monev"
        emptyDescription="Proposal siap di-monev setelah dana disalurkan dan pelaksanaan berjalan."
      />
    </div>
  );
}

