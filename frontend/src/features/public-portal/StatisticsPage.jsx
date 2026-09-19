import React, { useState, useEffect } from 'react';
import api from '../../services/api';
import { formatCurrency } from '../../utils/formatters';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Table, { TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '../../components/ui/Table';
import Spinner from '../../components/feedback/Spinner';
import {
  BanknotesIcon,
  ChartPieIcon,
  ArrowTrendingUpIcon,
  DocumentCheckIcon,
  BuildingOffice2Icon,
  CheckCircleIcon,
  FunnelIcon,
} from '@heroicons/react/24/outline';

export function StatisticsPage() {
  const [selectedYear, setSelectedYear] = useState('all');
  const [statsData, setStatsData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    async function loadStats() {
      setIsLoading(true);
      try {
        const url = selectedYear === 'all'
          ? '/public/statistics'
          : `/public/statistics?year=${selectedYear}`;
        const res = await api.get(url);
        if (isMounted && res?.data) {
          setStatsData(res.data);
        }
      } catch (err) {
        console.error('Failed to load public statistics:', err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadStats();
    return () => { isMounted = false; };
  }, [selectedYear]);

  const summary = statsData?.summary || {};
  const byYear = statsData?.by_fiscal_year || [];

  // Absorption Rate calculation
  const approved = summary.total_approved_amount || 0;
  const disbursed = summary.total_disbursed_amount || 0;
  const absorptionRate = approved > 0 ? ((disbursed / approved) * 100).toFixed(1) : 0;

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-10">
      {/* Header Banner */}
      <div className="bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-950 text-white rounded-3xl p-8 sm:p-10 shadow-lg border border-slate-800">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div className="max-w-2xl space-y-2">
            <div className="text-xs font-bold uppercase tracking-wider text-blue-400 flex items-center gap-1.5">
              <ChartPieIcon className="w-4 h-4" />
              <span>Transparansi Fiskal Pemerintah Provinsi Sulawesi Utara</span>
            </div>
            <h1 className="text-2xl sm:text-4xl font-black tracking-tight">
              Statistik Anggaran & Penyerapan Dana Hibah
            </h1>
            <p className="text-xs sm:text-sm text-slate-300 leading-relaxed">
              Monitoring real-time alokasi pagu APBD, penetapan usulan hibah, dan pencairan dana kepada organisasi kemasyarakatan dan yayasan di seluruh Sulawesi Utara.
            </p>
          </div>

          {/* Year Filter Dropdown Component */}
          <div className="bg-white/10 backdrop-blur-md p-3.5 rounded-2xl border border-white/20 shrink-0 space-y-1.5">
            <label htmlFor="statYearFilter" className="text-xs font-bold text-blue-200 flex items-center gap-1.5">
              <FunnelIcon className="w-3.5 h-3.5" />
              <span>Filter Periode Tahun:</span>
            </label>
            <select
              id="statYearFilter"
              value={selectedYear}
              onChange={(e) => setSelectedYear(e.target.value)}
              className="w-full text-xs font-bold px-3.5 py-2.5 rounded-xl border border-white/30 bg-slate-900 text-white cursor-pointer shadow-md focus:outline-hidden focus:ring-2 focus:ring-blue-400"
            >
              <option value="all">Semua Tahun Anggaran (Keseluruhan)</option>
              <option value="2026">Tahun Anggaran 2026</option>
              <option value="2025">Tahun Anggaran 2025</option>
            </select>
          </div>
        </div>
      </div>

      {isLoading ? (
        <Spinner label="Menghitung statistik fiskal..." />
      ) : (
        <>
          {/* Top 4 Key Metric Cards */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {/* Metric 1: Total Anggaran Dikeluarkan */}
            <Card className="border-emerald-200 bg-emerald-50/40">
              <CardBody className="p-6 flex flex-col justify-between">
                <div className="flex items-center justify-between mb-4">
                  <span className="text-xs font-bold uppercase tracking-wider text-emerald-800">
                    Total Anggaran Dicairkan
                  </span>
                  <div className="p-2.5 bg-emerald-600 text-white rounded-xl shadow-2xs">
                    <BanknotesIcon className="w-5 h-5" />
                  </div>
                </div>
                <div>
                  <div className="text-2xl font-black font-mono text-emerald-950 tracking-tight">
                    {formatCurrency(disbursed)}
                  </div>
                  <p className="text-[11px] text-emerald-700 mt-1">
                    Realisasi SP2D ke rekening penerima
                  </p>
                </div>
              </CardBody>
            </Card>

            {/* Metric 2: Total Pagu Disetujui */}
            <Card className="border-blue-200 bg-blue-50/40">
              <CardBody className="p-6 flex flex-col justify-between">
                <div className="flex items-center justify-between mb-4">
                  <span className="text-xs font-bold uppercase tracking-wider text-blue-800">
                    Total Pagu Disetujui (SK)
                  </span>
                  <div className="p-2.5 bg-blue-600 text-white rounded-xl shadow-2xs">
                    <ArrowTrendingUpIcon className="w-5 h-5" />
                  </div>
                </div>
                <div>
                  <div className="text-2xl font-black font-mono text-blue-950 tracking-tight">
                    {formatCurrency(approved)}
                  </div>
                  <p className="text-[11px] text-blue-700 mt-1">
                    Ketetapan SK Gubernur Sulut
                  </p>
                </div>
              </CardBody>
            </Card>

            {/* Metric 3: Tingkat Penyerapan */}
            <Card className="border-indigo-200 bg-indigo-50/40">
              <CardBody className="p-6 flex flex-col justify-between">
                <div className="flex items-center justify-between mb-4">
                  <span className="text-xs font-bold uppercase tracking-wider text-indigo-800">
                    Tingkat Penyerapan (Serapan)
                  </span>
                  <div className="p-2.5 bg-indigo-600 text-white rounded-xl shadow-2xs">
                    <CheckCircleIcon className="w-5 h-5" />
                  </div>
                </div>
                <div>
                  <div className="text-2xl font-black font-mono text-indigo-950 tracking-tight">
                    {absorptionRate}%
                  </div>
                  <div className="w-full bg-indigo-200 rounded-full h-1.5 mt-2 overflow-hidden">
                    <div
                      className="bg-indigo-600 h-1.5 rounded-full"
                      style={{ width: `${Math.min(absorptionRate, 100)}%` }}
                    />
                  </div>
                </div>
              </CardBody>
            </Card>

            {/* Metric 4: Usulan Diproses */}
            <Card className="border-slate-200 bg-slate-50/60">
              <CardBody className="p-6 flex flex-col justify-between">
                <div className="flex items-center justify-between mb-4">
                  <span className="text-xs font-bold uppercase tracking-wider text-slate-700">
                    Total Usulan Masuk
                  </span>
                  <div className="p-2.5 bg-slate-800 text-white rounded-xl shadow-2xs">
                    <DocumentCheckIcon className="w-5 h-5" />
                  </div>
                </div>
                <div>
                  <div className="text-2xl font-black font-mono text-slate-900 tracking-tight">
                    {summary.total_proposals_submitted || 0}
                  </div>
                  <p className="text-[11px] text-slate-500 mt-1">
                    {summary.proposals_approved || 0} Disetujui • {summary.proposals_in_process || 0} Diproses
                  </p>
                </div>
              </CardBody>
            </Card>
          </div>

          {/* Breakdown Per Tahun Anggaran Table */}
          <Card className="border-slate-200 shadow-md">
            <CardHeader
              title="Rincian Alokasi Per Tahun Anggaran"
              subtitle="Data perbandingan pagu, usulan, dan penetapan antar tahun anggaran APBD Sulawesi Utara"
            />
            <Table>
              <TableHead>
                <tr>
                  <TableHeaderCell>Tahun Anggaran</TableHeaderCell>
                  <TableHeaderCell>Jumlah Program</TableHeaderCell>
                  <TableHeaderCell>Usulan Diajukan</TableHeaderCell>
                  <TableHeaderCell>Usulan Disetujui</TableHeaderCell>
                  <TableHeaderCell className="text-right">Total Anggaran Disetujui</TableHeaderCell>
                </tr>
              </TableHead>
              <TableBody>
                {byYear.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={5} className="text-center py-6 text-slate-400">
                      Belum ada data rekap per tahun anggaran.
                    </TableCell>
                  </TableRow>
                ) : (
                  byYear.map((item) => (
                    <TableRow key={item.fiscal_year}>
                      <TableCell className="font-bold font-mono text-blue-900">
                        {item.fiscal_year}
                      </TableCell>
                      <TableCell>{item.programs_count} Program</TableCell>
                      <TableCell>{item.submitted_proposals_count} Usulan</TableCell>
                      <TableCell>
                        <span className="font-semibold text-emerald-700">
                          {item.approved_proposals_count} Usulan
                        </span>
                      </TableCell>
                      <TableCell className="text-right font-mono font-bold text-slate-900">
                        {formatCurrency(item.approved_amount)}
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </Card>
        </>
      )}
    </div>
  );
}

export default StatisticsPage;

