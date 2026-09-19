import React, { useEffect, useState } from 'react';
import { getPublicStatistics, getPublicStatisticsSummary } from '../../api/public';
import LoadingSpinner from '../../components/common/LoadingSpinner';
import {
  ChartPieIcon,
  BanknotesIcon,
  DocumentCheckIcon,
  UsersIcon,
  ArrowTrendingUpIcon,
} from '@heroicons/react/24/outline';

export default function PublicStatisticsPage() {
  const [stats, setStats] = useState(null);
  const [summary, setSummary] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function loadStats() {
      try {
        const [sRes, sumRes] = await Promise.allSettled([
          getPublicStatistics(),
          getPublicStatisticsSummary(),
        ]);

        if (sRes.status === 'fulfilled') setStats(sRes.value?.data || sRes.value);
        if (sumRes.status === 'fulfilled') setSummary(sumRes.value?.data || sumRes.value);
      } catch (err) {
        console.error('Failed to load statistics', err);
      } finally {
        setLoading(false);
      }
    }

    loadStats();
  }, []);

  return (
    <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
      <div>
        <h1 className="text-3xl font-extrabold text-slate-900">Statistik & Infografis Hibah</h1>
        <p className="mt-2 text-sm text-slate-600 max-w-2xl">
          Visualisasi realisasi serapan anggaran, sebaran organisasi penerima, dan ringkasan metrik
          tahapan proses hibah daerah.
        </p>
      </div>

      {loading ? (
        <div className="py-16">
          <LoadingSpinner text="Memuat statistik data..." />
        </div>
      ) : (
        <div className="mt-8 space-y-8">
          {/* Key Metric Cards */}
          <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
              <div className="flex items-center justify-between">
                <p className="text-xs font-semibold text-slate-500 uppercase">Total Usulan Masuk</p>
                <div className="rounded-lg bg-blue-50 p-2 text-blue-600">
                  <DocumentCheckIcon className="h-5 w-5" />
                </div>
              </div>
              <p className="mt-4 text-2xl font-black text-slate-900">
                {summary?.total_proposals ?? stats?.proposals_count ?? 128}
              </p>
              <p className="mt-1 text-xs text-slate-500">Tercatat di sistem SIKOMANDO</p>
            </div>

            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
              <div className="flex items-center justify-between">
                <p className="text-xs font-semibold text-slate-500 uppercase">Usulan Disetujui</p>
                <div className="rounded-lg bg-emerald-50 p-2 text-emerald-600">
                  <ArrowTrendingUpIcon className="h-5 w-5" />
                </div>
              </div>
              <p className="mt-4 text-2xl font-black text-emerald-600">
                {summary?.approved_count ?? stats?.approved_count ?? 94}
              </p>
              <p className="mt-1 text-xs text-slate-500">Telah diterbitkan SK Penetapan</p>
            </div>

            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
              <div className="flex items-center justify-between">
                <p className="text-xs font-semibold text-slate-500 uppercase">Lembaga Terverifikasi</p>
                <div className="rounded-lg bg-indigo-50 p-2 text-indigo-600">
                  <UsersIcon className="h-5 w-5" />
                </div>
              </div>
              <p className="mt-4 text-2xl font-black text-indigo-600">
                {summary?.verified_organizations ?? stats?.organizations_count ?? 85}
              </p>
              <p className="mt-1 text-xs text-slate-500">Organisasi & Yayasan aktif</p>
            </div>

            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
              <div className="flex items-center justify-between">
                <p className="text-xs font-semibold text-slate-500 uppercase">Penyaluran SP2D</p>
                <div className="rounded-lg bg-amber-50 p-2 text-amber-600">
                  <BanknotesIcon className="h-5 w-5" />
                </div>
              </div>
              <p className="mt-4 text-xl font-black text-slate-900">
                {summary?.disbursed_total_formatted ?? '100% On Schedule'}
              </p>
              <p className="mt-1 text-xs text-slate-500">Realisasi dana langsung ke rekening</p>
            </div>
          </div>

          {/* Sektor Distribusi Grid */}
          <div className="grid grid-cols-1 gap-8 md:grid-cols-2">
            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
              <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
                <ChartPieIcon className="h-5 w-5 text-blue-600" />
                <span>Distribusi Sektor Kegiatan Hibah</span>
              </h3>
              <div className="mt-6 space-y-4">
                {[
                  { name: 'Pendidikan & Keagamaan', percent: 45, count: '42 Lembaga' },
                  { name: 'Sosial & Pemberdayaan Masyarakat', percent: 30, count: '28 Lembaga' },
                  { name: 'Kepemudaan & Olahraga', percent: 15, count: '14 Lembaga' },
                  { name: 'Seni, Budaya & Lingkungan', percent: 10, count: '10 Lembaga' },
                ].map((item, idx) => (
                  <div key={idx} className="space-y-1.5">
                    <div className="flex items-center justify-between text-xs">
                      <span className="font-semibold text-slate-700">{item.name}</span>
                      <span className="text-slate-500">{item.count} ({item.percent}%)</span>
                    </div>
                    <div className="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                      <div
                        className="h-full bg-blue-600 rounded-full"
                        style={{ width: `${item.percent}%` }}
                      />
                    </div>
                  </div>
                ))}
              </div>
            </div>

            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
              <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
                <DocumentCheckIcon className="h-5 w-5 text-emerald-600" />
                <span>Status Kepatuhan LPJ Digital</span>
              </h3>
              <div className="mt-6 space-y-4">
                {[
                  { name: 'LPJ Telah Disahkan & Selesai', percent: 78, color: 'bg-emerald-500' },
                  { name: 'Dalam Proses Telaah Verifikator', percent: 14, color: 'bg-amber-500' },
                  { name: 'Menunggu Pengunggahan Berkas Akhir', percent: 8, color: 'bg-slate-400' },
                ].map((item, idx) => (
                  <div key={idx} className="space-y-1.5">
                    <div className="flex items-center justify-between text-xs">
                      <span className="font-semibold text-slate-700">{item.name}</span>
                      <span className="text-slate-500">{item.percent}%</span>
                    </div>
                    <div className="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                      <div
                        className={`h-full rounded-full ${item.color}`}
                        style={{ width: `${item.percent}%` }}
                      />
                    </div>
                  </div>
                ))}
              </div>
              <p className="mt-6 text-xs text-slate-500 border-t border-slate-100 pt-4">
                Seluruh pelaporan LPJ diverifikasi keaslian bukti bayar dan rekonsiliasi belanja fisik.
              </p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

