import React, { useEffect, useState } from 'react';
import { getPublicGrantPrograms } from '../api/public';
import {
  getRankings,
  generateRankings,
  getRankingDetail,
  finalizeRanking,
  regenerateRanking,
} from '../api/rankings';
import DataTable from '../components/common/DataTable';
import LoadingSpinner from '../components/common/LoadingSpinner';
import StatusBadge from '../components/common/StatusBadge';
import {
  TrophyIcon,
  SparklesIcon,
  CheckBadgeIcon,
  ArrowPathIcon,
  BanknotesIcon,
} from '@heroicons/react/24/outline';

export default function RankingRecommendationPage() {
  const [programs, setPrograms] = useState([]);
  const [selectedProgramId, setSelectedProgramId] = useState('');
  const [rankings, setRankings] = useState([]);
  const [activeRankingDetail, setActiveRankingDetail] = useState(null);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [successMsg, setSuccessMsg] = useState('');

  useEffect(() => {
    async function loadPrograms() {
      try {
        const res = await getPublicGrantPrograms();
        const list = res?.data || res || [];
        const arr = Array.isArray(list) ? list : list.data || [];
        setPrograms(arr);
        if (arr.length > 0) {
          setSelectedProgramId(arr[0].id);
        }
      } catch (err) {
        console.error('Failed to load programs for ranking', err);
      } finally {
        setLoading(false);
      }
    }

    loadPrograms();
  }, []);

  async function loadRankings(programId) {
    if (!programId) return;
    setLoading(true);
    try {
      const res = await getRankings(programId);
      const list = res?.data || res || [];
      const rankList = Array.isArray(list) ? list : list.data || [];
      setRankings(rankList);

      if (rankList.length > 0) {
        const detailRes = await getRankingDetail(programId, rankList[0].id);
        setActiveRankingDetail(detailRes?.data || detailRes);
      } else {
        setActiveRankingDetail(null);
      }
    } catch (err) {
      console.error('Failed to load rankings', err);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    if (selectedProgramId) {
      loadRankings(selectedProgramId);
    }
  }, [selectedProgramId]);

  async function handleGenerate() {
    if (!selectedProgramId) return;
    setActionLoading(true);
    setSuccessMsg('');
    try {
      await generateRankings(selectedProgramId);
      setSuccessMsg('Kalkulasi perankingan otomatis dan rekomendasi alokasi berhasil di-generate!');
      await loadRankings(selectedProgramId);
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menghasilkan perankingan.');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleFinalize() {
    if (!selectedProgramId || !activeRankingDetail) return;
    if (!window.confirm('Apakah Anda yakin ingin menetapkan finalisasi rekomendasi perankingan ini?')) return;

    setActionLoading(true);
    try {
      await finalizeRanking(selectedProgramId, activeRankingDetail.id);
      setSuccessMsg('Rekomendasi perankingan berhasil difinalisasi dan diajukan ke pimpinan!');
      await loadRankings(selectedProgramId);
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal memfinalisasi ranking.');
    } finally {
      setActionLoading(false);
    }
  }

  const proposals = activeRankingDetail?.items || activeRankingDetail?.proposals || [];

  const columns = [
    {
      title: 'Peringkat',
      key: 'rank',
      render: (val, row, idx) => {
        const rankNum = val || row.ranking_position || idx + 1;
        return (
          <div className="flex items-center gap-2">
            <span
              className={`flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold ${
                rankNum === 1
                  ? 'bg-amber-100 text-amber-800'
                  : rankNum === 2
                  ? 'bg-slate-200 text-slate-800'
                  : rankNum === 3
                  ? 'bg-amber-50 text-amber-700'
                  : 'bg-slate-100 text-slate-600'
              }`}
            >
              {rankNum}
            </span>
          </div>
        );
      },
    },
    {
      title: 'Lembaga / Usulan',
      key: 'title',
      render: (val, row) => (
        <div>
          <p className="font-bold text-slate-900">{row.proposal?.title || val || 'Usulan Kegiatan'}</p>
          <p className="text-xs text-slate-500">{row.proposal?.organization?.name || 'Organisasi'}</p>
        </div>
      ),
    },
    {
      title: 'Total Skor',
      key: 'score',
      render: (val, row) => (
        <span className="font-mono text-sm font-black text-indigo-700">
          {(val ?? row.total_score ?? row.score ?? 0).toFixed(2)}
        </span>
      ),
    },
    {
      title: 'Usulan RAB (Rp)',
      key: 'requested_amount',
      render: (val, row) => (
        <span className="text-xs text-slate-700">
          {Number(row.proposal?.requested_amount || val || 0).toLocaleString('id-ID')}
        </span>
      ),
    },
    {
      title: 'Rekomendasi Alokasi (Rp)',
      key: 'allocated_amount',
      render: (val, row) => (
        <span className="font-bold text-emerald-700">
          Rp {Number(val ?? row.recommended_amount ?? 0).toLocaleString('id-ID')}
        </span>
      ),
    },
    {
      title: 'Status Rekomendasi',
      key: 'status',
      render: (val, row) => {
        const isPassed = row.is_recommended !== false;
        return isPassed ? (
          <span className="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200">
            Lolos Pagu
          </span>
        ) : (
          <span className="rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 border border-rose-200">
            Di Luar Pagu
          </span>
        );
      },
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Perankingan & Rekomendasi Alokasi</h1>
          <p className="text-xs text-slate-500 mt-1">
            Penetapan peringkat otomatis berdasarkan skor evaluasi teknis dan survei lapangan sesuai pagu program.
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-3">
          <select
            value={selectedProgramId}
            onChange={(e) => setSelectedProgramId(e.target.value)}
            className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 focus:border-blue-500 focus:outline-hidden"
          >
            {programs.map((p) => (
              <option key={p.id} value={p.id}>
                {p.name} (TA {p.fiscal_year || '2026'})
              </option>
            ))}
          </select>

          <button
            type="button"
            onClick={handleGenerate}
            disabled={actionLoading}
            className="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-blue-700 transition disabled:opacity-50"
          >
            <SparklesIcon className="h-4 w-4" />
            <span>Generate Ranking</span>
          </button>

          {activeRankingDetail && activeRankingDetail.status !== 'finalized' && (
            <button
              type="button"
              onClick={handleFinalize}
              disabled={actionLoading}
              className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-700 transition disabled:opacity-50"
            >
              <CheckBadgeIcon className="h-4 w-4" />
              <span>Finalisasi Rekomendasi</span>
            </button>
          )}
        </div>
      </div>

      {successMsg && (
        <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-700">
          <CheckBadgeIcon className="h-5 w-5 shrink-0 text-emerald-600" />
          <span>{successMsg}</span>
        </div>
      )}

      {/* Summary Box */}
      {activeRankingDetail && (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            <p className="text-xs font-semibold uppercase text-slate-500">Status Sesi Perankingan</p>
            <p className="mt-2 text-base font-bold text-slate-900 uppercase">
              {activeRankingDetail.status || 'Draft Rekomendasi'}
            </p>
            <p className="text-[11px] text-slate-400 mt-0.5">Versi kalkulasi terkini</p>
          </div>

          <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            <p className="text-xs font-semibold uppercase text-slate-500">Total Usulan Dievaluasi</p>
            <p className="mt-2 text-2xl font-black text-indigo-600">
              {proposals.length} Proposal
            </p>
            <p className="text-[11px] text-slate-400 mt-0.5">Memenuhi ambang batas nilai</p>
          </div>

          <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            <p className="text-xs font-semibold uppercase text-slate-500">Pagu Rekomendasi Dialokasikan</p>
            <p className="mt-2 text-xl font-bold text-emerald-600">
              Rp {Number(activeRankingDetail.total_allocated || 0).toLocaleString('id-ID')}
            </p>
            <p className="text-[11px] text-slate-400 mt-0.5">Sesuai kuota anggaran daerah</p>
          </div>
        </div>
      )}

      <DataTable
        columns={columns}
        data={proposals}
        loading={loading}
        searchPlaceholder="Cari peringkat atau nama usulan..."
        emptyTitle="Belum Ada Hasil Perankingan"
        emptyDescription="Pilih program hibah dan klik tombol 'Generate Ranking' untuk mengkalkulasi peringkat usulan."
      />
    </div>
  );
}

