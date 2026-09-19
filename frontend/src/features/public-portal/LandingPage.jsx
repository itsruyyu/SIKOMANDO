import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../../services/api';
import { formatCurrency, formatDate } from '../../utils/formatters';
import Button from '../../components/ui/Button';
import Card, { CardBody } from '../../components/ui/Card';
import Badge from '../../components/ui/Badge';
import Spinner from '../../components/feedback/Spinner';
import {
  MagnifyingGlassIcon,
  ArrowRightIcon,
  BanknotesIcon,
  DocumentCheckIcon,
  BuildingLibraryIcon,
  ShieldCheckIcon,
  CalendarDaysIcon,
  QrCodeIcon,
  MapPinIcon,
  SparklesIcon,
  UserGroupIcon,
  ArrowTrendingUpIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
} from '@heroicons/react/24/outline';

export function LandingPage() {
  const navigate = useNavigate();

  // Quick Tracker input state
  const [trackingNumber, setTrackingNumber] = useState('');

  // Statistics State with Year Filter
  const [selectedYear, setSelectedYear] = useState('all'); // 'all', '2026', '2025'
  const [statsSummary, setStatsSummary] = useState(null);
  const [statsLoading, setStatsLoading] = useState(true);

  // Active Grant Programs State
  const [programs, setPrograms] = useState([]);
  const [programsLoading, setProgramsLoading] = useState(true);

  // Announcements State
  const [announcements, setAnnouncements] = useState([]);
  const [announcementsLoading, setAnnouncementsLoading] = useState(true);

  // Fetch Public Statistics when Year changes
  useEffect(() => {
    let isMounted = true;
    async function loadStatistics() {
      setStatsLoading(true);
      try {
        const url = selectedYear === 'all' 
          ? '/public/statistics' 
          : `/public/statistics?year=${selectedYear}`;
        const res = await api.get(url);
        if (isMounted && res?.data?.summary) {
          setStatsSummary(res.data.summary);
        }
      } catch (err) {
        console.error('Failed to load public statistics:', err);
      } finally {
        if (isMounted) setStatsLoading(false);
      }
    }
    loadStatistics();
    return () => { isMounted = false; };
  }, [selectedYear]);

  // Fetch Active Grant Programs
  useEffect(() => {
    let isMounted = true;
    async function loadPrograms() {
      setProgramsLoading(true);
      try {
        const res = await api.get('/public/grant-programs');
        if (isMounted && res?.data) {
          setPrograms(res.data.slice(0, 3)); // show top 3 programs
        }
      } catch (err) {
        console.error('Failed to load grant programs:', err);
      } finally {
        if (isMounted) setProgramsLoading(false);
      }
    }
    loadPrograms();
    return () => { isMounted = false; };
  }, []);

  // Fetch Announcements
  useEffect(() => {
    let isMounted = true;
    async function loadAnnouncements() {
      setAnnouncementsLoading(true);
      try {
        const res = await api.get('/public/announcements');
        if (isMounted && res?.data) {
          setAnnouncements(res.data.slice(0, 3));
        }
      } catch (err) {
        console.error('Failed to load announcements:', err);
      } finally {
        if (isMounted) setAnnouncementsLoading(false);
      }
    }
    loadAnnouncements();
    return () => { isMounted = false; };
  }, []);

  const handleQuickTrack = (e) => {
    e.preventDefault();
    if (!trackingNumber.trim()) return;
    navigate(`/tracker?code=${encodeURIComponent(trackingNumber.trim())}`);
  };

  return (
    <div className="space-y-20 pb-20">
      {/* ========================================================================= */}
      {/* SECTION 1: MODERN HERO WITH QUICK TRACKING & DYNAMIC HEADLINE             */}
      {/* ========================================================================= */}
      <section className="relative bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950 text-white pt-20 pb-28 px-4 sm:px-6 lg:px-8 overflow-hidden">
        {/* Decorative Grid and Gradients */}
        <div className="absolute inset-0 bg-[radial-gradient(#38bdf8_1px,transparent_1px)] [background-size:24px_24px] opacity-10 pointer-events-none" />
        <div className="absolute top-1/4 -left-20 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none" />
        <div className="absolute bottom-10 -right-20 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none" />

        <div className="max-w-5xl mx-auto text-center relative z-10 space-y-8">
          {/* Official Gov Tag */}
          <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-950/80 border border-blue-700/60 text-blue-300 text-xs font-semibold backdrop-blur-md shadow-xs animate-in fade-in duration-300">
            <SparklesIcon className="w-4 h-4 text-amber-400" />
            <span>Portal Resmi Tata Kelola Hibah Daerah Provinsi Sulawesi Utara</span>
          </div>

          {/* Headline */}
          <h1 className="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-tight sm:leading-none">
            Transparansi, Kecepatan & Akuntabilitas{' '}
            <span className="bg-gradient-to-r from-blue-400 via-sky-300 to-amber-300 bg-clip-text text-transparent">
              Hibah Komando Sulawesi Utara
            </span>
          </h1>

          <p className="max-w-3xl mx-auto text-slate-300 text-sm sm:text-base leading-relaxed">
            Platform satu pintu Biro Kesejahteraan Rakyat Setda Prov. Sulut untuk pengajuan usulan, verifikasi berbasis berkas digital, evaluasi teknis, survei geospasial lapangan ber-GPS, penetapan SK resmi, hingga pertanggungjawaban dana (LPJ).
          </p>

          {/* Quick Tracking Bar Box */}
          <div className="max-w-2xl mx-auto bg-white/10 backdrop-blur-xl p-2.5 sm:p-3 rounded-2xl border border-white/20 shadow-2xl">
            <form onSubmit={handleQuickTrack} className="flex flex-col sm:flex-row gap-2">
              <div className="relative flex-1">
                <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                  <MagnifyingGlassIcon className="w-5 h-5" />
                </div>
                <input
                  type="text"
                  value={trackingNumber}
                  onChange={(e) => setTrackingNumber(e.target.value)}
                  placeholder="Masukkan nomor usulan (contoh: PROP-SULUT/2026/01/0013)..."
                  className="w-full pl-10 pr-4 py-3 bg-white text-slate-900 placeholder-slate-400 text-xs sm:text-sm rounded-xl focus:outline-hidden focus:ring-2 focus:ring-blue-500 font-medium"
                />
              </div>
              <Button
                type="submit"
                variant="primary"
                size="lg"
                icon={ArrowRightIcon}
                iconPosition="right"
                className="shrink-0 bg-blue-600 hover:bg-blue-500 font-bold"
              >
                Lacak Usulan
              </Button>
            </form>
            <div className="text-[11px] text-slate-400 mt-2 text-left px-2 flex items-center gap-1.5">
              <ShieldCheckIcon className="w-4 h-4 text-emerald-400 shrink-0" />
              <span>Dukungan pencarian langsung status usulan tanpa perlu masuk sistem terlebih dahulu.</span>
            </div>
          </div>

          {/* CTA Buttons */}
          <div className="flex flex-wrap items-center justify-center gap-4 pt-4">
            <Link to="/programs">
              <Button variant="primary" size="lg" icon={DocumentCheckIcon} className="bg-blue-600 shadow-lg hover:shadow-blue-500/25">
                Lihat Program Hibah Buka
              </Button>
            </Link>
            <Link to="/transparency">
              <Button variant="outline" size="lg" className="border-slate-700 bg-slate-800/80 text-white hover:bg-slate-800">
                Portal Transparansi Publik
              </Button>
            </Link>
            <Link to="/verify">
              <Button variant="ghost" size="lg" icon={QrCodeIcon} className="text-slate-300 hover:text-white hover:bg-slate-800/60">
                Validasi QR Code
              </Button>
            </Link>
          </div>
        </div>
      </section>

      {/* ========================================================================= */}
      {/* SECTION 2: STATISTIK ANGGARAN & REALISASI DENGAN DROPDOWN PER TAHUN       */}
      {/* ========================================================================= */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-16 relative z-20">
        <Card className="shadow-xl border border-slate-200/90 overflow-hidden bg-white">
          {/* Section Header & Year Filter Dropdown */}
          <div className="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/50">
            <div>
              <div className="flex items-center gap-2">
                <span className="w-2 h-2 rounded-full bg-blue-600" />
                <h2 className="text-lg font-bold text-slate-900 tracking-tight">
                  Ringkasan Kinerja & Statistik Realisasi Hibah
                </h2>
              </div>
              <p className="text-xs text-slate-500 mt-0.5">
                Data teragregasi secara otomatis dari database SIKOMANDO Pemerintah Provinsi Sulawesi Utara.
              </p>
            </div>

            {/* Year Selector Dropdown */}
            <div className="flex items-center gap-2.5">
              <label htmlFor="yearSelect" className="text-xs font-semibold text-slate-700 shrink-0">
                Tahun Anggaran:
              </label>
              <select
                id="yearSelect"
                value={selectedYear}
                onChange={(e) => setSelectedYear(e.target.value)}
                className="text-xs font-semibold px-3 py-2 rounded-lg border border-slate-300 bg-white shadow-2xs text-slate-800 focus:outline-hidden focus:border-blue-500 cursor-pointer"
              >
                <option value="all">Semua Tahun Anggaran</option>
                <option value="2026">Tahun Anggaran 2026</option>
                <option value="2025">Tahun Anggaran 2025</option>
              </select>
            </div>
          </div>

          {/* Stats Cards Grid */}
          <CardBody className="p-6">
            {statsLoading ? (
              <Spinner label="Memperbarui data statistik anggaran..." />
            ) : (
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                {/* Stat 1: Total Anggaran Dicairkan */}
                <div className="bg-gradient-to-br from-emerald-50 to-teal-50 p-5 rounded-2xl border border-emerald-200/80 flex flex-col justify-between">
                  <div className="flex items-center justify-between mb-3">
                    <span className="text-xs font-bold uppercase tracking-wider text-emerald-800">
                      Total Dana Dicairkan (SP2D)
                    </span>
                    <div className="p-2 bg-emerald-600 text-white rounded-xl shadow-2xs">
                      <BanknotesIcon className="w-5 h-5" />
                    </div>
                  </div>
                  <div>
                    <div className="text-2xl font-black font-mono text-emerald-950 tracking-tight">
                      {formatCurrency(statsSummary?.total_disbursed_amount || 0)}
                    </div>
                    <div className="text-[11px] text-emerald-700 mt-1 flex items-center gap-1">
                      <CheckCircleIcon className="w-3.5 h-3.5" />
                      <span>Telah disalurkan ke rekening organisasi</span>
                    </div>
                  </div>
                </div>

                {/* Stat 2: Total Pagu Anggaran Disetujui */}
                <div className="bg-gradient-to-br from-blue-50 to-indigo-50 p-5 rounded-2xl border border-blue-200/80 flex flex-col justify-between">
                  <div className="flex items-center justify-between mb-3">
                    <span className="text-xs font-bold uppercase tracking-wider text-blue-800">
                      Pagu Disetujui (SK Gubernur)
                    </span>
                    <div className="p-2 bg-blue-600 text-white rounded-xl shadow-2xs">
                      <ArrowTrendingUpIcon className="w-5 h-5" />
                    </div>
                  </div>
                  <div>
                    <div className="text-2xl font-black font-mono text-blue-950 tracking-tight">
                      {formatCurrency(statsSummary?.total_approved_amount || 0)}
                    </div>
                    <div className="text-[11px] text-blue-700 mt-1 flex items-center gap-1">
                      <span>Dari {statsSummary?.proposals_approved || 0} usulan disetujui</span>
                    </div>
                  </div>
                </div>

                {/* Stat 3: Total Usulan Masuk */}
                <div className="bg-gradient-to-br from-slate-50 to-slate-100 p-5 rounded-2xl border border-slate-200 flex flex-col justify-between">
                  <div className="flex items-center justify-between mb-3">
                    <span className="text-xs font-bold uppercase tracking-wider text-slate-700">
                      Total Usulan Masuk
                    </span>
                    <div className="p-2 bg-slate-800 text-white rounded-xl shadow-2xs">
                      <DocumentCheckIcon className="w-5 h-5" />
                    </div>
                  </div>
                  <div>
                    <div className="text-2xl font-black font-mono text-slate-900 tracking-tight">
                      {statsSummary?.total_proposals_submitted || 0} Usulan
                    </div>
                    <div className="text-[11px] text-slate-600 mt-1">
                      {statsSummary?.proposals_in_process || 0} Dalam Tahap Verifikasi & Evaluasi
                    </div>
                  </div>
                </div>

                {/* Stat 4: Organisasi Penerima */}
                <div className="bg-gradient-to-br from-amber-50 to-orange-50 p-5 rounded-2xl border border-amber-200/80 flex flex-col justify-between">
                  <div className="flex items-center justify-between mb-3">
                    <span className="text-xs font-bold uppercase tracking-wider text-amber-800">
                      Organisasi Terdaftar
                    </span>
                    <div className="p-2 bg-amber-600 text-white rounded-xl shadow-2xs">
                      <UserGroupIcon className="w-5 h-5" />
                    </div>
                  </div>
                  <div>
                    <div className="text-2xl font-black font-mono text-amber-950 tracking-tight">
                      {statsSummary?.recipient_organizations_count || 0} Ormas / Yayasan
                    </div>
                    <div className="text-[11px] text-amber-700 mt-1">
                      Tersebar di 15 Kabupaten / Kota se-Sulut
                    </div>
                  </div>
                </div>
              </div>
            )}
          </CardBody>
        </Card>
      </section>

      {/* ========================================================================= */}
      {/* SECTION 3: 6-TAHAPAN ALUR PENGELOLAAN HIBAH DIGITAL INTERAKTIF            */}
      {/* ========================================================================= */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center max-w-3xl mx-auto mb-12">
          <div className="text-xs font-bold uppercase tracking-wider text-blue-600 mb-2">
            SOP Biro Kesra Setda Prov. Sulawesi Utara
          </div>
          <h2 className="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
            Alur Terintegrasi Hibah Komando Digital
          </h2>
          <p className="text-xs sm:text-sm text-slate-500 mt-2">
            Setiap usulan diproses secara bertahap melalui sistem elektronik dengan jejak audit yang tidak dapat diubah (immutable).
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {[
            {
              step: '01',
              title: 'Pengajuan & RAB Digital',
              desc: 'Pemohon mengisi formulir legalitas ormas, narasi proposal, dan rincian RAB dengan pembatasan pagu otomatis.',
              badge: 'Pemohon',
            },
            {
              step: '02',
              title: 'Verifikasi Administrasi',
              desc: 'Pemeriksaan kelengkapan dokumen legalitas (Akta, Kemenkumham, NPWP, Rekening Bank) oleh verifikator.',
              badge: 'Verifikator',
            },
            {
              step: '03',
              title: 'Evaluasi & Survei GPS',
              desc: 'Penilaian substansi teknis berbobot dan survei lapangan dengan tagging koordinat GPS serta dokumentasi foto.',
              badge: 'Evaluator & Surveyor',
            },
            {
              step: '04',
              title: 'Perankingan TAPD & SK',
              desc: 'Kompilasi nilai kelayakan oleh TAPD dan penerbitan Surat Keputusan (SK) Gubernur dengan QR Code resmi.',
              badge: 'Approver / Gubernur',
            },
            {
              step: '05',
              title: 'Pencairan Dana SP2D',
              desc: 'Penerbitan surat perintah pencairan dana secara bertahap via transfer bank langsung ke rekening ormas.',
              badge: 'Keuangan / BPKAD',
            },
            {
              step: '06',
              title: 'Realisasi, Kuitansi & LPJ',
              desc: 'Pelaporan penggunaan dana dengan bukti kuitansi ber-QR dan Berita Acara Serah Terima (BAST) fisik.',
              badge: 'Pemohon & Auditor',
            },
          ].map((item, idx) => (
            <div
              key={idx}
              className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-md hover:border-blue-300 transition-all duration-200 flex flex-col justify-between group"
            >
              <div>
                <div className="flex items-center justify-between mb-4">
                  <span className="text-2xl font-black font-mono text-blue-600/40 group-hover:text-blue-600 transition">
                    {item.step}
                  </span>
                  <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                    {item.badge}
                  </span>
                </div>
                <h3 className="text-base font-bold text-slate-800 mb-2 group-hover:text-blue-600 transition">
                  {item.title}
                </h3>
                <p className="text-xs text-slate-500 leading-relaxed">{item.desc}</p>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* ========================================================================= */}
      {/* SECTION 4: DAFTAR PROGRAM HIBAH TERBUKA & AKTIF                            */}
      {/* ========================================================================= */}
      <section className="bg-slate-100/70 py-16 px-4 sm:px-6 lg:px-8 border-y border-slate-200/60">
        <div className="max-w-7xl mx-auto space-y-10">
          <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
              <div className="text-xs font-bold uppercase tracking-wider text-blue-600 mb-1">
                Peluang Bantuan Hibah Sulut 2026
              </div>
              <h2 className="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                Program Hibah Daerah Sedang Dibuka
              </h2>
            </div>
            <Link to="/programs">
              <Button variant="outline" size="sm" icon={ArrowRightIcon} iconPosition="right">
                Lihat Semua Program
              </Button>
            </Link>
          </div>

          {programsLoading ? (
            <Spinner label="Memuat program hibah..." />
          ) : programs.length === 0 ? (
            <div className="bg-white p-8 text-center rounded-xl border border-slate-200 text-slate-500 text-xs">
              Saat ini belum ada program hibah aktif baru.
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {programs.map((prog) => (
                <Card key={prog.id} hover className="flex flex-col justify-between">
                  <CardBody className="space-y-4">
                    <div className="flex items-center justify-between">
                      <span className="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">
                        {prog.code}
                      </span>
                      <span className="text-xs font-semibold text-emerald-700 flex items-center gap-1">
                        <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" /> Pendaftaran Dibuka
                      </span>
                    </div>

                    <div>
                      <h3 className="text-base font-bold text-slate-900 leading-snug">
                        {prog.name}
                      </h3>
                      <p className="text-xs text-slate-500 mt-2 line-clamp-2 leading-relaxed">
                        {prog.description || 'Program bantuan hibah daerah untuk penguatan kapasitas dan kemandirian masyarakat.'}
                      </p>
                    </div>

                    <div className="pt-3 border-t border-slate-100 space-y-2 text-xs">
                      <div className="flex items-center justify-between">
                        <span className="text-slate-500">Pagu Per Usulan:</span>
                        <span className="font-bold font-mono text-slate-800">
                          {formatCurrency(prog.minimum_amount)} - {formatCurrency(prog.maximum_amount)}
                        </span>
                      </div>
                      <div className="flex items-center justify-between">
                        <span className="text-slate-500">Batas Waktu:</span>
                        <span className="font-semibold text-slate-700">
                          {formatDate(prog.registration_end_at)}
                        </span>
                      </div>
                    </div>
                  </CardBody>

                  <div className="px-6 py-3.5 bg-slate-50 border-t border-slate-100 rounded-b-xl flex items-center justify-between">
                    <span className="text-[11px] text-slate-500">T.A. {prog.fiscal_year}</span>
                    <Link to={`/programs/${prog.id}`}>
                      <Button variant="primary" size="xs" icon={ArrowRightIcon} iconPosition="right">
                        Syarat & Ketentuan
                      </Button>
                    </Link>
                  </div>
                </Card>
              ))}
            </div>
          )}
        </div>
      </section>

      {/* ========================================================================= */}
      {/* SECTION 5: PENGUMUMAN & BERITA RESMI                                      */}
      {/* ========================================================================= */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
          <div>
            <div className="text-xs font-bold uppercase tracking-wider text-blue-600 mb-1">
              Warta & Informasi Terkini
            </div>
            <h2 className="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
              Pengumuman Resmi Biro Kesra Sulut
            </h2>
          </div>
        </div>

        {announcementsLoading ? (
          <Spinner label="Memuat pengumuman..." />
        ) : announcements.length === 0 ? (
          <div className="bg-white p-8 text-center rounded-xl border border-slate-200 text-slate-500 text-xs">
            Belum ada pengumuman publik terbaru.
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {announcements.map((ann) => (
              <div
                key={ann.id}
                className="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-md transition flex flex-col justify-between"
              >
                <div>
                  <div className="flex items-center justify-between text-xs text-slate-400 mb-3">
                    <span className="inline-flex items-center gap-1 font-medium">
                      <CalendarDaysIcon className="w-4 h-4 text-slate-400" />
                      {formatDate(ann.published_at)}
                    </span>
                    {ann.is_pinned && (
                      <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                        Prioritas
                      </span>
                    )}
                  </div>
                  <h3 className="text-sm font-bold text-slate-800 line-clamp-2 mb-2 leading-snug">
                    {ann.title}
                  </h3>
                  <p className="text-xs text-slate-500 line-clamp-3 leading-relaxed">
                    {ann.excerpt || ann.content}
                  </p>
                </div>
                <div className="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between">
                  <span className="text-[10px] text-blue-600 font-semibold uppercase tracking-wider">
                    {ann.category || 'Informasi'}
                  </span>
                  <Link to="/programs" className="text-xs font-bold text-slate-700 hover:text-blue-600 transition flex items-center gap-1">
                    <span>Baca</span>
                    <ArrowRightIcon className="w-3 h-3" />
                  </Link>
                </div>
              </div>
            ))}
          </div>
        )}
      </section>

      {/* ========================================================================= */}
      {/* SECTION 6: KEUNGGULAN SIKOMANDO & VALIDASI QR CODE                         */}
      {/* ========================================================================= */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="bg-gradient-to-tr from-slate-900 via-blue-950 to-indigo-950 text-white rounded-3xl p-8 sm:p-12 shadow-2xl border border-blue-900/50 relative overflow-hidden">
          <div className="absolute right-0 top-0 bottom-0 w-1/3 bg-[radial-gradient(#60a5fa_1px,transparent_1px)] [background-size:16px_16px] opacity-20 pointer-events-none" />

          <div className="max-w-3xl space-y-6 relative z-10">
            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 text-blue-300 text-xs font-semibold border border-blue-500/30">
              <QrCodeIcon className="w-4 h-4 text-emerald-400" />
              <span>Sistem Kriptografi & Penomoran Dokumen Anti-Pemalsuan</span>
            </div>

            <h2 className="text-2xl sm:text-4xl font-black tracking-tight leading-snug">
              Semua Surat Keputusan (SK), Kuitansi & BAST Dilengkapi Validasi QR Interaktif
            </h2>

            <p className="text-xs sm:text-sm text-slate-300 leading-relaxed">
              SIKOMANDO mencegah duplikasi bantuan dan pemalsuan berkas melalui tanda tangan elektronik (TTE) dan QR Code kriptografis unik. Masyarakat dan aparat pemeriksa dapat memindai atau memasukkan token QR langsung pada portal verifikasi publik.
            </p>

            <div className="pt-2 flex flex-wrap items-center gap-4">
              <Link to="/verify">
                <Button variant="primary" size="md" icon={ShieldCheckIcon} className="bg-blue-500 hover:bg-blue-400 text-slate-950 font-bold">
                  Buka Portal Verifikasi Dokumen
                </Button>
              </Link>
              <Link to="/transparency">
                <Button variant="outline" size="md" className="border-slate-600 text-white hover:bg-white/10">
                  Lihat Daftar Penerima Hibah
                </Button>
              </Link>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}

export default LandingPage;

