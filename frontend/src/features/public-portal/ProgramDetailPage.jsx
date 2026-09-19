import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../../services/api';
import { formatCurrency, formatDate } from '../../utils/formatters';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import Alert from '../../components/feedback/Alert';
import {
  ArrowLeftIcon,
  CalendarDaysIcon,
  BanknotesIcon,
  DocumentTextIcon,
  CheckBadgeIcon,
  ArrowDownTrayIcon,
  SparklesIcon,
} from '@heroicons/react/24/outline';

export function ProgramDetailPage() {
  const { id } = useParams();
  const [program, setProgram] = useState(null);
  const [timeline, setTimeline] = useState([]);
  const [documents, setDocuments] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [errorMsg, setErrorMsg] = useState('');

  useEffect(() => {
    let isMounted = true;
    async function loadProgramDetail() {
      setIsLoading(true);
      setErrorMsg('');
      try {
        const [progRes, timeRes, docRes] = await Promise.allSettled([
          api.get(`/public/grant-programs/${id}`),
          api.get(`/public/grant-programs/${id}/timeline`),
          api.get(`/public/grant-programs/${id}/documents`),
        ]);

        if (!isMounted) return;

        if (progRes.status === 'fulfilled' && progRes.value?.data) {
          setProgram(progRes.value.data);
        } else {
          throw new Error('Program hibah tidak ditemukan.');
        }

        if (timeRes.status === 'fulfilled' && timeRes.value?.data) {
          setTimeline(timeRes.value.data);
        }

        if (docRes.status === 'fulfilled' && docRes.value?.data) {
          setDocuments(docRes.value.data);
        }
      } catch (err) {
        if (isMounted) setErrorMsg(err.message || 'Gagal memuat detail program.');
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadProgramDetail();
    return () => { isMounted = false; };
  }, [id]);

  if (isLoading) {
    return (
      <div className="max-w-5xl mx-auto px-4 py-16">
        <Spinner size="lg" label="Memuat informasi detail program hibah..." />
      </div>
    );
  }

  if (errorMsg || !program) {
    return (
      <div className="max-w-3xl mx-auto px-4 py-16">
        <Alert type="danger" title="Program Tidak Ditemukan">
          <p className="mb-4">{errorMsg || 'Data program hibah tidak tersedia atau telah dihapus.'}</p>
          <Link to="/programs">
            <Button variant="secondary" size="sm" icon={ArrowLeftIcon}>
              Kembali ke Daftar Program
            </Button>
          </Link>
        </Alert>
      </div>
    );
  }

  return (
    <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
      {/* Back Button */}
      <div>
        <Link
          to="/programs"
          className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-blue-600 transition"
        >
          <ArrowLeftIcon className="w-3.5 h-3.5" />
          <span>Kembali ke Katalog Program</span>
        </Link>
      </div>

      {/* Program Main Hero */}
      <div className="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-md flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div className="space-y-2 max-w-2xl">
          <div className="flex items-center gap-2">
            <span className="font-mono text-xs font-bold px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200">
              {program.code}
            </span>
            <span className="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full">
              Tahun Anggaran {program.fiscal_year}
            </span>
          </div>
          <h1 className="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
            {program.name}
          </h1>
          <p className="text-xs sm:text-sm text-slate-600 leading-relaxed pt-1">
            {program.description || 'Program bantuan hibah resmi Pemerintah Provinsi Sulawesi Utara.'}
          </p>
        </div>

        <div className="shrink-0 flex flex-col gap-3">
          <Link to="/login">
            <Button variant="primary" size="lg" icon={SparklesIcon} className="w-full">
              Ajukan Usulan (Login)
            </Button>
          </Link>
          <span className="text-[11px] text-center text-slate-400">
            Khusus organisasi berbadan hukum terdaftar
          </span>
        </div>
      </div>

      {/* Pagu & Timeline Info Grid */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card className="border-slate-200">
          <CardBody className="space-y-1">
            <div className="text-xs font-semibold text-slate-500 flex items-center gap-1.5">
              <BanknotesIcon className="w-4 h-4 text-emerald-600" /> Kisaran Dana Hibah:
            </div>
            <div className="text-lg font-black font-mono text-slate-900">
              {formatCurrency(program.minimum_amount)}
            </div>
            <div className="text-xs text-slate-400">sampai dengan {formatCurrency(program.maximum_amount)}</div>
          </CardBody>
        </Card>

        <Card className="border-slate-200">
          <CardBody className="space-y-1">
            <div className="text-xs font-semibold text-slate-500 flex items-center gap-1.5">
              <CalendarDaysIcon className="w-4 h-4 text-blue-600" /> Periode Pendaftaran:
            </div>
            <div className="text-sm font-bold text-slate-800">
              {formatDate(program.registration_start_at)}
            </div>
            <div className="text-xs text-slate-400">s/d {formatDate(program.registration_end_at)}</div>
          </CardBody>
        </Card>

        <Card className="border-slate-200">
          <CardBody className="space-y-1">
            <div className="text-xs font-semibold text-slate-500 flex items-center gap-1.5">
              <CheckBadgeIcon className="w-4 h-4 text-amber-600" /> Total Pagu Disediakan:
            </div>
            <div className="text-lg font-black font-mono text-slate-900">
              {formatCurrency(program.total_budget || 0)}
            </div>
            <div className="text-xs text-slate-400">APBD Prov. Sulawesi Utara</div>
          </CardBody>
        </Card>
      </div>

      {/* Requirements Documents Checklist */}
      <Card className="border-slate-200">
        <CardHeader
          title="Persyaratan Dokumen Legalitas & Usulan"
          subtitle="Dokumen-dokumen berikut wajib diunggah dalam format PDF saat menyusun usulan proposal"
        />
        <CardBody className="p-0">
          {documents.length === 0 ? (
            <div className="p-6 text-center text-xs text-slate-500">
              Persyaratan dokumen standar berlaku: Surat Permohonan, Akta Notaris, SK Kemenkumham, NPWP, Rekening Bank, dan Rincian RAB.
            </div>
          ) : (
            <div className="divide-y divide-slate-100">
              {documents.map((doc, idx) => (
                <div key={idx} className="p-4 flex items-center justify-between gap-4 hover:bg-slate-50 transition">
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                      <DocumentTextIcon className="w-4 h-4" />
                    </div>
                    <div>
                      <div className="text-xs font-bold text-slate-800">{doc.name || doc.document_type}</div>
                      <div className="text-[11px] text-slate-500">{doc.description || 'Format berkas PDF maksimal 5MB'}</div>
                    </div>
                  </div>
                  <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${doc.is_mandatory !== false ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600'}`}>
                    {doc.is_mandatory !== false ? 'Wajib' : 'Opsional'}
                  </span>
                </div>
              ))}
            </div>
          )}
        </CardBody>
      </Card>
    </div>
  );
}

export default ProgramDetailPage;

