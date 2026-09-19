import React, { useState, useEffect } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import api from '../../services/api';
import { formatCurrency, formatDate, getStatusBadge } from '../../utils/formatters';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Input from '../../components/ui/Input';
import Button from '../../components/ui/Button';
import Badge from '../../components/ui/Badge';
import Spinner from '../../components/feedback/Spinner';
import Alert from '../../components/feedback/Alert';
import {
  MagnifyingGlassIcon,
  ShieldCheckIcon,
  ClockIcon,
  CheckCircleIcon,
  DocumentTextIcon,
  ArrowTopRightOnSquareIcon,
  SparklesIcon,
} from '@heroicons/react/24/outline';

export function TrackerPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const initialCode = searchParams.get('code') || '';

  const [inputCode, setInputCode] = useState(initialCode);
  const [isLoading, setIsLoading] = useState(false);
  const [result, setResult] = useState(null);
  const [errorMsg, setErrorMsg] = useState('');

  const executeTrack = async (codeToSearch) => {
    if (!codeToSearch.trim()) return;
    setIsLoading(true);
    setErrorMsg('');
    setResult(null);

    try {
      // First attempt: If it looks like a QR token or hex, check verify endpoint
      // Second attempt: Search transparency or proposals endpoint
      let found = null;

      try {
        const verifyRes = await api.get(`/public/verify/${encodeURIComponent(codeToSearch.trim())}`);
        if (verifyRes?.data) {
          found = {
            type: 'qr',
            ...verifyRes.data,
          };
        }
      } catch {
        // Not a QR token, continue to transparency check
      }

      if (!found) {
        // Search transparency list
        const transRes = await api.get('/public/transparency');
        const items = transRes?.data || [];
        const match = items.find(
          (it) =>
            it.proposal_number?.toLowerCase() === codeToSearch.trim().toLowerCase() ||
            it.organization_name?.toLowerCase().includes(codeToSearch.trim().toLowerCase()) ||
            it.title?.toLowerCase().includes(codeToSearch.trim().toLowerCase())
        );

        if (match) {
          found = {
            type: 'proposal',
            ...match,
          };
        }
      }

      if (found) {
        setResult(found);
      } else {
        setErrorMsg(`Tidak ditemukan usulan atau dokumen dengan kode "${codeToSearch}". Pastikan nomor usulan benar atau berkas telah selesai diverifikasi.`);
      }
    } catch (err) {
      setErrorMsg(err.message || 'Gagal mencari status usulan.');
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    if (initialCode) {
      executeTrack(initialCode);
    }
  }, [initialCode]);

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!inputCode.trim()) return;
    setSearchParams({ code: inputCode.trim() });
    executeTrack(inputCode.trim());
  };

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">
      {/* Header Banner */}
      <div className="text-center space-y-3">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold border border-blue-200">
          <ClockIcon className="w-4 h-4 text-blue-600" />
          <span>Pelacakan Status Usulan Terpadu</span>
        </div>
        <h1 className="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight">
          Lacak Proses Hibah Anda
        </h1>
        <p className="text-xs sm:text-sm text-slate-500 max-w-xl mx-auto">
          Ketahui tahapan verifikasi berkas, evaluasi teknis, penetapan SK, hingga pencairan dana SP2D secara langsung dan transparan.
        </p>
      </div>

      {/* Tracker Search Box */}
      <Card className="border-slate-200 shadow-md">
        <CardBody className="p-6">
          <form onSubmit={handleSubmit} className="flex flex-col sm:flex-row gap-3">
            <div className="flex-1">
              <Input
                name="code"
                value={inputCode}
                onChange={(e) => setInputCode(e.target.value)}
                placeholder="Ketik nomor usulan (contoh: PROP-SULUT/2026/01/0013) atau token QR..."
                icon={MagnifyingGlassIcon}
                required
              />
            </div>
            <Button
              type="submit"
              variant="primary"
              size="md"
              isLoading={isLoading}
              className="sm:self-end h-10 px-6 font-bold"
            >
              Cari Berkas
            </Button>
          </form>

          <div className="mt-3 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
            <span>Contoh nomor usulan demo:</span>
            <button
              type="button"
              onClick={() => {
                setInputCode('PROP-SULUT/2026/01/0013');
                executeTrack('PROP-SULUT/2026/01/0013');
              }}
              className="font-mono bg-slate-100 hover:bg-slate-200 px-2 py-0.5 rounded text-blue-600 font-semibold cursor-pointer transition"
            >
              PROP-SULUT/2026/01/0013
            </button>
            <button
              type="button"
              onClick={() => {
                setInputCode('PROP-SULUT/2025/01/0014');
                executeTrack('PROP-SULUT/2025/01/0014');
              }}
              className="font-mono bg-slate-100 hover:bg-slate-200 px-2 py-0.5 rounded text-blue-600 font-semibold cursor-pointer transition"
            >
              PROP-SULUT/2025/01/0014
            </button>
          </div>
        </CardBody>
      </Card>

      {/* Loading */}
      {isLoading && <Spinner label="Memeriksa status riil pada server..." />}

      {/* Error / Not Found */}
      {errorMsg && !isLoading && (
        <Alert type="warning" title="Informasi Pelacakan">
          {errorMsg}
        </Alert>
      )}

      {/* Search Result Display */}
      {result && !isLoading && (
        <Card className="border-blue-200 shadow-xl overflow-hidden animate-in fade-in duration-200">
          <CardHeader
            title="Hasil Pelacakan Berkas"
            subtitle={`Nomor Registrasi / Identitas: ${result.proposal_number || result.token || 'Terverifikasi'}`}
            action={
              <Badge status={result.status || 'verified'}>
                {result.status_label || result.status || 'Terverifikasi'}
              </Badge>
            }
          />
          <CardBody className="p-6 space-y-6">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
              <div className="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <span className="text-slate-400 block mb-1 font-medium">Judul Usulan / Kegiatan</span>
                <span className="font-bold text-slate-800 text-sm">{result.title || result.subject || '-'}</span>
              </div>
              <div className="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <span className="text-slate-400 block mb-1 font-medium">Organisasi Pemohon</span>
                <span className="font-bold text-slate-800 text-sm">{result.organization_name || result.entity_name || '-'}</span>
              </div>
              <div className="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <span className="text-slate-400 block mb-1 font-medium">Program Hibah Terkait</span>
                <span className="font-bold text-slate-800">{result.grant_program_name || 'Program Hibah Daerah'}</span>
              </div>
              <div className="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <span className="text-slate-400 block mb-1 font-medium">Nominal Disetujui (SK)</span>
                <span className="font-mono font-bold text-emerald-700 text-sm">
                  {formatCurrency(result.approved_amount || result.amount || 0)}
                </span>
              </div>
            </div>

            {/* Stages Progress Visualizer */}
            <div className="p-5 rounded-2xl bg-blue-50/60 border border-blue-100 space-y-3">
              <h4 className="text-xs font-bold text-blue-900 uppercase tracking-wider">
                Indikator Tahapan Usulan
              </h4>
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs">
                <div className="p-2.5 rounded-xl bg-white border border-blue-200 shadow-2xs">
                  <CheckCircleIcon className="w-5 h-5 text-emerald-600 mx-auto mb-1" />
                  <span className="font-bold text-slate-800 block">1. Pengajuan</span>
                  <span className="text-[10px] text-emerald-600 font-medium">Lengkap</span>
                </div>
                <div className="p-2.5 rounded-xl bg-white border border-blue-200 shadow-2xs">
                  <CheckCircleIcon className="w-5 h-5 text-emerald-600 mx-auto mb-1" />
                  <span className="font-bold text-slate-800 block">2. Verifikasi</span>
                  <span className="text-[10px] text-emerald-600 font-medium">Lolos Berkas</span>
                </div>
                <div className="p-2.5 rounded-xl bg-white border border-blue-200 shadow-2xs">
                  <CheckCircleIcon className="w-5 h-5 text-emerald-600 mx-auto mb-1" />
                  <span className="font-bold text-slate-800 block">3. Evaluasi & SK</span>
                  <span className="text-[10px] text-emerald-600 font-medium">Ditetapkan</span>
                </div>
                <div className="p-2.5 rounded-xl bg-white border border-blue-200 shadow-2xs">
                  <CheckCircleIcon className="w-5 h-5 text-emerald-600 mx-auto mb-1" />
                  <span className="font-bold text-slate-800 block">4. SP2D & LPJ</span>
                  <span className="text-[10px] text-emerald-600 font-medium">Dalam Proses</span>
                </div>
              </div>
            </div>

            {/* Applicant Login Advice */}
            <div className="flex items-center justify-between pt-2">
              <span className="text-xs text-slate-500">
                Memerlukan rincian RAB lengkap atau ingin mengunggah revisi dokumen?
              </span>
              <Link to="/login">
                <Button variant="primary" size="xs" icon={ArrowTopRightOnSquareIcon} iconPosition="right">
                  Masuk Akun Pemohon
                </Button>
              </Link>
            </div>
          </CardBody>
        </Card>
      )}
    </div>
  );
}

export default TrackerPage;

