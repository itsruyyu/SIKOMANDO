import { useState } from 'react';
import api from '../../services/api';
import Card from '../../components/ui/Card';
import Badge from '../../components/ui/Badge';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import Modal from '../../components/ui/Modal';
import Textarea from '../../components/ui/Textarea';
import Table from '../../components/ui/Table';
import Spinner from '../../components/feedback/Spinner';
import Alert from '../../components/feedback/Alert';
import EmptyState from '../../components/feedback/EmptyState';
import { useToast } from '../../context/ToastContext';
import { formatDateIndo } from '../../utils/formatters';
import {
  QrCodeIcon,
  MagnifyingGlassIcon,
  ShieldCheckIcon,
  ExclamationTriangleIcon,
  ArrowPathIcon,
  NoSymbolIcon,
  ClockIcon,
  LinkIcon,
  ServerIcon
} from '@heroicons/react/24/outline';

export default function QrManagementPage() {
  const { toast } = useToast();
  const [tokenInput, setTokenInput] = useState('');
  const [loading, setLoading] = useState(false);
  const [resolvedData, setResolvedData] = useState(null);
  const [errorMsg, setErrorMsg] = useState('');

  // Logs state
  const [logs, setLogs] = useState([]);
  const [loadingLogs, setLoadingLogs] = useState(false);

  // Revoke / Regenerate Modals
  const [showRevokeModal, setShowRevokeModal] = useState(false);
  const [showRegenModal, setShowRegenModal] = useState(false);
  const [actionReason, setActionReason] = useState('');
  const [actionLoading, setActionLoading] = useState(false);

  const handleResolve = async (e) => {
    if (e) e.preventDefault();
    const cleanToken = tokenInput.trim();
    if (!cleanToken) {
      setErrorMsg('Harap masukkan token atau string payload QR.');
      return;
    }

    setLoading(true);
    setErrorMsg('');
    setResolvedData(null);
    setLogs([]);

    try {
      const res = await api.get(`/qr/resolve/${encodeURIComponent(cleanToken)}`);
      const payload = res.data.data || res.data;
      setResolvedData(payload);

      // If QR identity ID exists, load verification logs
      const qrId = payload.qr_identity?.id || payload.id;
      if (qrId) {
        fetchLogs(qrId);
      }
    } catch (err) {
      setErrorMsg(err.response?.data?.message || 'Token QR tidak ditemukan atau tidak valid.');
    } finally {
      setLoading(false);
    }
  };

  const fetchLogs = async (qrId) => {
    setLoadingLogs(true);
    try {
      const res = await api.get(`/qr/${qrId}/logs`);
      setLogs(res.data.data?.data || res.data.data || []);
    } catch (err) {
      console.error('Failed to fetch QR logs', err);
    } finally {
      setLoadingLogs(false);
    }
  };

  const handleRevoke = async (e) => {
    e.preventDefault();
    const qrId = resolvedData.qr_identity?.id || resolvedData.id;
    if (!qrId) return;

    setActionLoading(true);
    try {
      await api.post(`/qr/${qrId}/revoke`, { reason: actionReason });
      toast.success('QR Code berhasil dicabut (REVOKED). Token ini tidak lagi valid!');
      setShowRevokeModal(false);
      setActionReason('');
      // Re-resolve
      handleResolve();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Gagal mencabut QR');
    } finally {
      setActionLoading(false);
    }
  };

  const handleRegenerate = async (e) => {
    e.preventDefault();
    const qrId = resolvedData.qr_identity?.id || resolvedData.id;
    if (!qrId) return;

    setActionLoading(true);
    try {
      const res = await api.post(`/qr/${qrId}/regenerate`, { reason: actionReason });
      toast.success('QR Code baru berhasil diterbitkan!');
      setShowRegenModal(false);
      setActionReason('');
      
      const newQr = res.data.data || {};
      if (newQr.token) {
        setTokenInput(newQr.token);
      }
      handleResolve();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Gagal menerbitkan ulang QR');
    } finally {
      setActionLoading(false);
    }
  };

  const qrIdentity = resolvedData?.qr_identity || resolvedData;
  const targetEntity = resolvedData?.entity || resolvedData?.target;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
          <QrCodeIcon className="w-7 h-7 text-gov-navy" />
          Pusat Manajemen QR Code & Integritas Dokumen
        </h1>
        <p className="text-sm text-slate-500 mt-1">
          Penyelesaian token kriptografi, penelusuran identitas berkas, pencabutan token (revocation), dan riwayat pemindaian.
        </p>
      </div>

      {/* Lookup Card */}
      <Card>
        <form onSubmit={handleResolve} className="space-y-3">
          <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
            Resolusi Token / Verifikasi Internal
          </label>
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <MagnifyingGlassIcon className="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                value={tokenInput}
                onChange={(e) => setTokenInput(e.target.value)}
                placeholder="Tempel Token QR / Hash unik dokumen (contoh: 64 karakter hex atau uuid)..."
                className="w-full pl-11 pr-4 py-2.5 text-sm font-mono bg-slate-50 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gov-navy/20 focus:border-gov-navy"
              />
            </div>
            <Button
              type="submit"
              loading={loading}
              leftIcon={<MagnifyingGlassIcon className="w-4 h-4" />}
            >
              Resolusi Token
            </Button>
          </div>
          <p className="text-xs text-slate-400">
            Token QR dibuat otomatis untuk Proposal, Keputusan Gubernur (SK), SP2D, Bukti Pengeluaran (Kwitansi), dan Berita Acara (BAST).
          </p>
        </form>

        {errorMsg && (
          <div className="mt-4">
            <Alert variant="danger">{errorMsg}</Alert>
          </div>
        )}
      </Card>

      {/* Resolved Result */}
      {loading ? (
        <div className="flex justify-center p-12">
          <Spinner size="lg" />
        </div>
      ) : resolvedData ? (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Main Info */}
          <div className="lg:col-span-2 space-y-6">
            <Card>
              <div className="flex items-start justify-between border-b border-slate-100 pb-4">
                <div className="flex items-center gap-3">
                  <div className="p-3 bg-gov-navy/10 text-gov-navy rounded-xl">
                    <QrCodeIcon className="w-8 h-8" />
                  </div>
                  <div>
                    <h3 className="font-bold text-slate-900 text-lg">
                      {qrIdentity?.entity_type?.replace(/_/g, ' ') || 'IDENTITAS DOKUMEN RESMI'}
                    </h3>
                    <p className="text-xs font-mono text-slate-500 break-all">
                      ID: {qrIdentity?.id || '-'}
                    </p>
                  </div>
                </div>
                <div>
                  {qrIdentity?.is_revoked || qrIdentity?.status === 'REVOKED' ? (
                    <Badge variant="danger" size="lg">DICABUT (REVOKED)</Badge>
                  ) : qrIdentity?.status === 'SUPERSEDED' ? (
                    <Badge variant="warning" size="lg">DIGANTIKAN (SUPERSEDED)</Badge>
                  ) : (
                    <Badge variant="success" size="lg">AKTIF / VALID</Badge>
                  )}
                </div>
              </div>

              {/* Attributes Grid */}
              <div className="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div className="p-3.5 bg-slate-50 rounded-lg">
                  <span className="text-slate-400 block mb-0.5">Token Kriptografi</span>
                  <span className="font-mono font-semibold text-slate-900 break-all select-all">
                    {qrIdentity?.token || tokenInput}
                  </span>
                </div>
                <div className="p-3.5 bg-slate-50 rounded-lg">
                  <span className="text-slate-400 block mb-0.5">Tipe Entitas Terkait</span>
                  <span className="font-semibold text-slate-800 uppercase">
                    {qrIdentity?.entity_type || '-'}
                  </span>
                </div>
                <div className="p-3.5 bg-slate-50 rounded-lg">
                  <span className="text-slate-400 block mb-0.5">Tanggal Terbit</span>
                  <span className="font-semibold text-slate-800">
                    {formatDateIndo(qrIdentity?.created_at || qrIdentity?.issued_at)}
                  </span>
                </div>
                <div className="p-3.5 bg-slate-50 rounded-lg">
                  <span className="text-slate-400 block mb-0.5">Masa Berlaku</span>
                  <span className="font-semibold text-slate-800">
                    {qrIdentity?.expires_at ? formatDateIndo(qrIdentity.expires_at) : 'Permanen / Tidak Kedaluwarsa'}
                  </span>
                </div>
              </div>

              {/* Revocation Reason if any */}
              {(qrIdentity?.is_revoked || qrIdentity?.status === 'REVOKED') && (
                <div className="mt-4 p-4 rounded-lg bg-red-50 border border-red-200">
                  <div className="flex items-center gap-2 text-red-700 font-bold text-xs">
                    <ExclamationTriangleIcon className="w-4 h-4" />
                    Alasan Pencabutan:
                  </div>
                  <p className="text-xs text-red-600 mt-1">
                    {qrIdentity?.revocation_reason || qrIdentity?.reason || 'Dokumen telah dicabut secara administratif.'}
                  </p>
                </div>
              )}

              {/* Linked Entity Info */}
              {targetEntity && (
                <div className="mt-6 pt-4 border-t border-slate-100">
                  <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center gap-1.5">
                    <LinkIcon className="w-4 h-4" /> Data Entitas Terkait
                  </h4>
                  <div className="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2 text-xs">
                    <div className="flex justify-between">
                      <span className="text-slate-500">Nomor / Kode:</span>
                      <span className="font-bold text-slate-900 font-mono">
                        {targetEntity.nomor_proposal || targetEntity.code || targetEntity.nomor_sk || targetEntity.nomor_sp2d || targetEntity.id}
                      </span>
                    </div>
                    {targetEntity.judul && (
                      <div className="flex justify-between">
                        <span className="text-slate-500">Judul Kegiatan:</span>
                        <span className="font-medium text-slate-800 text-right max-w-xs">{targetEntity.judul}</span>
                      </div>
                    )}
                    {targetEntity.status && (
                      <div className="flex justify-between">
                        <span className="text-slate-500">Status Entitas:</span>
                        <span className="font-semibold text-slate-800">{targetEntity.status}</span>
                      </div>
                    )}
                  </div>
                </div>
              )}
            </Card>

            {/* Scan / Verification Logs */}
            <Card>
              <div className="flex items-center justify-between mb-4">
                <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2">
                  <ClockIcon className="w-4 h-4 text-slate-500" />
                  Riwayat Pemindaian & Audit Log
                </h3>
                <span className="text-xs text-slate-400">
                  {logs.length} kali diverifikasi
                </span>
              </div>

              {loadingLogs ? (
                <div className="flex justify-center p-6">
                  <Spinner />
                </div>
              ) : logs.length === 0 ? (
                <EmptyState
                  title="Belum Ada Riwayat Pemindaian"
                  description="QR Code ini belum pernah diverifikasi melalui kanal publik ataupun internal."
                />
              ) : (
                <div className="overflow-x-auto">
                  <Table>
                    <thead>
                      <tr className="border-b border-slate-200 text-left text-xs font-semibold text-slate-500">
                        <th className="pb-3 px-3">Waktu (WITA)</th>
                        <th className="pb-3 px-3">Hasil</th>
                        <th className="pb-3 px-3">IP Address</th>
                        <th className="pb-3 px-3">Pemindai</th>
                        <th className="pb-3 px-3">User Agent</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 text-xs font-mono">
                      {logs.map((log) => (
                        <tr key={log.id} className="hover:bg-slate-50">
                          <td className="py-2.5 px-3 text-slate-700 whitespace-nowrap">
                            {formatDateIndo(log.created_at)}
                          </td>
                          <td className="py-2.5 px-3">
                            {log.is_valid || log.status === 'SUCCESS' ? (
                              <Badge variant="success" size="sm">VALID</Badge>
                            ) : (
                              <Badge variant="danger" size="sm">INVALID</Badge>
                            )}
                          </td>
                          <td className="py-2.5 px-3 text-slate-600">{log.ip_address || '-'}</td>
                          <td className="py-2.5 px-3 text-slate-600 font-sans">
                            {log.scanner?.name || 'Publik (Anonim)'}
                          </td>
                          <td className="py-2.5 px-3 text-slate-400 max-w-xs truncate" title={log.user_agent}>
                            {log.user_agent || '-'}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </Table>
                </div>
              )}
            </Card>
          </div>

          {/* Action Sidebar */}
          <div className="space-y-4">
            <Card>
              <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">
                Tindakan Integritas QR
              </h4>
              <div className="space-y-3">
                {!(qrIdentity?.is_revoked || qrIdentity?.status === 'REVOKED') ? (
                  <Button
                    variant="danger"
                    className="w-full justify-center"
                    onClick={() => setShowRevokeModal(true)}
                    leftIcon={<NoSymbolIcon className="w-4 h-4" />}
                  >
                    Cabut QR (Revoke)
                  </Button>
                ) : (
                  <div className="p-3 bg-red-50 text-red-700 text-xs rounded-lg text-center font-medium">
                    QR ini telah dicabut secara permanen.
                  </div>
                )}

                <Button
                  variant="outline"
                  className="w-full justify-center"
                  onClick={() => setShowRegenModal(true)}
                  leftIcon={<ArrowPathIcon className="w-4 h-4" />}
                >
                  Terbitkan Ulang (Regenerate)
                </Button>
              </div>
            </Card>

            {/* Architecture Explainer */}
            <Card className="bg-gradient-to-br from-slate-900 to-gov-navy text-white">
              <div className="flex items-center gap-2 mb-2 text-gov-gold">
                <ShieldCheckIcon className="w-5 h-5" />
                <span className="font-bold text-xs uppercase tracking-wider">Standar Kriptografi Sulut</span>
              </div>
              <p className="text-xs text-slate-300 leading-relaxed">
                SIKOMANDO mengimplementasikan token verifikasi bertanda tangan hash (HMAC-SHA256) tahan pemalsuan.
                Pencabutan token dicatat dalam audit trail permanen yang tidak dapat dimanipulasi.
              </p>
              <div className="mt-4 pt-3 border-t border-white/10 flex items-center gap-2 text-[11px] text-slate-400">
                <ServerIcon className="w-4 h-4" />
                <span>Zero Knowledge Leak Prevention</span>
              </div>
            </Card>
          </div>
        </div>
      ) : (
        <Card className="p-12 text-center">
          <QrCodeIcon className="w-16 h-16 text-slate-300 mx-auto mb-3" />
          <h3 className="font-bold text-slate-700">Resolusi Identitas QR</h3>
          <p className="text-xs text-slate-400 max-w-md mx-auto mt-1">
            Gunakan kotak pencarian di atas untuk memasukkan string token QR dari dokumen cetak untuk memeriksa status legalitas dan integritas berkas.
          </p>
        </Card>
      )}

      {/* Revoke Modal */}
      <Modal
        isOpen={showRevokeModal}
        onClose={() => setShowRevokeModal(false)}
        title="Cabut QR Code (Revocation)"
        size="md"
      >
        <form onSubmit={handleRevoke} className="space-y-4">
          <Alert variant="danger">
            Tindakan ini akan membatalkan legalitas token QR secara permanen. Pengguna publik yang memindai QR ini akan mendapatkan status DIBATALKAN.
          </Alert>
          <Textarea
            label="Alasan Pencabutan"
            value={actionReason}
            onChange={(e) => setActionReason(e.target.value)}
            placeholder="Jelaskan alasan resmi pencabutan (contoh: pemalsuan dokumen fisik, revisi data penerima)..."
            required
            rows={4}
          />
          <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
            <Button
              type="button"
              variant="outline"
              onClick={() => setShowRevokeModal(false)}
              disabled={actionLoading}
            >
              Batal
            </Button>
            <Button
              type="submit"
              variant="danger"
              loading={actionLoading}
            >
              Konfirmasi Cabut QR
            </Button>
          </div>
        </form>
      </Modal>

      {/* Regenerate Modal */}
      <Modal
        isOpen={showRegenModal}
        onClose={() => setShowRegenModal(false)}
        title="Terbitkan Ulang QR Code"
        size="md"
      >
        <form onSubmit={handleRegenerate} className="space-y-4">
          <Alert variant="warning">
            Token lama akan diberi status SUPERSEDED (digantikan), dan sebuah token baru akan di-generate untuk entitas ini.
          </Alert>
          <Textarea
            label="Alasan Regenerasi"
            value={actionReason}
            onChange={(e) => setActionReason(e.target.value)}
            placeholder="Jelaskan alasan penerbitan ulang token..."
            required
            rows={4}
          />
          <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
            <Button
              type="button"
              variant="outline"
              onClick={() => setShowRegenModal(false)}
              disabled={actionLoading}
            >
              Batal
            </Button>
            <Button
              type="submit"
              variant="primary"
              loading={actionLoading}
            >
              Terbitkan QR Baru
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  );
}

