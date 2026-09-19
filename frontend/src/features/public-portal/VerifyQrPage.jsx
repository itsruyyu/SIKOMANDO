import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import api from '../../services/api';
import { formatDate, formatDateTime } from '../../utils/formatters';
import { LogoPemprovSulut, LogoSikomando } from '../../components/ui/Logos';
import Card, { CardBody } from '../../components/ui/Card';
import Input from '../../components/ui/Input';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import Alert from '../../components/feedback/Alert';
import {
  QrCodeIcon,
  ShieldCheckIcon,
  ShieldExclamationIcon,
  CheckCircleIcon,
  DocumentCheckIcon,
  BuildingLibraryIcon,
  CalendarDaysIcon,
} from '@heroicons/react/24/outline';

export function VerifyQrPage() {
  const { token: urlToken } = useParams();
  const navigate = useNavigate();

  const [inputToken, setInputToken] = useState(urlToken || '');
  const [verificationResult, setVerificationResult] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  const verifyToken = async (tok) => {
    if (!tok.trim()) return;
    setIsLoading(true);
    setErrorMsg('');
    setVerificationResult(null);

    try {
      const res = await api.get(`/public/verify/${encodeURIComponent(tok.trim())}`);
      if (res?.data) {
        setVerificationResult(res.data);
      } else {
        throw new Error('Data verifikasi QR tidak ditemukan.');
      }
    } catch (err) {
      setErrorMsg(err.message || 'Token QR tidak valid, kadaluarsa, atau telah dicabut.');
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    if (urlToken) {
      setInputToken(urlToken);
      verifyToken(urlToken);
    }
  }, [urlToken]);

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!inputToken.trim()) return;
    navigate(`/verify/${encodeURIComponent(inputToken.trim())}`);
  };

  return (
    <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">
      {/* Header Banner */}
      <div className="text-center space-y-3">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 text-xs font-semibold border border-emerald-200">
          <ShieldCheckIcon className="w-4 h-4 text-emerald-600" />
          <span>Layanan Kriptografi Verifikasi Dokumen Publik</span>
        </div>
        <h1 className="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight">
          Verifikasi Keaslian Dokumen SIKOMANDO
        </h1>
        <p className="text-xs sm:text-sm text-slate-500 max-w-xl mx-auto">
          Pastikan keabsahan Surat Keputusan (SK) Gubernur, Berita Acara Serah Terima (BAST), Kuitansi Hibah, dan Dokumen Pengawasan resmi Pemerintah Provinsi Sulawesi Utara.
        </p>
      </div>

      {/* Verification Input Box */}
      <Card className="border-slate-200 shadow-md">
        <CardBody className="p-6">
          <form onSubmit={handleSubmit} className="flex flex-col sm:flex-row gap-3">
            <div className="flex-1">
              <Input
                name="token"
                value={inputToken}
                onChange={(e) => setInputToken(e.target.value)}
                placeholder="Tempel atau ketik token QR dokumen (contoh: 32 karakter hex/uuid)..."
                icon={QrCodeIcon}
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
              Verifikasi Token
            </Button>
          </form>
        </CardBody>
      </Card>

      {/* Loading */}
      {isLoading && <Spinner label="Memverifikasi tanda tangan digital & identitas dokumen..." />}

      {/* Error / Invalid Token */}
      {errorMsg && !isLoading && (
        <Alert type="danger" title="Verifikasi Gagal">
          <div className="flex items-start gap-2">
            <ShieldExclamationIcon className="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
            <div>
              <p>{errorMsg}</p>
              <p className="text-xs mt-2 text-rose-700">
                Peringatan: Jika dokumen fisik memuat QR Code yang tidak lolos verifikasi, mohon waspada terhadap indikasi pemalsuan dokumen bantuan hibah.
              </p>
            </div>
          </div>
        </Alert>
      )}

      {/* Success Verification Card */}
      {verificationResult && !isLoading && (
        <div className="bg-white rounded-3xl border-2 border-emerald-500 shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200">
          {/* Certificate Header Banner */}
          <div className="bg-gradient-to-r from-emerald-800 to-teal-900 text-white p-6 sm:p-8 flex items-center justify-between gap-4">
            <div className="flex items-center gap-4">
              <div className="p-3 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20">
                <CheckCircleIcon className="w-8 h-8 text-emerald-300" />
              </div>
              <div>
                <span className="text-[11px] font-bold uppercase tracking-wider text-emerald-200 block">
                  Dokumen Resmi Terverifikasi
                </span>
                <h3 className="text-lg sm:text-xl font-black">
                  Tanda Tangan Elektronik Asli & Sah
                </h3>
              </div>
            </div>
            <div className="hidden sm:block">
              <LogoPemprovSulut className="w-12 h-14" />
            </div>
          </div>

          {/* Body Information */}
          <div className="p-6 sm:p-8 space-y-6">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
              <div className="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <span className="text-slate-400 block mb-1">Jenis Dokumen</span>
                <span className="font-bold text-slate-800 text-sm capitalize">
                  {verificationResult.entity_type || verificationResult.document_type || 'Dokumen Resmi Hibah'}
                </span>
              </div>

              <div className="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <span className="text-slate-400 block mb-1">Status Keabsahan</span>
                <span className="font-bold text-emerald-700 text-sm flex items-center gap-1">
                  <span className="w-2 h-2 rounded-full bg-emerald-500" />
                  {verificationResult.status || 'AKTIF & TERDAFTAR'}
                </span>
              </div>

              <div className="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <span className="text-slate-400 block mb-1">Nomor / Identitas Dokumen</span>
                <span className="font-mono font-bold text-slate-900 text-sm">
                  {verificationResult.document_number || verificationResult.token?.substring(0, 16) || '-'}
                </span>
              </div>

              <div className="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <span className="text-slate-400 block mb-1">Waktu Penerbitan</span>
                <span className="font-semibold text-slate-800 text-sm">
                  {formatDateTime(verificationResult.issued_at || verificationResult.created_at)}
                </span>
              </div>
            </div>

            {verificationResult.details && (
              <div className="p-4 rounded-xl bg-blue-50/50 border border-blue-100 text-xs space-y-2">
                <span className="font-bold text-blue-900 block">Keterangan Subjek Dokumen:</span>
                <p className="text-slate-700">{verificationResult.details}</p>
              </div>
            )}

            {/* Official Seal Footer */}
            <div className="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-[11px] text-slate-500">
              <div className="flex items-center gap-2">
                <LogoSikomando className="w-5 h-5" />
                <span>Pemerintah Provinsi Sulawesi Utara • Biro Kesejahteraan Rakyat</span>
              </div>
              <div className="text-slate-400 font-mono text-[10px]">
                Validasi hash SHA-256 tersertifikasi
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default VerifyQrPage;

