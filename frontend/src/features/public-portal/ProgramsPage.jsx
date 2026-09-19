import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import api from '../../services/api';
import { formatCurrency, formatDate } from '../../utils/formatters';
import Card, { CardBody } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import EmptyState from '../../components/feedback/EmptyState';
import {
  CalendarDaysIcon,
  ArrowRightIcon,
  MagnifyingGlassIcon,
  TagIcon,
  BanknotesIcon,
} from '@heroicons/react/24/outline';

export function ProgramsPage() {
  const [programs, setPrograms] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [selectedYear, setSelectedYear] = useState('all');

  useEffect(() => {
    let isMounted = true;
    async function loadPrograms() {
      setIsLoading(true);
      try {
        const res = await api.get('/public/grant-programs');
        const list = Array.isArray(res?.data) ? res.data : Array.isArray(res?.data?.data) ? res.data.data : [];
        if (isMounted) {
          setPrograms(list);
        }
      } catch (err) {
        console.error('Failed to load programs:', err);
        if (isMounted) setPrograms([]);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }
    loadPrograms();
    return () => { isMounted = false; };
  }, []);

  const filteredPrograms = (Array.isArray(programs) ? programs : []).filter((p) => {
    const matchesSearch = p.name.toLowerCase().includes(search.toLowerCase()) ||
                          p.code.toLowerCase().includes(search.toLowerCase());
    const matchesYear = selectedYear === 'all' || p.fiscal_year?.toString() === selectedYear;
    return matchesSearch && matchesYear;
  });

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">
      {/* Header Banner */}
      <div className="bg-gradient-to-r from-slate-900 to-blue-950 text-white rounded-3xl p-8 sm:p-10 shadow-lg border border-slate-800">
        <div className="max-w-3xl space-y-3">
          <div className="text-xs font-bold uppercase tracking-wider text-blue-400">
            Pemerintah Provinsi Sulawesi Utara
          </div>
          <h1 className="text-2xl sm:text-4xl font-black tracking-tight">
            Katalog Program Bantuan Hibah Daerah
          </h1>
          <p className="text-xs sm:text-sm text-slate-300 leading-relaxed">
            Daftar program hibah yang diselenggarakan oleh Biro Kesejahteraan Rakyat dan instansi terkait. Pilih program untuk melihat syarat administrasi, timeline, dan alokasi pagu anggaran.
          </p>
        </div>
      </div>

      {/* Filter and Search Bar */}
      <div className="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
        <div className="relative w-full sm:max-w-md">
          <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <MagnifyingGlassIcon className="w-4 h-4" />
          </div>
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Cari nama atau kode program hibah..."
            className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm focus:outline-hidden focus:bg-white focus:border-blue-500"
          />
        </div>

        <div className="flex items-center gap-3 w-full sm:w-auto justify-end">
          <label htmlFor="yearFilter" className="text-xs font-semibold text-slate-600">
            Tahun Anggaran:
          </label>
          <select
            id="yearFilter"
            value={selectedYear}
            onChange={(e) => setSelectedYear(e.target.value)}
            className="text-xs font-semibold px-3 py-2 rounded-xl border border-slate-200 bg-white cursor-pointer shadow-2xs"
          >
            <option value="all">Semua Tahun</option>
            <option value="2026">2026</option>
            <option value="2025">2025</option>
          </select>
        </div>
      </div>

      {/* Programs Cards Grid */}
      {isLoading ? (
        <Spinner label="Memuat katalog program hibah..." />
      ) : filteredPrograms.length === 0 ? (
        <EmptyState
          title="Tidak Ada Program Hibah Ditemukan"
          description="Tidak ada program hibah yang sesuai dengan kriteria pencarian atau tahun anggaran yang dipilih."
        />
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {filteredPrograms.map((program) => (
            <Card key={program.id} hover className="flex flex-col justify-between">
              <CardBody className="space-y-4">
                <div className="flex items-center justify-between">
                  <span className="text-[11px] font-mono font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">
                    {program.code}
                  </span>
                  <span className="text-xs font-semibold text-emerald-700 flex items-center gap-1">
                    <span className="w-2 h-2 rounded-full bg-emerald-500" />
                    {program.status === 'active' ? 'Aktif Terbuka' : 'Ditutup'}
                  </span>
                </div>

                <div>
                  <h3 className="text-base font-bold text-slate-900 leading-snug">
                    {program.name}
                  </h3>
                  <p className="text-xs text-slate-500 mt-2 line-clamp-3 leading-relaxed">
                    {program.description || 'Program bantuan hibah untuk penguatan kapasitas dan kemandirian masyarakat Sulawesi Utara.'}
                  </p>
                </div>

                <div className="pt-3 border-t border-slate-100 space-y-2 text-xs">
                  <div className="flex items-center justify-between">
                    <span className="text-slate-500 flex items-center gap-1">
                      <BanknotesIcon className="w-3.5 h-3.5 text-slate-400" /> Kisaran Dana:
                    </span>
                    <span className="font-bold font-mono text-slate-800">
                      {formatCurrency(program.minimum_amount)} - {formatCurrency(program.maximum_amount)}
                    </span>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-slate-500 flex items-center gap-1">
                      <CalendarDaysIcon className="w-3.5 h-3.5 text-slate-400" /> Periode Pendaftaran:
                    </span>
                    <span className="font-semibold text-slate-700">
                      {formatDate(program.registration_start_at)} s/d {formatDate(program.registration_end_at)}
                    </span>
                  </div>
                </div>
              </CardBody>

              <div className="px-6 py-3.5 bg-slate-50 border-t border-slate-100 rounded-b-xl flex items-center justify-between">
                <span className="text-xs font-semibold text-slate-600">
                  Tahun Anggaran: <span className="font-mono text-slate-900 font-bold">{program.fiscal_year}</span>
                </span>
                <Link to={`/programs/${program.id}`}>
                  <Button variant="primary" size="xs" icon={ArrowRightIcon} iconPosition="right">
                    Detail & Syarat
                  </Button>
                </Link>
              </div>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}

export default ProgramsPage;

