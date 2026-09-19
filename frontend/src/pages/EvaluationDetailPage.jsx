import React, { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import {
  getEvaluation,
  updateEvaluationItem,
  completeEvaluation,
  getEvaluationPdfUrl,
} from '../api/evaluations';
import { getProposal } from '../api/proposals';
import LoadingSpinner from '../components/common/LoadingSpinner';
import StatusBadge from '../components/common/StatusBadge';
import Modal from '../components/common/Modal';
import {
  ArrowLeftIcon,
  PrinterIcon,
  ChartBarIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  BanknotesIcon,
} from '@heroicons/react/24/outline';

export default function EvaluationDetailPage() {
  const { proposalId, evaluationId } = useParams();
  const navigate = useNavigate();

  const [evaluation, setEvaluation] = useState(null);
  const [proposal, setProposal] = useState(null);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState('');
  const [successMsg, setSuccessMsg] = useState('');

  // Complete modal
  const [completeModalOpen, setCompleteModalOpen] = useState(false);
  const [recommendedAmount, setRecommendedAmount] = useState('');
  const [evalNotes, setEvalNotes] = useState('');

  async function loadData() {
    try {
      const [evalRes, propRes] = await Promise.allSettled([
        getEvaluation(proposalId, evaluationId),
        getProposal(proposalId),
      ]);

      if (evalRes.status === 'fulfilled') {
        const data = evalRes.value?.data || evalRes.value;
        setEvaluation(data);
        if (!recommendedAmount && data?.recommended_amount) {
          setRecommendedAmount(data.recommended_amount);
        }
      }
      if (propRes.status === 'fulfilled') {
        const pData = propRes.value?.data || propRes.value;
        setProposal(pData);
        if (!recommendedAmount && pData?.requested_amount) {
          setRecommendedAmount(pData.requested_amount);
        }
      }
    } catch (err) {
      setError('Gagal memuat detail evaluasi teknis.');
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadData();
  }, [proposalId, evaluationId]);

  async function handleScoreChange(item, scoreVal) {
    const num = Number(scoreVal);
    if (isNaN(num) || num < 0 || num > 100) return;

    try {
      await updateEvaluationItem(proposalId, evaluationId, item.id, {
        score: num,
        notes: item.notes || 'Penilaian kriteria teknis sesuai instrumen.',
      });
      await loadData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal memperbarui skor.');
    }
  }

  async function handleComplete(e) {
    e.preventDefault();
    setActionLoading(true);
    setError('');

    try {
      await completeEvaluation(proposalId, evaluationId, {
        recommended_amount: Number(recommendedAmount),
        notes: evalNotes,
      });

      setCompleteModalOpen(false);
      setSuccessMsg('Evaluasi teknis dan penetapan rekomendasi berhasil disimpan!');
      await loadData();
    } catch (err) {
      setError(err.response?.data?.message || 'Gagal menyelesaikan evaluasi teknis.');
    } finally {
      setActionLoading(false);
    }
  }

  if (loading) {
    return <LoadingSpinner text="Memuat instrumen evaluasi teknis..." />;
  }

  const items = evaluation?.items || evaluation?.evaluation_items || [];
  const isCompleted = evaluation?.status === 'completed';

  const totalScore = items.reduce((acc, curr) => acc + Number(curr.score || 0), 0);
  const avgScore = items.length > 0 ? (totalScore / items.length).toFixed(1) : '—';

  return (
    <div className="space-y-6">
      <Link
        to="/evaluation"
        className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition"
      >
        <ArrowLeftIcon className="h-3.5 w-3.5" /> Kembali ke Daftar Evaluasi
      </Link>

      {/* Header Info */}
      <div className="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div className="flex items-center gap-2">
              <span className="font-mono text-xs font-semibold text-indigo-600">
                #{proposal?.proposal_number || proposalId?.slice(0, 8)}
              </span>
              <StatusBadge status={evaluation?.status || 'evaluation'} />
            </div>
            <h1 className="mt-1.5 text-xl font-bold text-slate-900 sm:text-2xl">
              Evaluasi Teknis: {proposal?.title}
            </h1>
            <p className="mt-1 text-xs text-slate-500">
              Lembaga: <span className="font-semibold text-slate-700">{proposal?.organization?.name}</span> •
              RAB Dimohon: <span className="font-semibold text-slate-700">Rp {Number(proposal?.requested_amount || 0).toLocaleString('id-ID')}</span>
            </p>
          </div>

          <div className="flex items-center gap-3">
            <a
              href={getEvaluationPdfUrl(proposalId, evaluationId)}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition"
            >
              <PrinterIcon className="h-4 w-4 text-slate-500" />
              <span>Cetak BAP Evaluasi</span>
            </a>

            {!isCompleted && (
              <button
                type="button"
                onClick={() => setCompleteModalOpen(true)}
                className="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-700 transition"
              >
                <ChartBarIcon className="h-4 w-4" />
                <span>Selesaikan & Rekomendasikan</span>
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

      {/* Skoring Summary Box */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
          <p className="text-xs font-semibold text-slate-500 uppercase">Skor Rata-Rata Teknis</p>
          <p className="mt-2 text-3xl font-black text-indigo-600">{avgScore} / 100</p>
          <p className="text-[11px] text-slate-400 mt-1">Akumulasi dari seluruh kriteria</p>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
          <p className="text-xs font-semibold text-slate-500 uppercase">RAB Diajukan</p>
          <p className="mt-2 text-xl font-bold text-slate-900">
            Rp {Number(proposal?.requested_amount || 0).toLocaleString('id-ID')}
          </p>
          <p className="text-[11px] text-slate-400 mt-1">Usulan awal pemohon</p>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
          <p className="text-xs font-semibold text-slate-500 uppercase">Rekomendasi Evaluator</p>
          <p className="mt-2 text-xl font-bold text-emerald-600">
            {evaluation?.recommended_amount
              ? `Rp ${Number(evaluation.recommended_amount).toLocaleString('id-ID')}`
              : 'Belum Ditetapkan'}
          </p>
          <p className="text-[11px] text-slate-400 mt-1">Nominal rekomendasi bantuan</p>
        </div>
      </div>

      {/* Criteria Table */}
      <div className="rounded-2xl border border-slate-200 bg-white shadow-xs overflow-hidden">
        <div className="border-b border-slate-200 px-6 py-4">
          <h2 className="text-base font-bold text-slate-900">Instrumen Kriteria Penilaian Teknis</h2>
          <p className="text-xs text-slate-500">Berikan skor (skala 0 - 100) pada setiap aspek kelayakan teknis usulan.</p>
        </div>

        {items.length === 0 ? (
          <div className="p-8 text-center text-xs text-slate-500">
            Instrumen kriteria evaluasi teknis terpasang otomatis dari modul sistem penilaian.
          </div>
        ) : (
          <table className="min-w-full divide-y divide-slate-200 text-left text-xs">
            <thead className="bg-slate-50 font-semibold uppercase text-slate-500">
              <tr>
                <th className="px-6 py-3.5">Kriteria Penilaian</th>
                <th className="px-6 py-3.5">Deskripsi & Indikator</th>
                <th className="px-6 py-3.5 w-32">Skor (0 - 100)</th>
                <th className="px-6 py-3.5">Catatan Evaluator</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 bg-white">
              {items.map((item, idx) => (
                <tr key={item.id || idx}>
                  <td className="px-6 py-4 font-bold text-slate-800">
                    {item.criteria_name || item.name || `Kriteria #${idx + 1}`}
                  </td>
                  <td className="px-6 py-4 text-slate-500 max-w-sm">
                    {item.description || 'Kesesuaian anggaran, kewajaran harga satuan, dan keberlanjutan program.'}
                  </td>
                  <td className="px-6 py-4">
                    {isCompleted ? (
                      <span className="font-bold text-indigo-700 text-sm">{item.score || 0}</span>
                    ) : (
                      <input
                        type="number"
                        min="0"
                        max="100"
                        defaultValue={item.score || 0}
                        onBlur={(e) => handleScoreChange(item, e.target.value)}
                        className="w-20 rounded-lg border border-slate-300 py-1.5 px-2.5 text-center font-bold text-slate-900 focus:border-indigo-500 focus:outline-hidden"
                      />
                    )}
                  </td>
                  <td className="px-6 py-4 text-slate-600">
                    {item.notes || 'Memenuhi kriteria teknis.'}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {/* Complete Modal */}
      <Modal
        isOpen={completeModalOpen}
        onClose={() => setCompleteModalOpen(false)}
        title="Finalisasi Evaluasi Teknis & Rekomendasi Nominal"
      >
        <form onSubmit={handleComplete} className="space-y-4">
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Rekomendasi Nominal Hibah (Rp) *
            </label>
            <input
              type="number"
              value={recommendedAmount}
              onChange={(e) => setRecommendedAmount(e.target.value)}
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2.5 px-3 text-sm font-bold text-slate-900 focus:border-indigo-500 focus:outline-hidden"
            />
            <p className="mt-1 text-[11px] text-slate-400">
              Nominal bantuan yang direkomendasikan setelah evaluasi kewajaran RAB.
            </p>
          </div>

          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Catatan Rekomendasi Teknis
            </label>
            <textarea
              rows={4}
              value={evalNotes}
              onChange={(e) => setEvalNotes(e.target.value)}
              placeholder="Tuliskan pertimbangan teknis kelayakan usulan dan justifikasi nominal..."
              className="mt-1.5 w-full rounded-lg border border-slate-300 p-3 text-xs focus:border-indigo-500 focus:outline-hidden"
            />
          </div>

          <div className="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
            <button
              type="button"
              onClick={() => setCompleteModalOpen(false)}
              className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={actionLoading}
              className="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
            >
              {actionLoading ? 'Menyimpan...' : 'Tetapkan Rekomendasi'}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}

