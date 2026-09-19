import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../../services/api';
import { formatCurrency, formatDate } from '../../utils/formatters';
import { openPdf } from '../../utils/pdf';
import { LogoPemprovSulut, LogoSikomando } from '../../components/ui/Logos';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import Alert from '../../components/feedback/Alert';
import {
  DocumentArrowDownIcon,
  PrinterIcon,
  ArrowLeftIcon,
  QrCodeIcon,
  ShieldCheckIcon,
} from '@heroicons/react/24/outline';

export function DecisionDetailPage() {
  const { id } = useParams();
  const [decision, setDecision] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [errorMsg, setErrorMsg] = useState('');

  useEffect(() => {
    let isMounted = true;
    async function loadDecision() {
      setIsLoading(true);
      try {
        const res = await api.get(`/decisions/${id}`);
        if (isMounted && res?.data) {
          setDecision(res.data);
        }
      } catch (err) {
        if (isMounted) setErrorMsg(err.message || 'Gagal memuat detail Surat Keputusan.');
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadDecision();
    return () => { isMounted = false; };
  }, [id]);

  if (isLoading) {
    return (
      <div className="py-20">
        <Spinner size="lg" label="Menyiapkan naskah Surat Keputusan resmi..." />
      </div>
    );
  }

  if (errorMsg || !decision) {
    return (
      <div className="max-w-2xl mx-auto py-16">
        <Alert type="danger" title="Surat Keputusan Tidak Ditemukan">
          <p className="mb-4">{errorMsg || 'Data SK tidak tersedia.'}</p>
          <Link to="/decisions">
            <Button variant="secondary" size="sm">Kembali ke Daftar SK</Button>
          </Link>
        </Alert>
      </div>
    );
  }

  const proposal = decision.proposal || {};

  return (
    <div className="max-w-4xl mx-auto space-y-6 pb-20">
      <PageHeader
        title={`Rincian Surat Keputusan: ${decision.decision_number || 'SK GUBERNUR'}`}
        subtitle={`Penetapan Penerima Bantuan Hibah Daerah Provinsi Sulawesi Utara • T.A. ${decision.fiscal_year || '2026'}`}
        breadcrumbs={[
          { label: 'Surat Keputusan (SK)', to: '/decisions' },
          { label: decision.decision_number || 'Naskah SK' },
        ]}
        action={
          <div className="flex items-center gap-2">
            <Link to="/decisions">
              <Button variant="secondary" size="sm" icon={ArrowLeftIcon}>
                Kembali
              </Button>
            </Link>
            <Button
              variant="primary"
              size="sm"
              icon={PrinterIcon}
              onClick={() => openPdf(`/pdf/decisions/${decision.id}`)}
            >
              Cetak Naskah Asli (PDF)
            </Button>
          </div>
        }
      />

      {/* Official Government Decree Preview Card */}
      <div className="bg-white rounded-3xl p-8 sm:p-12 shadow-xl border border-slate-200 text-slate-800 space-y-8">
        {/* Decree Formal Header (Kop Surat) */}
        <div className="text-center space-y-2 border-b-2 border-slate-900 pb-6">
          <div className="flex justify-center mb-2">
            <LogoPemprovSulut className="w-16 h-18" />
          </div>
          <h2 className="text-lg sm:text-xl font-black uppercase tracking-widest text-slate-950">
            GUBERNUR SULAWESI UTARA
          </h2>
          <div className="text-xs font-bold font-mono tracking-wider text-slate-700">
            KEPUTUSAN GUBERNUR SULAWESI UTARA<br />
            NOMOR: {decision.decision_number || '100/SK/KESRA/2026'}
          </div>
          <div className="text-xs font-semibold uppercase text-slate-600 pt-1">
            TENTANG<br />
            PENETAPAN PENERIMA BANTUAN HIBAH DAERAH PROVINSI SULAWESI UTARA TAHUN ANGGARAN {decision.fiscal_year || '2026'}
          </div>
        </div>

        {/* Decree Body Sections */}
        <div className="space-y-6 text-xs sm:text-sm leading-relaxed text-justify">
          <div>
            <h4 className="font-bold text-slate-900 mb-1">MENIMBANG:</h4>
            <p className="text-slate-700">
              {decision.considerations || 'Bahwa untuk menunjang pencapaian sasaran program pembangunan daerah, meningkatkan pelayanan dasar, serta memberdayakan peran serta lembaga kemasyarakatan di Provinsi Sulawesi Utara, dipandang perlu memberikan bantuan hibah kepada organisasi penerima yang telah memenuhi persyaratan.'}
            </p>
          </div>

          <div>
            <h4 className="font-bold text-slate-900 mb-1">MENGINGAT:</h4>
            <p className="text-slate-700">
              1. Undang-Undang Nomor 23 Tahun 2014 tentang Pemerintahan Daerah;<br />
              2. Peraturan Menteri Dalam Negeri Nomor 77 Tahun 2020 tentang Pedoman Teknis Pengelolaan Keuangan Daerah;<br />
              3. Peraturan Daerah Provinsi Sulawesi Utara tentang Anggaran Pendapatan dan Belanja Daerah (APBD).
            </p>
          </div>

          <div className="pt-2">
            <h4 className="font-bold text-slate-900 mb-2 text-center uppercase tracking-wider">
              MEMUTUSKAN:
            </h4>
            <div className="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
              <div>
                <strong>KESATU:</strong> Menetapkan organisasi berikut sebagai penerima bantuan hibah:
                <div className="mt-1.5 pl-4 border-l-2 border-blue-500 space-y-1">
                  <div>Nama Lembaga: <strong>{proposal.organization?.name || '-'}</strong></div>
                  <div>Ketua Pengurus: <strong>{proposal.applicant?.name || '-'}</strong></div>
                  <div>Alamat: {proposal.organization?.address || 'Sulawesi Utara'}</div>
                  <div>Judul Kegiatan: <em>&quot;{proposal.title}&quot;</em></div>
                </div>
              </div>

              <div className="pt-2 border-t border-slate-200 flex items-center justify-between">
                <span>Besaran Alokasi Hibah (KEDUA):</span>
                <span className="font-mono font-black text-emerald-800 text-base">
                  {formatCurrency(decision.approved_amount ?? decision.amount ?? proposal?.approved_amount ?? proposal?.requested_amount ?? 0)}
                </span>
              </div>
            </div>
          </div>
        </div>

        {/* Signature & QR Cryptographic Verification Stamp */}
        <div className="pt-8 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-6">
          {decision.qr?.svg_url ? (
            <div className="p-4 rounded-2xl bg-blue-50/80 border border-blue-200 flex items-center gap-4">
              <img
                src={decision.qr.svg_url}
                alt="QR Code SK"
                className="w-16 h-16 bg-white border border-slate-300 rounded-xl p-1 shadow-2xs shrink-0"
              />
              <div className="text-xs text-blue-950 space-y-1">
                <span className="font-bold block text-sm">QR Code Verifikasi Resmi</span>
                <span className="text-[11px] text-slate-600 block">Token Kriptografis Sah SIKOMANDO</span>
                <Link
                  to={`/tracking?code=${decision.qr.token}`}
                  target="_blank"
                  className="text-blue-700 hover:text-blue-900 font-bold underline inline-block text-[11px]"
                >
                  Periksa Keabsahan Dokumen &raquo;
                </Link>
              </div>
            </div>
          ) : (
            <div className="p-4 rounded-2xl bg-blue-50/70 border border-blue-200 flex items-center gap-3">
              <QrCodeIcon className="w-10 h-10 text-blue-700 shrink-0" />
              <div className="text-[11px] text-blue-900">
                <span className="font-bold block">Dokumen Resmi Ber-QR Kriptografis</span>
                <span>Pindai untuk memeriksa keabsahan pada portal verifikasi publik SIKOMANDO.</span>
              </div>
            </div>
          )}

          <div className="text-center sm:text-right space-y-1 text-xs">
            <span className="block text-slate-500">Ditetapkan di Manado, {formatDate(decision.decision_date || decision.created_at)}</span>
            <span className="font-bold text-slate-900 block">GUBERNUR SULAWESI UTARA</span>
            <div className="h-14 flex items-center justify-center sm:justify-end">
              <span className="text-[10px] font-bold px-3 py-1 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-300">
                Ditandatangani Secara Elektronik (TTE)
              </span>
            </div>
            <span className="font-bold text-slate-900 block underline">Drs. STEVEN KANDOUW, M.Si</span>
          </div>
        </div>
      </div>
    </div>
  );
}

export default DecisionDetailPage;

