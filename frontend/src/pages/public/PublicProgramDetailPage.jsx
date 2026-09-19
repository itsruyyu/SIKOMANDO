import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import {
  getPublicGrantProgram,
  getPublicGrantProgramTimeline,
  getPublicGrantProgramDocuments,
} from '../../api/public';
import {
  CalendarDaysIcon,
  DocumentCheckIcon,
  BanknotesIcon,
  ArrowLeftIcon,
  CheckCircleIcon,
  ArrowTopRightOnSquareIcon,
} from '@heroicons/react/24/outline';
import LoadingSpinner from '../../components/common/LoadingSpinner';

export default function PublicProgramDetailPage() {
  const { id } = useParams();
  const [program, setProgram] = useState(null);
  const [timeline, setTimeline] = useState([]);
  const [documents, setDocuments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    async function loadData() {
      try {
        const [pRes, tRes, dRes] = await Promise.allSettled([
          getPublicGrantProgram(id),
          getPublicGrantProgramTimeline(id),
          getPublicGrantProgramDocuments(id),
        ]);

        if (pRes.status === 'fulfilled') {
          setProgram(pRes.value?.data || pRes.value);
        } else {
          setError('Gagal memuat informasi program.');
        }

        if (tRes.status === 'fulfilled') {
          setTimeline(tRes.value?.data || tRes.value || []);
        }
        if (dRes.status === 'fulfilled') {
          setDocuments(dRes.value?.data || dRes.value || []);
        }
      } catch (err) {
        setError('Terjadi kesalahan saat memuat data.');
      } finally {
        setLoading(false);
      }
    }

    loadData();
  }, [id]);

  if (loading) {
    return (
      <div className="mx-auto max-w-5xl px-4 py-16">
        <LoadingSpinner text="Memuat detail program..." />
      </div>
    );
  }

  if (error || !program) {
    return (
      <div className="mx-auto max-w-5xl px-4 py-16 text-center">
        <p className="text-rose-600 font-semibold">{error || 'Program tidak ditemukan.'}</p>
        <Link
          to="/programs"
          className="mt-4 inline-flex items-center gap-1.5 text-sm text-blue-600 hover:underline"
        >
          <ArrowLeftIcon className="h-4 w-4" /> Kembali ke Daftar Program
        </Link>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
      <Link
        to="/programs"
        className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition mb-6"
      >
        <ArrowLeftIcon className="h-3.5 w-3.5" /> Kembali ke Katalog Program
      </Link>

      {/* Program Header */}
      <div className="rounded-2xl border border-slate-200 bg-white p-8 shadow-xs">
        <div className="flex flex-wrap items-center gap-2">
          <span className="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
            Tahun Anggaran {program.fiscal_year || '2026'}
          </span>
          <span
            className={`rounded-full px-3 py-1 text-xs font-semibold ${
              program.is_open ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'
            }`}
          >
            {program.is_open ? 'Pendaftaran Dibuka' : 'Pendaftaran Ditutup'}
          </span>
        </div>

        <h1 className="mt-4 text-2xl font-bold text-slate-900 sm:text-3xl">{program.name}</h1>
        <p className="mt-3 text-sm text-slate-600 leading-relaxed">
          {program.description || 'Program bantuan hibah untuk mendukung kegiatan operasional dan program kemasyarakatan.'}
        </p>

        <div className="mt-6 grid grid-cols-1 gap-4 border-t border-slate-100 pt-6 sm:grid-cols-3">
          <div className="flex items-center gap-3">
            <BanknotesIcon className="h-8 w-8 text-blue-600 shrink-0" />
            <div>
              <p className="text-xs text-slate-500">Pagu Maksimal</p>
              <p className="text-sm font-bold text-slate-900">
                {program.maximum_amount
                  ? `Rp ${Number(program.maximum_amount).toLocaleString('id-ID')}`
                  : 'Sesuai Ketentuan'}
              </p>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <CalendarDaysIcon className="h-8 w-8 text-indigo-600 shrink-0" />
            <div>
              <p className="text-xs text-slate-500">Batas Pengajuan</p>
              <p className="text-sm font-bold text-slate-900">
                {program.end_date || 'Sesuai Jadwal'}
              </p>
            </div>
          </div>

          <div className="flex items-center justify-start sm:justify-end">
            <Link
              to={`/login?redirect=/proposals/create?program=${program.id}`}
              className="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-blue-700 transition"
            >
              <span>Ajukan Proposal</span>
              <ArrowTopRightOnSquareIcon className="h-4 w-4" />
            </Link>
          </div>
        </div>
      </div>

      {/* Grid: Timeline & Syarat Dokumen */}
      <div className="mt-8 grid grid-cols-1 gap-8 md:grid-cols-2">
        {/* Timeline Tahapan */}
        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
          <h2 className="text-base font-bold text-slate-900 flex items-center gap-2">
            <CalendarDaysIcon className="h-5 w-5 text-blue-600" />
            <span>Jadwal & Timeline Program</span>
          </h2>
          <div className="mt-6 space-y-4">
            {timeline.length === 0 ? (
              <p className="text-xs text-slate-500">
                Jadwal tahapan program hibah mengikuti kalender anggaran resmi yang berlaku.
              </p>
            ) : (
              timeline.map((step, idx) => (
                <div key={idx} className="flex gap-4 border-l-2 border-blue-500 pl-4 py-1">
                  <div>
                    <p className="text-xs font-semibold text-slate-900">{step.stage_name || step.name}</p>
                    <p className="text-[11px] text-slate-500">{step.period || step.date_range}</p>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>

        {/* Syarat Dokumen Wajib */}
        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
          <h2 className="text-base font-bold text-slate-900 flex items-center gap-2">
            <DocumentCheckIcon className="h-5 w-5 text-emerald-600" />
            <span>Dokumen Persyaratan Wajib</span>
          </h2>
          <div className="mt-6 space-y-3">
            {documents.length === 0 ? (
              <ul className="space-y-2 text-xs text-slate-600">
                <li className="flex items-start gap-2">
                  <CheckCircleIcon className="h-4 w-4 text-emerald-500 shrink-0 mt-0.5" />
                  <span>Surat Permohonan Hibah ditandatangani Ketua & Sekretaris</span>
                </li>
                <li className="flex items-start gap-2">
                  <CheckCircleIcon className="h-4 w-4 text-emerald-500 shrink-0 mt-0.5" />
                  <span>Proposal Usulan Kegiatan dan Rincian Anggaran Biaya (RAB)</span>
                </li>
                <li className="flex items-start gap-2">
                  <CheckCircleIcon className="h-4 w-4 text-emerald-500 shrink-0 mt-0.5" />
                  <span>Salinan Akta Notaris & SK Kemenkumham / Keterangan Terdaftar</span>
                </li>
                <li className="flex items-start gap-2">
                  <CheckCircleIcon className="h-4 w-4 text-emerald-500 shrink-0 mt-0.5" />
                  <span>Nomor Pokok Wajib Pajak (NPWP) dan Rekening Bank atas nama Lembaga</span>
                </li>
                <li className="flex items-start gap-2">
                  <CheckCircleIcon className="h-4 w-4 text-emerald-500 shrink-0 mt-0.5" />
                  <span>Surat Pernyataan Tanggung Jawab Mutlak (SPTJM)</span>
                </li>
              </ul>
            ) : (
              documents.map((doc, idx) => (
                <div key={idx} className="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 p-3">
                  <span className="text-xs font-medium text-slate-800">{doc.name || doc.title}</span>
                  <span className="text-[10px] text-slate-500">{doc.is_required ? 'Wajib' : 'Opsional'}</span>
                </div>
              ))
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

