import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { getPublicGrantPrograms } from '../../api/public';
import { MagnifyingGlassIcon, CalendarIcon, BanknotesIcon, ArrowRightIcon } from '@heroicons/react/24/outline';
import LoadingSpinner from '../../components/common/LoadingSpinner';
import EmptyState from '../../components/common/EmptyState';

export default function PublicProgramsPage() {
  const [programs, setPrograms] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [selectedYear, setSelectedYear] = useState('');

  useEffect(() => {
    async function loadPrograms() {
      setLoading(true);
      try {
        const params = {};
        if (search) params.search = search;
        if (selectedYear) params.fiscal_year = selectedYear;

        const res = await getPublicGrantPrograms(params);
        setPrograms(res?.data || res || []);
      } catch (err) {
        console.error('Failed to load grant programs', err);
      } finally {
        setLoading(false);
      }
    }

    const timer = setTimeout(() => {
      loadPrograms();
    }, 300);

    return () => clearTimeout(timer);
  }, [search, selectedYear]);

  return (
    <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
      <div>
        <h1 className="text-3xl font-extrabold text-slate-900">Katalog Program Hibah</h1>
        <p className="mt-2 text-sm text-slate-600 max-w-2xl">
          Daftar program bantuan hibah yang dibuka untuk organisasi kemasyarakatan, yayasan, dan
          lembaga nirlaba.
        </p>
      </div>

      {/* Filter Bar */}
      <div className="mt-8 flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-xs sm:flex-row sm:items-center sm:justify-between">
        <div className="relative flex-1 max-w-md">
          <MagnifyingGlassIcon className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Cari nama program hibah..."
            className="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm placeholder-slate-400 focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500"
          />
        </div>

        <div className="flex items-center gap-3">
          <select
            value={selectedYear}
            onChange={(e) => setSelectedYear(e.target.value)}
            className="rounded-lg border border-slate-300 py-2 px-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500"
          >
            <option value="">Semua Tahun Anggaran</option>
            <option value="2026">Tahun Anggaran 2026</option>
            <option value="2025">Tahun Anggaran 2025</option>
            <option value="2024">Tahun Anggaran 2024</option>
          </select>
        </div>
      </div>

      {/* Programs List */}
      <div className="mt-8">
        {loading ? (
          <LoadingSpinner text="Memuat daftar program hibah..." />
        ) : programs.length === 0 ? (
          <EmptyState
            title="Tidak Ada Program Ditemukan"
            description="Tidak ada program hibah yang sesuai dengan kriteria pencarian Anda."
          />
        ) : (
          <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            {programs.map((prog) => (
              <div
                key={prog.id}
                className="flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-6 shadow-xs transition hover:border-blue-300 hover:shadow-md"
              >
                <div>
                  <div className="flex items-center justify-between text-xs">
                    <span className="rounded-full bg-blue-50 px-2.5 py-0.5 font-semibold text-blue-700">
                      TA {prog.fiscal_year || '2026'}
                    </span>
                    <span
                      className={`font-medium ${
                        prog.is_open ? 'text-emerald-600' : 'text-slate-500'
                      }`}
                    >
                      {prog.is_open ? 'Buka Pendaftaran' : 'Tutup'}
                    </span>
                  </div>

                  <h3 className="mt-4 text-base font-bold text-slate-900 line-clamp-2">
                    {prog.name}
                  </h3>
                  <p className="mt-2 text-xs text-slate-600 line-clamp-3">
                    {prog.description || 'Program bantuan hibah untuk mendukung kegiatan organisasi kemasyarakatan.'}
                  </p>
                </div>

                <div className="mt-6 border-t border-slate-100 pt-4">
                  <div className="space-y-1.5 text-xs text-slate-600 mb-4">
                    <div className="flex items-center justify-between">
                      <span className="flex items-center gap-1 text-slate-500">
                        <BanknotesIcon className="h-3.5 w-3.5" />
                        Pagu Maksimal:
                      </span>
                      <span className="font-semibold text-slate-800">
                        {prog.maximum_amount
                          ? `Rp ${Number(prog.maximum_amount).toLocaleString('id-ID')}`
                          : 'Sesuai Ketentuan'}
                      </span>
                    </div>
                  </div>

                  <Link
                    to={`/programs/${prog.id}`}
                    className="flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 py-2 text-center text-xs font-semibold text-white hover:bg-blue-700 transition"
                  >
                    <span>Pelajari Syarat & Dokumen</span>
                    <ArrowRightIcon className="h-3.5 w-3.5" />
                  </Link>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

