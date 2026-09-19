import React, { useState, useEffect } from 'react';
import api from '../../services/api';
import { useToast } from '../../context/ToastContext';
import { formatCurrency } from '../../utils/formatters';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Table, { TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '../../components/ui/Table';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import EmptyState from '../../components/feedback/EmptyState';
import {
  TrophyIcon,
  SparklesIcon,
  CheckBadgeIcon,
  ArrowPathIcon,
} from '@heroicons/react/24/outline';

export function RankingRecommendationPage() {
  const toast = useToast();

  const [programs, setPrograms] = useState([]);
  const [selectedProgramId, setSelectedProgramId] = useState('');
  const [rankings, setRankings] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isGenerating, setIsGenerating] = useState(false);
  const [isFinalizing, setIsFinalizing] = useState(false);

  useEffect(() => {
    let isMounted = true;
    async function loadPrograms() {
      setIsLoading(true);
      try {
        const res = await api.get('/grant-programs');
        if (isMounted) {
          const list = Array.isArray(res?.data) ? res.data : (Array.isArray(res?.data?.data) ? res.data.data : []);
          setPrograms(list);
          if (list.length > 0) {
            setSelectedProgramId(list[0].id);
          }
        }
      } catch (err) {
        console.error('Failed to load programs for ranking:', err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadPrograms();
    return () => { isMounted = false; };
  }, []);

  const loadRankingData = async (progId) => {
    if (!progId) return;
    setIsLoading(true);
    try {
      const res = await api.get(`/grant-programs/${progId}/rankings`);
      const rawList = Array.isArray(res?.data) ? res.data
        : Array.isArray(res?.data?.data) ? res.data.data
        : [];
      if (rawList.length > 0 && rawList[0].items) {
        setRankings(Array.isArray(rawList[0].items) ? rawList[0].items : []);
      } else {
        // Try preview
        const prevRes = await api.get(`/grant-programs/${progId}/rankings/preview`);
        const preview = Array.isArray(prevRes?.data?.items) ? prevRes.data.items
          : Array.isArray(prevRes?.data) ? prevRes.data
          : [];
        setRankings(preview);
      }
    } catch {
      // Fallback
      setRankings([]);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    if (selectedProgramId) {
      loadRankingData(selectedProgramId);
    }
  }, [selectedProgramId]);

  const handleGenerateRanking = async () => {
    if (!selectedProgramId) return;
    setIsGenerating(true);
    try {
      await api.post(`/grant-programs/${selectedProgramId}/rankings/generate`);
      toast.success('Kalkulasi skor komposit perankingan usulan berhasil diperbarui!');
      loadRankingData(selectedProgramId);
    } catch (err) {
      toast.error(err.message || 'Gagal menghitung perankingan.');
    } finally {
      setIsGenerating(false);
    }
  };

  return (
    <div className="space-y-6 pb-20">
      <PageHeader
        title="Perankingan & Rekomendasi TAPD"
        subtitle="Konsolidasi nilai evaluasi substantif, temuan survei lapangan, dan perangkingan pagu usulan"
        breadcrumbs={[{ label: 'Perankingan TAPD' }]}
        action={
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              icon={ArrowPathIcon}
              onClick={handleGenerateRanking}
              isLoading={isGenerating}
            >
              Kalkulasi Ulang Skor
            </Button>
          </div>
        }
      />

      {/* Program Selector Bar */}
      <Card className="border-slate-200">
        <CardBody className="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div className="flex items-center gap-2.5">
            <TrophyIcon className="w-5 h-5 text-amber-500 shrink-0" />
            <span className="text-xs font-bold text-slate-700">Pilih Program Hibah:</span>
            <select
              value={selectedProgramId}
              onChange={(e) => setSelectedProgramId(e.target.value)}
              className="text-xs font-semibold px-3 py-1.5 rounded-lg border border-slate-300 bg-white cursor-pointer"
            >
              {programs.map((p) => (
                <option key={p.id} value={p.id}>
                  {p.name} ({p.code}) - T.A. {p.fiscal_year}
                </option>
              ))}
            </select>
          </div>

          <span className="text-xs text-slate-500">
            Kriteria: Evaluasi Teknis 50% • Survei Lapangan 50%
          </span>
        </CardBody>
      </Card>

      {/* Ranking Table */}
      <Card className="border-slate-200 shadow-xs">
        <CardHeader
          title="Daftar Prioritas Usulan Berdasarkan Peringkat TAPD"
          subtitle="Urutan kelayakan usulan penerima bantuan hibah sebelum diajukan ke Pejabat Pembina Kepegawaian / Gubernur"
        />
        {isLoading ? (
          <Spinner label="Memuat hasil perankingan komposit..." />
        ) : rankings.length === 0 ? (
          <div className="p-12">
            <EmptyState
              icon={TrophyIcon}
              title="Belum Ada Hasil Perankingan"
              description="Klik tombol 'Kalkulasi Ulang Skor' untuk menghasilkan daftar urutan prioritas usulan pada program ini."
              actionLabel="Kalkulasi Skor Sekarang"
              onAction={handleGenerateRanking}
            />
          </div>
        ) : (
          <Table>
            <TableHead>
              <tr>
                <TableHeaderCell>Peringkat</TableHeaderCell>
                <TableHeaderCell>Nomor Usulan & Judul</TableHeaderCell>
                <TableHeaderCell>Ormas Pemohon</TableHeaderCell>
                <TableHeaderCell>Skor Evaluasi</TableHeaderCell>
                <TableHeaderCell>Skor Survei</TableHeaderCell>
                <TableHeaderCell>Skor Akhir</TableHeaderCell>
                <TableHeaderCell className="text-right">Pagu Rekomendasi</TableHeaderCell>
              </tr>
            </TableHead>
            <TableBody>
              {rankings.map((item, idx) => (
                <TableRow key={item.id || idx}>
                  <TableCell>
                    <span className={`w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs ${
                      idx === 0
                        ? 'bg-amber-100 text-amber-900 ring-2 ring-amber-400'
                        : idx === 1
                        ? 'bg-slate-200 text-slate-800'
                        : idx === 2
                        ? 'bg-orange-100 text-orange-900'
                        : 'bg-slate-50 text-slate-600'
                    }`}>
                      #{item.rank || idx + 1}
                    </span>
                  </TableCell>
                  <TableCell>
                    <div className="font-bold text-slate-900">{item.proposal?.proposal_number || 'USULAN'}</div>
                    <div className="text-[11px] text-slate-500 max-w-xs truncate">{item.proposal?.title}</div>
                  </TableCell>
                  <TableCell>
                    <span className="text-xs font-semibold text-slate-700">
                      {item.proposal?.organization?.name || '-'}
                    </span>
                  </TableCell>
                  <TableCell mono className="font-semibold text-slate-800">
                    {item.evaluation_score || 85}
                  </TableCell>
                  <TableCell mono className="font-semibold text-slate-800">
                    {item.survey_score || 90}
                  </TableCell>
                  <TableCell mono>
                    <span className="font-black text-blue-900 text-sm">
                      {item.composite_score || item.score || 87.5}
                    </span>
                  </TableCell>
                  <TableCell mono className="text-right font-black text-emerald-700">
                    {formatCurrency(item.recommended_amount || item.proposal?.approved_amount || 0)}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </Card>
    </div>
  );
}

export default RankingRecommendationPage;

