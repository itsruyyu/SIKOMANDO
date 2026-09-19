import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../../services/api';
import { useToast } from '../../context/ToastContext';
import { formatCurrency, formatDate, formatDateTime } from '../../utils/formatters';
import { openPdf } from '../../utils/pdf';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Input from '../../components/ui/Input';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import Alert from '../../components/feedback/Alert';
import {
  BanknotesIcon,
  PrinterIcon,
  ArrowLeftIcon,
  CheckCircleIcon,
  BuildingLibraryIcon,
} from '@heroicons/react/24/outline';

export function DisbursementDetailPage() {
  const { id } = useParams();
  const toast = useToast();

  const [disbursement, setDisbursement] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isRecording, setIsRecording] = useState(false);
  const [referenceNumber, setReferenceNumber] = useState('');
  const [paidAmount, setPaidAmount] = useState(0);

  const loadDisbursementDetail = async () => {
    setIsLoading(true);
    try {
      const res = await api.get(`/disbursements/${id}`);
      if (res?.data) {
        setDisbursement(res.data);
        setPaidAmount(parseFloat(res.data.amount) || 0);
      }
    } catch (err) {
      console.error('Failed to load disbursement detail:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadDisbursementDetail();
  }, [id]);

  const handleRecordTransaction = async (e) => {
    e.preventDefault();
    if (!referenceNumber.trim()) {
      toast.error('Nomor referensi / SP2D wajib diisi.');
      return;
    }

    setIsRecording(true);
    try {
      await api.post(`/disbursements/${id}/transactions`, {
        reference_number: referenceNumber,
        amount: paidAmount,
        transaction_date: new Date().toISOString().split('T')[0],
        notes: 'Penyaluran via Surat Perintah Pencairan Dana (SP2D).',
      });

      toast.success('Pencatatan transaksi perbankan SP2D berhasil disimpan!');
      loadDisbursementDetail();
    } catch (err) {
      toast.error(err.message || 'Gagal mencatat transaksi.');
    } finally {
      setIsRecording(false);
    }
  };

  if (isLoading) {
    return (
      <div className="py-20">
        <Spinner size="lg" label="Memuat rincian pencairan dana..." />
      </div>
    );
  }

  if (!disbursement) {
    return (
      <div className="max-w-2xl mx-auto py-16">
        <Alert type="danger" title="Data Tidak Ditemukan">
          <p className="mb-4">Informasi pencairan dana tidak tersedia.</p>
          <Link to="/disbursements">
            <Button variant="secondary" size="sm">Kembali</Button>
          </Link>
        </Alert>
      </div>
    );
  }

  const proposal = disbursement.proposal || {};

  return (
    <div className="max-w-4xl mx-auto space-y-6 pb-20">
      <PageHeader
        title={`Rincian Penyaluran Dana: ${disbursement.disbursement_number || 'SP2D'}`}
        subtitle={`Lembaga Penerima: ${proposal.organization?.name || '-'} • Termin #${disbursement.stage || 1}`}
        breadcrumbs={[
          { label: 'Pencairan Dana', to: '/disbursements' },
          { label: 'Rincian Penyaluran' },
        ]}
        action={
          <div className="flex items-center gap-2">
            <Link to="/disbursements">
              <Button variant="secondary" size="sm" icon={ArrowLeftIcon}>
                Kembali
              </Button>
            </Link>
            <Button
              variant="outline"
              size="sm"
              icon={PrinterIcon}
              onClick={() => openPdf(`/pdf/disbursements/${disbursement.id}`)}
            >
              Cetak Bukti SP2D
            </Button>
          </div>
        }
      />

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {/* Info Left Box (2 cols) */}
        <div className="md:col-span-2 space-y-6">
          <Card className="border-slate-200">
            <CardHeader title="Informasi Rekening Bank Tujuan Transfer" subtitle="Rekening resmi terverifikasi" />
            <CardBody className="space-y-4 text-xs">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <span className="text-slate-400 block font-medium">Bank Penyalur:</span>
                  <span className="font-bold text-slate-800 text-sm">{proposal.organization?.bank_name || 'Bank SulutGo (BSG)'}</span>
                </div>
                <div>
                  <span className="text-slate-400 block font-medium">Nomor Rekening:</span>
                  <span className="font-mono font-black text-blue-900 text-sm">{proposal.organization?.bank_account_number || '001020304050'}</span>
                </div>
              </div>
              <div className="pt-2 border-t border-slate-100">
                <span className="text-slate-400 block font-medium">Atas Nama Rekening:</span>
                <span className="font-bold text-slate-800">{proposal.organization?.name || '-'}</span>
              </div>
            </CardBody>
          </Card>

          {/* Record Transaction Form */}
          {disbursement.status !== 'paid' && (
            <Card className="border-emerald-200 shadow-sm">
              <CardHeader
                title="Pencatatan Nomor Referensi Bank / SP2D"
                subtitle="Isikan bukti pencairan dana transfer setelah proses perbankan sukses"
              />
              <CardBody>
                <form onSubmit={handleRecordTransaction} className="space-y-4">
                  <Input
                    label="Nomor SP2D / Ref Transfer Bank"
                    value={referenceNumber}
                    onChange={(e) => setReferenceNumber(e.target.value)}
                    placeholder="SP2D/2026/09/001"
                    mono
                    required
                  />
                  <Input
                    label="Nominal Dana yang Disalurkan (Rp)"
                    type="number"
                    value={paidAmount}
                    onChange={(e) => setPaidAmount(parseFloat(e.target.value) || 0)}
                    mono
                    required
                  />
                  <Button
                    type="submit"
                    variant="success"
                    size="md"
                    className="w-full font-bold"
                    isLoading={isRecording}
                    icon={CheckCircleIcon}
                  >
                    Konfirmasi Penyaluran Dana Selesai
                  </Button>
                </form>
              </CardBody>
            </Card>
          )}
        </div>

        {/* Right Status Card */}
        <div className="space-y-6">
          <Card className="border-emerald-200 bg-emerald-50/50 text-center">
            <CardBody className="p-6 space-y-2">
              <span className="text-xs font-bold uppercase tracking-wider text-emerald-800">
                Jumlah Penyaluran
              </span>
              <div className="text-3xl font-black font-mono text-emerald-950">
                {formatCurrency(disbursement.amount || paidAmount)}
              </div>
              <div className="pt-2">
                <span className={`text-xs font-bold px-3 py-1 rounded-full ${disbursement.status === 'paid' ? 'bg-emerald-200 text-emerald-900' : 'bg-amber-100 text-amber-900'}`}>
                  {disbursement.status === 'paid' ? 'Telah Disalurkan' : 'Menunggu Eksekusi SP2D'}
                </span>
              </div>
            </CardBody>
          </Card>
        </div>
      </div>
    </div>
  );
}

export default DisbursementDetailPage;

