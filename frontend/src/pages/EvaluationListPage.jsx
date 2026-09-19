import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { getProposals } from '../api/proposals';
import { startEvaluation, getEvaluations } from '../api/evaluations';
import DataTable from '../components/common/DataTable';
import StatusBadge from '../components/common/StatusBadge';
import { ChartBarIcon } from '@heroicons/react/24/outline';

export default function EvaluationListPage() {
  const navigate = useNavigate();
  const [proposals, setProposals] = useState([]);
  const [loading, setLoading] = useState(true);

  async function loadProposals() {
    setLoading(true);
    try {
      const res = await getProposals();
      const list = res?.data || res || [];
      const dataArr = Array.isArray(list) ? list : list.data || [];
      // Focus on proposals ready for technical evaluation
      const filtered = dataArr.filter((p) =>
        ['verified', 'evaluation', 'survey', 'recommended'].includes(p.status)
      );
      setProposals(filtered);
    } catch (err) {
      console.error('Failed to load evaluation list', err);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadProposals();
  }, []);

  async function handleOpenEvaluation(row) {
    try {
      const evalRes = await getEvaluations(row.id);
      const evalList = evalRes?.data || evalRes || [];
      const existing = Array.isArray(evalList) && evalList.length > 0 ? evalList[0] : null;

      if (existing) {
        navigate(`/evaluation/${row.id}/${existing.id}`);
      } else {
        const startRes = await startEvaluation(row.id);
        const newEval = startRes?.data || startRes;
        navigate(`/evaluation/${row.id}/${newEval.id}`);
      }
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal membuka sesi evaluasi teknis.');
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
      title: 'Nominal Diajukan (RAB)',
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
          onClick={() => handleOpenEvaluation(row)}
          className="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-indigo-700 transition"
        >
          <ChartBarIcon className="h-4 w-4" />
          <span>Evaluasi Teknis</span>
        </button>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Evaluasi Teknis & Skoring Kelayakan</h1>
        <p className="text-xs text-slate-500 mt-1">
          Penilaian kriteria teknis, kesesuaian anggaran RAB, dampak manfaat sosial, dan pemberian bobot nilai rekomendasi.
        </p>
      </div>

      <DataTable
        columns={columns}
        data={proposals}
        loading={loading}
        searchPlaceholder="Cari proposal untuk dievaluasi..."
        searchKey="title"
        emptyTitle="Tidak Ada Usulan untuk Evaluasi Teknis"
        emptyDescription="Saat ini tidak ada proposal yang siap dalam tahap evaluasi teknis."
      />
    </div>
  );
}

