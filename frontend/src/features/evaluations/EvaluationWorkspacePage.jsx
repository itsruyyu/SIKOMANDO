import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import api from '../../services/api';
import { useToast } from '../../context/ToastContext';
import { formatCurrency } from '../../utils/formatters';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Input from '../../components/ui/Input';
import Textarea from '../../components/ui/Textarea';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import Alert from '../../components/feedback/Alert';
import {
  AcademicCapIcon,
  CheckBadgeIcon,
  ArrowLeftIcon,
  BanknotesIcon,
  ChartBarIcon,
} from '@heroicons/react/24/outline';

export function EvaluationWorkspacePage() {
  const { proposalId } = useParams();
  const navigate = useNavigate();
  const toast = useToast();

  const [proposal, setProposal] = useState(null);
  const [evaluation, setEvaluation] = useState(null);
  const [items, setItems] = useState([
    { id: '1', criteria_name: 'Relevansi Program & Sasaran', weight: 25, score: 85, notes: '' },
    { id: '2', criteria_name: 'Kapasitas Kelembagaan & Pengalaman Ormas', weight: 25, score: 80, notes: '' },
    { id: '3', criteria_name: 'Kelayakan Teknis & Timeline Pelaksanaan', weight: 25, score: 85, notes: '' },
    { id: '4', criteria_name: 'Kewajaran Rincian Anggaran Biaya (RAB)', weight: 25, score: 90, notes: '' },
  ]);
  const [recommendedAmount, setRecommendedAmount] = useState(0);
  const [recommendationNotes, setRecommendationNotes] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const loadEvaluationData = async () => {
    setIsLoading(true);
    try {
      const [propRes, evalListRes] = await Promise.all([
        api.get(`/proposals/${proposalId}`),
        api.get(`/proposals/${proposalId}/evaluations`),
      ]);

      const propData = propRes.data;
      setProposal(propData);
      setRecommendedAmount(parseFloat(propData.requested_amount) || 0);

      let activeEval = null;
      if (evalListRes.data && evalListRes.data.length > 0) {
        activeEval = evalListRes.data[0];
      } else {
        try {
          const createRes = await api.post(`/proposals/${proposalId}/evaluations`, {
            notes: 'Penilaian kelayakan teknis proposal.',
          });
          activeEval = createRes.data;
        } catch {
          // If creation fails or exists
        }
      }

      if (activeEval) {
        setEvaluation(activeEval);
        if (activeEval.items && activeEval.items.length > 0) {
          setItems(activeEval.items);
        }
        if (activeEval.recommended_amount) {
          setRecommendedAmount(parseFloat(activeEval.recommended_amount));
        }
        if (activeEval.notes) {
          setRecommendationNotes(activeEval.notes);
        }
      }
    } catch (err) {
      console.error('Failed to load evaluation workspace:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadEvaluationData();
  }, [proposalId]);

  const handleScoreChange = async (index, newScore) => {
    if (isCompleted) return;
    const scoreVal = Math.min(100, Math.max(0, parseInt(newScore) || 0));
    const updated = [...items];
    updated[index].score = scoreVal;
    setItems(updated);

    const item = updated[index];
    if (evaluation && item.id) {
      try {
        await api.patch(
          `/proposals/${proposalId}/evaluations/${evaluation.id}/items/${item.id}`,
          { score: scoreVal, notes: item.notes || '' }
        );
      } catch {
        // Silently handled
      }
    }
  };

  const handleNotesChange = (index, notesVal) => {
    if (isCompleted) return;
    const updated = [...items];
    updated[index].notes = notesVal;
    setItems(updated);
  };

  // Weighted total calculation (0 - 100)
  const totalScore = items.reduce((sum, it) => {
    const w = parseFloat(it.weight) || 25;
    const s = parseFloat(it.score) || 0;
    return sum + (s * (w / 100));
  }, 0).toFixed(1);

  const handleCompleteEvaluation = async () => {
    if (!evaluation || isCompleted) return;
    setIsSubmitting(true);

    try {
      await api.post(`/proposals/${proposalId}/evaluations/${evaluation.id}/complete`, {
        total_score: parseFloat(totalScore),
        recommended_amount: recommendedAmount,
        notes: recommendationNotes || 'Proposal dinyatakan layak teknis untuk diproses ke tahapan selanjutnya.',
      });

      toast.success('Evaluasi teknis berhasil diselesaikan dan dicatat dalam rekam jejak TAPD!');
      navigate('/evaluations');
    } catch (err) {
      toast.error(err.message || 'Gagal menyelesaikan evaluasi.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const evalStatus = String(evaluation?.status || '').toLowerCase();
  const propStatus = String(proposal?.status || '').toLowerCase();
  const isCompleted =
    evalStatus === 'completed' ||
    evalStatus === 'recommended' ||
    evaluation?.completed_at != null ||
    Boolean(propStatus && !['draft', 'submitted', 'verification', 'revision', 'verified', 'evaluation'].includes(propStatus));

  if (isLoading) {
    return (
      <div className="py-20">
        <Spinner size="lg" label="Menyiapkan rubrik evaluasi teknis..." />
      </div>
    );
  }

  return (
    <div className="space-y-6 pb-20">
      <PageHeader
        title={`Evaluasi Teknis: ${proposal?.proposal_number || 'USULAN'}`}
        subtitle={`Pengusul: ${proposal?.organization?.name || '-'} • Anggaran Dimohon: ${formatCurrency(proposal?.requested_amount)}`}
        breadcrumbs={[
          { label: 'Evaluasi Teknis', to: '/evaluations' },
          { label: 'Penilaian' },
        ]}
        action={
          <Link to="/evaluations">
            <Button variant="secondary" size="sm" icon={ArrowLeftIcon}>
              Kembali ke Antrean
            </Button>
          </Link>
        }
      />

      {/* Completed Notice Banner */}
      {isCompleted && (
        <div className="p-5 rounded-2xl bg-emerald-50 border border-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-xs">
          <div className="flex items-center gap-3">
            <div className="p-2.5 rounded-xl bg-emerald-600 text-white shrink-0">
              <CheckBadgeIcon className="w-7 h-7" />
            </div>
            <div>
              <h4 className="text-sm font-bold text-emerald-950">
                Lembar Hasil Evaluasi Kelayakan Teknis Telah Selesai
              </h4>
              <p className="text-xs text-emerald-800 mt-0.5 leading-relaxed">
                Penilaian substansi usulan ini telah disahkan dan tercatat dalam sistem pertimbangan TAPD Pemprov Sulawesi Utara.
              </p>
            </div>
          </div>
          <span className="text-xs font-bold px-3 py-1.5 rounded-xl bg-emerald-200 text-emerald-950 border border-emerald-300 uppercase tracking-wider shrink-0">
            {totalScore >= 75 ? 'Rekomendasi Lolos' : 'Di Bawah Standar'}
          </span>
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {/* LEFT PANE (7 Cols): Scoring Rubrics */}
        <div className="lg:col-span-7 space-y-4">
          <Card className="border-slate-200 shadow-sm">
            <CardHeader
              title="Rubrik Penilaian Berbobot (Bobot Total: 100%)"
              subtitle="Berikan nilai angka 0 - 100 untuk setiap aspek substansi proposal"
            />
            <CardBody className="space-y-4">
              {items.map((it, idx) => (
                <div
                  key={it.id || idx}
                  className="p-4 rounded-xl border border-slate-200 bg-white space-y-2 shadow-2xs hover:border-blue-200 transition"
                >
                  <div className="flex items-center justify-between gap-3">
                    <div>
                      <span className="text-xs font-bold text-slate-800 block">
                        {it.criteria_name || it.criteria?.name || `Kriteria #${idx + 1}`}
                      </span>
                      <span className="text-[11px] text-slate-400">
                        Bobot Penilaian: <strong className="text-slate-700">{it.weight || 25}%</strong>
                      </span>
                    </div>

                    <div className="w-24 shrink-0">
                      <Input
                        type="number"
                        min="0"
                        max="100"
                        value={it.score}
                        onChange={(e) => handleScoreChange(idx, e.target.value)}
                        placeholder="0-100"
                        mono
                        disabled={isCompleted}
                        required
                      />
                    </div>
                  </div>

                  <input
                    type="text"
                    value={it.notes || ''}
                    onChange={(e) => handleNotesChange(idx, e.target.value)}
                    placeholder="Catatan justifikasi skor (opsional)..."
                    disabled={isCompleted}
                    className={`w-full text-xs p-2 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 ${isCompleted ? 'cursor-not-allowed opacity-80' : ''}`}
                  />
                </div>
              ))}
            </CardBody>
          </Card>
        </div>

        {/* RIGHT PANE (5 Cols): Weighted Score Summary & Recommendations */}
        <div className="lg:col-span-5 space-y-6">
          {/* Score Calculation Card */}
          <Card className="border-blue-200 bg-gradient-to-br from-blue-50 to-indigo-50 shadow-md">
            <CardBody className="p-6 text-center space-y-2">
              <span className="text-xs font-bold uppercase tracking-wider text-blue-800">
                Nilai Total Kelayakan Terbobot
              </span>
              <div className="text-5xl font-black font-mono text-blue-950 tracking-tight">
                {totalScore}
              </div>
              <span className="text-xs font-semibold px-3 py-1 rounded-full bg-blue-200 text-blue-900 inline-block mt-2">
                {totalScore >= 75 ? 'Rekomendasi Lolos (Memenuhi Syarat)' : 'Di Bawah Standar Minimum'}
              </span>
            </CardBody>
          </Card>

          {/* Recommendation Input Card */}
          <Card className="border-slate-200 shadow-sm">
            <CardHeader
              title="Rekomendasi Nilai Anggaran"
              subtitle="Pagu yang disetujui untuk diusulkan ke TAPD"
            />
            <CardBody className="space-y-4">
              <Input
                label="Nominal Rekomendasi (Rp)"
                type="number"
                value={recommendedAmount}
                onChange={(e) => setRecommendedAmount(parseFloat(e.target.value) || 0)}
                placeholder="Rp 0"
                mono
                disabled={isCompleted}
                required
              />
              <div className="text-[11px] text-slate-400">
                Permohonan Awal: <strong className="font-mono text-slate-700">{formatCurrency(proposal?.requested_amount)}</strong>
              </div>

              <Textarea
                label="Catatan Kesimpulan Rekomendasi Evaluator"
                value={recommendationNotes}
                onChange={(e) => setRecommendationNotes(e.target.value)}
                placeholder="Tuliskan catatan teknis untuk pertimbangan Tim Anggaran Pemerintah Daerah (TAPD)..."
                rows={4}
                disabled={isCompleted}
              />

              {isCompleted ? (
                <Button
                  variant="secondary"
                  size="lg"
                  className="w-full font-bold opacity-80 cursor-not-allowed bg-slate-100 text-slate-600 border-slate-300"
                  disabled
                  icon={CheckBadgeIcon}
                >
                  Rekomendasi Telah Diterbitkan (Selesai)
                </Button>
              ) : (
                <Button
                  variant="primary"
                  size="lg"
                  className="w-full font-bold shadow-md"
                  onClick={handleCompleteEvaluation}
                  isLoading={isSubmitting}
                  icon={CheckBadgeIcon}
                >
                  Selesaikan &amp; Terbitkan Rekomendasi
                </Button>
              )}
            </CardBody>
          </Card>
        </div>
      </div>
    </div>
  );
}

export default EvaluationWorkspacePage;

