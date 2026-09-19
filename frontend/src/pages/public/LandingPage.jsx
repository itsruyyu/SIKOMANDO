import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  getPublicGrantPrograms,
  getPublicStatisticsSummary,
  getPublicAnnouncements,
} from '../../api/public';
import {
  ShieldCheckIcon,
  ArrowRightIcon,
  CheckBadgeIcon,
  DocumentMagnifyingGlassIcon,
  SparklesIcon,
  BuildingLibraryIcon,
} from '@heroicons/react/24/outline';
import LoadingSpinner from '../../components/common/LoadingSpinner';

export default function LandingPage() {
  const [programs, setPrograms] = useState([]);
  const [summary, setSummary] = useState(null);
  const [announcements, setAnnouncements] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function loadData() {
      try {
        const [progRes, sumRes, annRes] = await Promise.allSettled([
          getPublicGrantPrograms({ per_page: 6 }),
          getPublicStatisticsSummary(),
          getPublicAnnouncements({ per_page: 3 }),
        ]);

        if (progRes.status === 'fulfilled') {
          setPrograms(progRes.value?.data || progRes.value || []);
        }
        if (sumRes.status === 'fulfilled') {
          setSummary(sumRes.value?.data || sumRes.value || null);
        }
        if (annRes.status === 'fulfilled') {
          setAnnouncements(annRes.value?.data || annRes.value || []);
        }
      } catch (err) {
        console.error('Failed to load landing data', err);
      } finally {
        setLoading(false);
      }
    }

    loadData();
  }, []);

  return (
    <div>
      {/* Hero Section */}
      <section className="relative overflow-hidden bg-gradient-to-br from-blue-900 via-indigo-900 to-slate-950 py-20 text-white lg:py-28">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_30%_30%,rgba(59,130,246,0.15),transparent)] pointer-events-none" />
        <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="max-w-3xl">
            <div className="inline-flex items-center gap-2 rounded-full border border-blue-400/30 bg-blue-500/10 px-3.5 py-1 text-xs font-medium text-blue-300 backdrop-blur-xs">
              <SparklesIcon className="h-4 w-4" />
              <span>Transparansi & Akuntabilitas Hibah Daerah</span>
            </div>
            <h1 className="mt-6 text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl">
              SIKOMANDO
            </h1>
            <p className="mt-2 text-xl font-medium text-blue-200 sm:text-2xl">
              Sistem Informasi Komprehensif Manajemen Digitalisasi Hibah Organisasi
            </p>
            <p className="mt-4 text-base text-slate-300 leading-relaxed sm:text-lg">
              Portal terpadu pengajuan, verifikasi berjenjang, evaluasi teknis, survei lapangan,
              hingga penatausahaan LPJ dan pelacakan keaslian dokumen berbasis QR Traceability.
            </p>

            <div className="mt-8 flex flex-wrap items-center gap-4">
              <Link
                to="/programs"
                className="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 hover:bg-blue-500 transition"
              >
                <span>Lihat Program Hibah</span>
                <ArrowRightIcon className="h-4 w-4" />
              </Link>
              <Link
                to="/verify"
                className="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-xs hover:bg-white/20 transition"
              >
                <ShieldCheckIcon className="h-5 w-5 text-emerald-400" />
                <span>Cek Keaslian Dokumen / QR</span>
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* Metrics Summary Strip */}
      <section className="relative -mt-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-2 gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-xl sm:grid-cols-4">
          <div className="border-b border-slate-100 pb-4 sm:border-b-0 sm:border-r sm:pb-0 sm:pr-4 text-center">
            <p className="text-2xl font-bold text-slate-900 sm:text-3xl">
              {summary?.total_programs ?? '10+'}
            </p>
            <p className="mt-1 text-xs font-medium text-slate-500">Program Terbuka</p>
          </div>
          <div className="border-b border-slate-100 pb-4 sm:border-b-0 sm:border-r sm:pb-0 sm:pr-4 text-center">
            <p className="text-2xl font-bold text-slate-900 sm:text-3xl">
              {summary?.total_proposals ?? '100%'}
            </p>
            <p className="mt-1 text-xs font-medium text-slate-500">Digital Paperless</p>
          </div>
          <div className="pb-4 sm:border-r sm:pb-0 sm:pr-4 text-center">
            <p className="text-2xl font-bold text-slate-900 sm:text-3xl">
              {summary?.verified_count ?? '100%'}
            </p>
            <p className="mt-1 text-xs font-medium text-slate-500">Traceability QR</p>
          </div>
          <div className="text-center">
            <p className="text-2xl font-bold text-emerald-600 sm:text-3xl">
              {summary?.disbursed_total_formatted ?? 'Akuntabel'}
            </p>
            <p className="mt-1 text-xs font-medium text-slate-500">Transparansi Publik</p>
          </div>
        </div>
      </section>

      {/* Program Hibah Aktif */}
      <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
          <div>
            <h2 className="text-2xl font-bold text-slate-900">Program Hibah Terbuka</h2>
            <p className="mt-1 text-sm text-slate-600">
              Pilih program hibah yang sesuai dengan fokus dan kriteria organisasi Anda.
            </p>
          </div>
          <Link
            to="/programs"
            className="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-700"
          >
            <span>Semua Program</span>
            <ArrowRightIcon className="h-4 w-4" />
          </Link>
        </div>

        {loading ? (
          <LoadingSpinner text="Memuat program hibah..." />
        ) : programs.length === 0 ? (
          <div className="mt-8 rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">
            Belum ada program hibah aktif saat ini. Silakan pantau pengumuman terbaru.
          </div>
        ) : (
          <div className="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {programs.map((prog) => (
              <div
                key={prog.id}
                className="flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-6 shadow-xs transition hover:border-blue-300 hover:shadow-md"
              >
                <div>
                  <div className="flex items-center justify-between">
                    <span className="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                      Tahun {prog.fiscal_year || new Date().getFullYear()}
                    </span>
                    <span className="text-xs text-slate-500">
                      {prog.is_open ? 'Pendaftaran Buka' : 'Ditutup'}
                    </span>
                  </div>
                  <h3 className="mt-4 text-base font-bold text-slate-900 line-clamp-2">
                    {prog.name}
                  </h3>
                  <p className="mt-2 text-xs text-slate-600 line-clamp-3">
                    {prog.description || 'Program bantuan hibah untuk mendukung kegiatan organisasi.'}
                  </p>
                </div>

                <div className="mt-6 border-t border-slate-100 pt-4">
                  <div className="flex items-center justify-between text-xs text-slate-500 mb-3">
                    <span>Maksimal Bantuan:</span>
                    <span className="font-semibold text-slate-900">
                      {prog.maximum_amount
                        ? `Rp ${Number(prog.maximum_amount).toLocaleString('id-ID')}`
                        : 'Sesuai Ketentuan'}
                    </span>
                  </div>
                  <Link
                    to={`/programs/${prog.id}`}
                    className="block w-full rounded-lg bg-slate-900 py-2 text-center text-xs font-semibold text-white hover:bg-blue-600 transition"
                  >
                    Detail & Persyaratan
                  </Link>
                </div>
              </div>
            ))}
          </div>
        )}
      </section>

      {/* Alur Pengajuan 4 Langkah */}
      <section className="border-t border-slate-200 bg-slate-100/60 py-16">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <h2 className="text-2xl font-bold text-slate-900">Tata Cara Pengajuan Hibah</h2>
            <p className="mt-2 text-sm text-slate-600 max-w-xl mx-auto">
              Seluruh proses hibah dilakukan secara digital, transparan, dan dapat dipantau
              secara langsung.
            </p>
          </div>

          <div className="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-700 font-bold">
                1
              </div>
              <h3 className="mt-4 text-sm font-bold text-slate-900">Pendaftaran & Akun</h3>
              <p className="mt-2 text-xs text-slate-600 leading-relaxed">
                Organisasi mendaftar dan melengkapi legalitas serta dokumen persyaratan kepengurusan.
              </p>
            </div>

            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-700 font-bold">
                2
              </div>
              <h3 className="mt-4 text-sm font-bold text-slate-900">Pengajuan Usulan & RAB</h3>
              <p className="mt-2 text-xs text-slate-600 leading-relaxed">
                Pilih program hibah aktif, isi rincian kegiatan, tujuan, target output, dan susun RAB terinci.
              </p>
            </div>

            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-700 font-bold">
                3
              </div>
              <h3 className="mt-4 text-sm font-bold text-slate-900">Verifikasi & Penilaian</h3>
              <p className="mt-2 text-xs text-slate-600 leading-relaxed">
                Tim verifikator, evaluator, dan surveyor melakukan uji kelayakan dokumen dan cek fisik lapangan.
              </p>
            </div>

            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-700 font-bold">
                4
              </div>
              <h3 className="mt-4 text-sm font-bold text-slate-900">SK, Pencairan & LPJ</h3>
              <p className="mt-2 text-xs text-slate-600 leading-relaxed">
                Penerbitan SK resmi ber-QR, penyaluran SP2D langsung ke rekening, dan penyerahan LPJ digital.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* QR Banner Callout */}
      <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div className="rounded-2xl bg-gradient-to-r from-blue-700 to-indigo-800 p-8 text-white sm:p-12">
          <div className="flex flex-col items-center justify-between gap-6 md:flex-row">
            <div className="max-w-xl">
              <div className="flex items-center gap-2">
                <CheckBadgeIcon className="h-6 w-6 text-emerald-400" />
                <span className="text-xs font-bold uppercase tracking-wider text-blue-200">
                  Keamanan Dokumen Terjamin
                </span>
              </div>
              <h3 className="mt-2 text-2xl font-bold sm:text-3xl">
                Cek Keaslian Dokumen & TTE Berbasis QR
              </h3>
              <p className="mt-2 text-sm text-blue-100 leading-relaxed">
                Setiap dokumen resmi, SK Penetapan, Berita Acara, dan naskah hibah SIKOMANDO
                dilengkapi tanda tangan elektronik dan QR Code yang dapat diverifikasi secara publik
                untuk mencegah pemalsuan.
              </p>
            </div>
            <Link
              to="/verify"
              className="shrink-0 rounded-xl bg-white px-6 py-3.5 text-sm font-bold text-blue-900 shadow-md hover:bg-blue-50 transition"
            >
              Verifikasi Dokumen Sekarang
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
}

