import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { getApprovals } from '../api/approvals';
import DataTable from '../components/common/DataTable';
import StatusBadge from '../components/common/StatusBadge';
import { CheckBadgeIcon } from '@heroicons/react/24/outline';

export default function ApprovalListPage() {
  const navigate = useNavigate();
  const [approvals, setApprovals] = useState([]);
  const [loading, setLoading] = useState(true);

  async function loadApprovals() {
    setLoading(true);
    try {
      const res = await getApprovals();
      const list = res?.data || res || [];
      setApprovals(Array.isArray(list) ? list : list.data || []);
    } catch (err) {
      console.error('Failed to load approvals', err);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadApprovals();
  }, []);

  const columns = [
    {
      title: 'Nomor & Judul Proposal',
      key: 'proposal',
      render: (val, row) => (
        <div>
          <span className="font-mono text-xs text-blue-600 font-semibold">
            #{row.proposal?.proposal_number || row.proposal_id?.slice(0, 8)}
          </span>
          <p className="font-bold text-slate-900">{row.proposal?.title || 'Usulan Bantuan Hibah'}</p>
          <p className="text-xs text-slate-500">{row.proposal?.organization?.name || 'Organisasi Pemohon'}</p>
        </div>
      ),
    },
    {
      title: 'Program Hibah',
      key: 'program',
      render: (_, row) => (
        <span className="text-xs text-slate-700">{row.proposal?.grant_program?.name || 'Hibah Daerah'}</span>
      ),
    },
    {
      title: 'Alokasi Rekomendasi (Rp)',
      key: 'amount',
      render: (val, row) => {
        const amount = val || row.recommended_amount || row.proposal?.requested_amount;
        return (
          <span className="font-bold text-emerald-700">
            {amount ? `Rp ${Number(amount).toLocaleString('id-ID')}` : '—'}
          </span>
        );
      },
    },
    {
      title: 'Status Persetujuan',
      key: 'status',
      render: (val) => <StatusBadge status={val || 'approval'} />,
    },
    {
      title: 'Aksi',
      key: 'action',
      render: (_, row) => (
        <button
          type="button"
          onClick={() => navigate(`/approvals/${row.id}`)}
          className="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-emerald-700 transition"
        >
          <CheckBadgeIcon className="h-4 w-4" />
          <span>Telaah & Setujui</span>
        </button>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Persetujuan Pimpinan Daerah</h1>
        <p className="text-xs text-slate-500 mt-1">
          Penelaahan lembar rekomendasi akhir dan penetapan persetujuan usulan hibah sebelum penerbitan SK.
        </p>
      </div>

      <DataTable
        columns={columns}
        data={approvals}
        loading={loading}
        searchPlaceholder="Cari proposal untuk persetujuan pimpinan..."
        emptyTitle="Tidak Ada Antrean Persetujuan"
        emptyDescription="Saat ini tidak ada usulan hibah yang menunggu persetujuan pimpinan."
      />
    </div>
  );
}

