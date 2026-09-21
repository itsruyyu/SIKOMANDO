import React, { useState, useEffect } from 'react';
import api from '../../services/api';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody } from '../../components/ui/Card';
import Table, {
  TableHead,
  TableBody,
  TableRow,
  TableHeaderCell,
  TableCell,
} from '../../components/ui/Table';
import Badge from '../../components/ui/Badge';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import EmptyState from '../../components/feedback/EmptyState';
import { useToast } from '../../context/ToastContext';
import { formatDate } from '../../utils/formatters';
import {
  CheckBadgeIcon,
  ShieldCheckIcon,
  DocumentCheckIcon,
  XCircleIcon,
  LockClosedIcon,
  UserCircleIcon,
  ArrowPathIcon,
  KeyIcon,
} from '@heroicons/react/24/outline';

export default function DigitalSignaturePage() {
  const { addToast } = useToast();

  const [activeTab, setActiveTab] = useState('pending'); // 'pending' | 'profile'
  const [pendingSignatures, setPendingSignatures] = useState([]);
  const [profiles, setProfiles] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);

  // Profile modal / form
  const [isProfileModalOpen, setIsProfileModalOpen] = useState(false);
  const [profileForm, setProfileForm] = useState({
    signer_name: '',
    nip: '',
    position: '',
  });

  // Reject modal
  const [rejectTarget, setRejectTarget] = useState(null);
  const [rejectReason, setRejectReason] = useState('');

  const loadData = async () => {
    setIsLoading(true);
    try {
      const [pendRes, profRes] = await Promise.allSettled([
        api.get('/signatures/pending'),
        api.get('/signature-profiles'),
      ]);

      if (pendRes.status === 'fulfilled') {
        const list = Array.isArray(pendRes.value?.data)
          ? pendRes.value.data
          : Array.isArray(pendRes.value?.data?.data)
          ? pendRes.value.data.data
          : [];
        setPendingSignatures(list);
      }

      if (profRes.status === 'fulfilled') {
        const pList = Array.isArray(profRes.value?.data)
          ? profRes.value.data
          : Array.isArray(profRes.value?.data?.data)
          ? profRes.value.data.data
          : [];
        setProfiles(pList);
      }
    } catch (err) {
      console.error('Failed to load digital signature data:', err);
      addToast('Gagal memuat data tanda tangan digital.', 'error');
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const handleSign = async (item) => {
    if (
      !window.confirm(
        `Tandatangani dokumen "${item.document_title || item.type || 'Dokumen'}" secara digital dengan identitas elektronik Anda?`
      )
    ) {
      return;
    }

    setActionLoading(true);
    try {
      await api.post(`/signatures/${item.id}/sign`);
      addToast('Dokumen berhasil ditandatangani secara elektronik (TTE Sah)!', 'success');
      loadData();
    } catch (err) {
      console.error('Signing failed:', err);
      addToast(err.message || 'Gagal menandatangani dokumen.', 'error');
    } finally {
      setActionLoading(false);
    }
  };

  const handleRejectSubmit = async (e) => {
    e.preventDefault();
    if (!rejectReason.trim()) {
      addToast('Harap masukkan alasan penolakan TTE.', 'warning');
      return;
    }

    setActionLoading(true);
    try {
      await api.post(`/signatures/${rejectTarget.id}/reject`, {
        reason: rejectReason,
      });
      addToast('Permohonan tanda tangan elektronik berhasil ditolak.', 'info');
      setRejectTarget(null);
      setRejectReason('');
      loadData();
    } catch (err) {
      console.error('Reject signature failed:', err);
      addToast(err.message || 'Gagal menolak permohonan TTE.', 'error');
    } finally {
      setActionLoading(false);
    }
  };

  const handleCreateProfile = async (e) => {
    e.preventDefault();
    setActionLoading(true);
    try {
      await api.post('/signature-profiles', profileForm);
      addToast('Profil tanda tangan digital berhasil disimpan.', 'success');
      setIsProfileModalOpen(false);
      setProfileForm({ signer_name: '', nip: '', position: '' });
      loadData();
    } catch (err) {
      console.error('Create profile failed:', err);
      addToast(err.message || 'Gagal menyimpan profil TTE.', 'error');
    } finally {
      setActionLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      <PageHeader
        title="Tanda Tangan Elektronik (TTE) Pejabat"
        subtitle="Otorisasi berkas keputusan, SK Gubernur, dan berita acara secara kriptografis tersertifikasi"
        breadcrumbs={[{ label: 'Penetapan & Otorisasi' }, { label: 'TTE Digital' }]}
        action={
          <div className="flex gap-2">
            <Button variant="secondary" onClick={loadData} className="flex items-center gap-1.5">
              <ArrowPathIcon className="w-4 h-4" />
              Segarkan
            </Button>
            <Button
              variant="primary"
              onClick={() => setIsProfileModalOpen(true)}
              className="flex items-center gap-1.5"
            >
              <KeyIcon className="w-4 h-4" />
              Kelola Profil TTE
            </Button>
          </div>
        }
      />

      {/* Tabs */}
      <div className="border-b border-slate-200">
        <nav className="flex space-x-8">
          <button
            onClick={() => setActiveTab('pending')}
            className={`pb-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 ${
              activeTab === 'pending'
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
            }`}
          >
            <DocumentCheckIcon className="w-5 h-5" />
            Antrean Penandatanganan
            {pendingSignatures.length > 0 && (
              <span className="ml-2 bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full font-bold">
                {pendingSignatures.length}
              </span>
            )}
          </button>
          <button
            onClick={() => setActiveTab('profile')}
            className={`pb-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 ${
              activeTab === 'profile'
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
            }`}
          >
            <ShieldCheckIcon className="w-5 h-5" />
            Sertifikat & Profil Terdaftar
          </button>
        </nav>
      </div>

      {isLoading ? (
        <div className="py-12 flex justify-center">
          <Spinner size="lg" />
        </div>
      ) : activeTab === 'pending' ? (
        <Card className="border-slate-200">
          {pendingSignatures.length === 0 ? (
            <EmptyState
              icon={CheckBadgeIcon}
              title="Semua Dokumen Sudah Ditandatangani"
              description="Tidak ada dokumen dinas atau SK penetapan hibah yang menunggu tanda tangan digital Anda saat ini."
            />
          ) : (
            <Table>
              <TableHead>
                <TableRow>
                  <TableHeaderCell>Dokumen / SK</TableHeaderCell>
                  <TableHeaderCell>Nomor Berkas</TableHeaderCell>
                  <TableHeaderCell>Tanggal Pengajuan</TableHeaderCell>
                  <TableHeaderCell>Status Keamanan</TableHeaderCell>
                  <TableHeaderCell className="text-right">Aksi Otorisasi</TableHeaderCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {pendingSignatures.map((item) => (
                  <TableRow key={item.id}>
                    <TableCell>
                      <div className="font-semibold text-slate-900">
                        {item.document_title || item.title || 'Dokumen Keputusan / Berita Acara'}
                      </div>
                      <div className="text-xs text-slate-500">
                        Jenis: {item.signable_type || 'SK Penetapan Hibah'}
                      </div>
                    </TableCell>
                    <TableCell className="font-mono text-xs text-slate-700">
                      {item.reference_number || item.id?.substring(0, 13) || '-'}
                    </TableCell>
                    <TableCell className="text-slate-500 text-xs">
                      {formatDate(item.created_at || new Date().toISOString())}
                    </TableCell>
                    <TableCell>
                      <Badge variant="warning" className="flex items-center gap-1 w-fit">
                        <LockClosedIcon className="w-3.5 h-3.5" />
                        Menunggu TTE
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
                        <Button
                          variant="danger"
                          size="sm"
                          disabled={actionLoading}
                          onClick={() => setRejectTarget(item)}
                        >
                          Tolak
                        </Button>
                        <Button
                          variant="primary"
                          size="sm"
                          disabled={actionLoading}
                          onClick={() => handleSign(item)}
                          className="flex items-center gap-1.5"
                        >
                          <CheckBadgeIcon className="w-4 h-4" />
                          Tanda Tangani
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </Card>
      ) : (
        <div className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {profiles.length === 0 ? (
              <Card className="p-8 text-center col-span-2 border-slate-200">
                <UserCircleIcon className="w-12 h-12 text-slate-400 mx-auto mb-3" />
                <h3 className="font-semibold text-slate-800">Profil TTE Belum Terdaftar</h3>
                <p className="text-sm text-slate-500 mt-1 max-w-md mx-auto">
                  Anda belum mendaftarkan profil penandatangan resmi (Nama, NIP, Jabatan). Daftarkan profil Anda untuk mulai menandatangani dokumen secara sah.
                </p>
                <Button
                  variant="primary"
                  onClick={() => setIsProfileModalOpen(true)}
                  className="mt-4"
                >
                  Daftarkan Profil Pejabat
                </Button>
              </Card>
            ) : (
              profiles.map((prof) => (
                <Card key={prof.id} className="p-6 border-slate-200 space-y-4">
                  <div className="flex justify-between items-start">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                        {prof.signer_name?.charAt(0) || 'P'}
                      </div>
                      <div>
                        <h4 className="font-bold text-slate-900">{prof.signer_name}</h4>
                        <div className="text-xs text-slate-500">{prof.position || 'Pejabat Penandatangan'}</div>
                      </div>
                    </div>
                    <Badge variant={prof.is_active ? 'success' : 'default'}>
                      {prof.is_active ? 'Aktif' : 'Non-Aktif'}
                    </Badge>
                  </div>

                  <div className="border-t pt-3 space-y-2 text-sm">
                    <div className="flex justify-between text-slate-600">
                      <span>NIP:</span>
                      <span className="font-mono font-medium text-slate-900">{prof.nip || '-'}</span>
                    </div>
                    <div className="flex justify-between text-slate-600">
                      <span>Masa Berlaku Kunci:</span>
                      <span className="text-slate-900">
                        {prof.certificate_expires_at ? formatDate(prof.certificate_expires_at) : 'Berlaku Permanen'}
                      </span>
                    </div>
                  </div>
                </Card>
              ))
            )}
          </div>
        </div>
      )}

      {/* Modal Buat / Edit Profil Penandatangan */}
      {isProfileModalOpen && (
        <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
          <div className="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
            <h3 className="text-lg font-bold text-slate-900 flex items-center gap-2">
              <KeyIcon className="w-5 h-5 text-blue-600" />
              Profil Pejabat Penandatangan
            </h3>
            <p className="text-xs text-slate-500">
              Identitas ini akan disematkan ke dalam metadata sertifikat kriptografi dan dicetak pada QR verifikasi publik.
            </p>

            <form onSubmit={handleCreateProfile} className="space-y-3">
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Nama Lengkap & Gelar Pejabat <span className="text-red-500">*</span>
                </label>
                <input
                  required
                  type="text"
                  placeholder="Contoh: Dr. Ir. H. Ahmad Fauzi, M.Si"
                  value={profileForm.signer_name}
                  onChange={(e) =>
                    setProfileForm({ ...profileForm, signer_name: e.target.value })
                  }
                  className="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  NIP / Identitas Pegawai <span className="text-red-500">*</span>
                </label>
                <input
                  required
                  type="text"
                  placeholder="19800101 200501 1 001"
                  value={profileForm.nip}
                  onChange={(e) => setProfileForm({ ...profileForm, nip: e.target.value })}
                  className="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Jabatan Dinas <span className="text-red-500">*</span>
                </label>
                <input
                  required
                  type="text"
                  placeholder="Contoh: Kepala Badan Keuangan dan Aset Daerah"
                  value={profileForm.position}
                  onChange={(e) =>
                    setProfileForm({ ...profileForm, position: e.target.value })
                  }
                  className="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t">
                <Button
                  type="button"
                  variant="secondary"
                  onClick={() => setIsProfileModalOpen(false)}
                >
                  Batal
                </Button>
                <Button type="submit" variant="primary" disabled={actionLoading}>
                  {actionLoading ? 'Menyimpan...' : 'Simpan Profil TTE'}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal Tolak TTE */}
      {rejectTarget && (
        <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
          <div className="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
            <h3 className="text-lg font-bold text-red-600 flex items-center gap-2">
              <XCircleIcon className="w-5 h-5" />
              Tolak Permohonan TTE
            </h3>
            <p className="text-xs text-slate-600">
              Dokumen akan dikembalikan ke pemroses dengan catatan perbaikan berikut.
            </p>

            <form onSubmit={handleRejectSubmit} className="space-y-3">
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Alasan Penolakan TTE <span className="text-red-500">*</span>
                </label>
                <textarea
                  required
                  rows={3}
                  placeholder="Tuliskan catatan perbaikan atau alasan penolakan..."
                  value={rejectReason}
                  onChange={(e) => setRejectReason(e.target.value)}
                  className="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-red-500"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t">
                <Button
                  type="button"
                  variant="secondary"
                  onClick={() => setRejectTarget(null)}
                >
                  Batal
                </Button>
                <Button type="submit" variant="danger" disabled={actionLoading}>
                  {actionLoading ? 'Memproses...' : 'Tolak Dokumen'}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

