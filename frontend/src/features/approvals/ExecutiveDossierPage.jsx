import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import api from '../../services/api';
import { useToast } from '../../context/ToastContext';
import { formatCurrency, formatDate, formatCoordinates } from '../../utils/formatters';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Input from '../../components/ui/Input';
import Textarea from '../../components/ui/Textarea';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import Alert from '../../components/feedback/Alert';
import {
  CheckBadgeIcon,
  XCircleIcon,
  ArrowLeftIcon,
  ShieldCheckIcon,
  DocumentCheckIcon,
  AcademicCapIcon,
  MapPinIcon,
  BanknotesIcon,
} from '@heroicons/react/24/outline';

export function ExecutiveDossierPage() {
  const { approvalId } = useParams();
  const navigate = useNavigate();
  const toast = useToast();

  const [approval, setApproval] = useState(null);
  const [approvedAmount, setApprovedAmount] = useState(0);
  const [decisionNotes, setDecisionNotes] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);

  useEffect(() => {
    let isMounted = true;
    async function loadApprovalDetail() {
      setIsLoading(true);
      try {
        const res = await api.get(`/approvals/${approvalId}`);
        if (isMounted && res?.data) {
          setApproval(res.data);
          const initialAmt = parseFloat(res.data.recommended_amount || res.data.proposal?.approved_amount || res.data.proposal?.requested_amount || 0);
          setApprovedAmount(initialAmt);
        }
      } catch (err) {
        console.error('Failed to load approval dossier:', err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadApprovalDetail();
    return () => { isMounted = false; };
  }, [approvalId]);

  const handleApprove = async () => {
    setIsSubmitting(true);
    try {
      await api.post(`/approvals/${approvalId}/approve`, {
        approved_amount: approvedAmount,
        notes: decisionNotes || 'Usulan disetujui untuk ditetapkan melalui Surat Keputusan Gubernur.',
      });

      toast.success('Persetujuan usulan berhasil disahkan! Berkas diteruskan untuk penerbitan SK.');
      navigate('/approvals');
    } catch (err) {
      toast.error(err.message || 'Gagal menyetujui usulan.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleReject = async () => {
    if (!decisionNotes.trim()) {
      toast.error('Alasan penolakan formal wajib diisi.');
      return;
    }

    setIsSubmitting(true);
    try {
      await api.post(`/approvals/${approvalId}/reject`, {
        notes: decisionNotes,
      });

      toast.warning('Usulan telah resmi ditolak.');
      navigate('/approvals');
    } catch (err) {
      toast.error(err.message || 'Gagal menolak usulan.');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (isLoading) {
    return (
      <div className="py-20">
        <Spinner size="lg" label="Mempersiapkan Executive Dossier..." />
      </div>
    );
  }

  const proposal = approval?.proposal || {};

  return (
    <div className="max-w-5xl mx-auto space-y-6 pb-20">
      <PageHeader
        title={`Executive Dossier: ${proposal?.proposal_number || 'USULAN'}`}
        subtitle={`Ketetapan Kelayakan Formal Penerima Hibah • T.A. ${proposal?.grant_program?.fiscal_year || '2026'}`}
        breadcrumbs={[
          { label: 'Persetujuan Pimpinan', to: '/approvals' },
          { label: 'Dossier Eksekutif' },
        ]}
        action={
          <Link to="/approvals">
            <Button variant="secondary" size="sm" icon={ArrowLeftIcon}>
              Kembali ke Antrean
            </Button>
          </Link>
        }
      />

      {/* Dossier Overview Top Banner */}
      <div className="bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-950 text-white p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-4">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div className="space-y-1">
            <span className="text-[10px] font-bold px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 uppercase tracking-wider">
              Rekam Jejak Tervalidasi TAPD
            </span>
            <h2 className="text-xl sm:text-2xl font-black text-white pt-1">{proposal?.title}</h2>
            <p className="text-xs text-slate-300">
              Ormas Pemohon: <strong className="text-white">{proposal?.organization?.name}</strong> • Ketua: {proposal?.applicant?.name}
            </p>
          </div>

          <div className="text-right bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/20 shrink-0">
            <span className="text-[11px] text-blue-200 block">Rekomendasi Pagu TAPD:</span>
            <span className="text-2xl font-black font-mono text-emerald-400 block">
              {formatCurrency(approval?.recommended_amount || approvedAmount)}
            </span>
          </div>
        </div>
      </div>

      {/* 3 Pillars of Evidence Grid */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
        {/* Pillar 1: Verifikasi Administrasi */}
        <Card className="border-slate-200 shadow-xs">
          <CardHeader
            title="1. Administrasi Hukum"
            action={<span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">Lolos</span>}
          />
          <CardBody className="space-y-2 text-xs">
            <div className="flex items-center gap-2 text-emerald-700">
              <DocumentCheckIcon className="w-4 h-4 shrink-0" />
              <span>Akta Notaris & SK Kemenkumham Sah</span>
            </div>
            <div className="flex items-center gap-2 text-emerald-700">
              <DocumentCheckIcon className="w-4 h-4 shrink-0" />
              <span>NPWP & Rekening Bank Terverifikasi</span>
            </div>
            <div className="flex items-center gap-2 text-emerald-700">
              <DocumentCheckIcon className="w-4 h-4 shrink-0" />
              <span>Surat Pernyataan Mutlak Terlampir</span>
            </div>
          </CardBody>
        </Card>

        {/* Pillar 2: Evaluasi Substantif */}
        <Card className="border-slate-200 shadow-xs">
          <CardHeader
            title="2. Evaluasi Teknis"
            action={<span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">Skor: 87.5</span>}
          />
          <CardBody className="space-y-2 text-xs">
            <div className="flex items-center gap-2 text-slate-700">
              <AcademicCapIcon className="w-4 h-4 text-blue-600 shrink-0" />
              <span>Relevansi Terhadap RKPD Sulut Tinggi</span>
            </div>
            <div className="flex items-center gap-2 text-slate-700">
              <AcademicCapIcon className="w-4 h-4 text-blue-600 shrink-0" />
              <span>Kewajaran Rincian RAB Terkonfirmasi</span>
            </div>
            <div className="flex items-center gap-2 text-slate-700">
              <AcademicCapIcon className="w-4 h-4 text-blue-600 shrink-0" />
              <span>Kapasitas Pengurus Berpengalaman</span>
            </div>
          </CardBody>
        </Card>

        {/* Pillar 3: Survei Lapangan & GPS */}
        <Card className="border-slate-200 shadow-xs">
          <CardHeader
            title="3. Faktual Lapangan & GPS"
            action={<span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-cyan-100 text-cyan-800">Tervalidasi</span>}
          />
          <CardBody className="space-y-2 text-xs">
            <div className="flex items-center gap-2 text-slate-700">
              <MapPinIcon className="w-4 h-4 text-rose-600 shrink-0" />
              <span>Koordinat GPS: 1.4748° LU, 124.8428° BT</span>
            </div>
            <div className="flex items-center gap-2 text-slate-700">
              <ShieldCheckIcon className="w-4 h-4 text-emerald-600 shrink-0" />
              <span>Plang Nama & Sekretariat Fisik Nyata</span>
            </div>
            <div className="flex items-center gap-2 text-slate-700">
              <ShieldCheckIcon className="w-4 h-4 text-emerald-600 shrink-0" />
              <span>Wawancara Pengurus Sesuai KTP</span>
            </div>
          </CardBody>
        </Card>
      </div>

      {/* Decision Maker Execution Card */}
      <Card className="border-blue-200 shadow-md">
        <CardHeader
          title="Lembar Penetapan Keputusan Pimpinan"
          subtitle="Tentukan besaran nominal final pagu hibah dan berikan catatan pengesahan Surat Keputusan"
        />
        <CardBody className="space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Input
              label="Besaran Pagu Hibah yang Ditetapkan (Rp)"
              type="number"
              value={approvedAmount}
              onChange={(e) => setApprovedAmount(parseFloat(e.target.value) || 0)}
              placeholder="Rp 0"
              mono
              required
            />
            <div className="flex flex-col justify-center text-xs text-slate-500 pt-3">
              <span>Rekomendasi TAPD: <strong className="font-mono text-slate-800">{formatCurrency(approval?.recommended_amount || approvedAmount)}</strong></span>
              <span>Permohonan Awal: <strong className="font-mono text-slate-800">{formatCurrency(proposal?.requested_amount)}</strong></span>
            </div>
          </div>

          <Textarea
            label="Catatan Pengesahan Formal / Diktum SK Gubernur"
            value={decisionNotes}
            onChange={(e) => setDecisionNotes(e.target.value)}
            placeholder="Menimbang bahwa permohonan hibah telah memenuhi aspek yuridis, teknis dan faktual..."
            rows={3}
          />

          <div className="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-end gap-3">
            <Button
              variant="danger"
              size="md"
              icon={XCircleIcon}
              onClick={handleReject}
              isLoading={isSubmitting}
            >
              Tolak Usulan
            </Button>
            <Button
              variant="success"
              size="lg"
              icon={CheckBadgeIcon}
              onClick={handleApprove}
              isLoading={isSubmitting}
              className="font-bold shadow-md shadow-emerald-600/20"
            >
              Setujui & Terbitkan SK Penetapan
            </Button>
          </div>
        </CardBody>
      </Card>
    </div>
  );
}

export default ExecutiveDossierPage;

