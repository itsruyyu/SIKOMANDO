import React, { useEffect, useState } from 'react';
import {
  getSignatureProfiles,
  createSignatureProfile,
  getPendingSignatures,
  signSignature,
  rejectSignature,
} from '../api/signatures';
import DataTable from '../components/common/DataTable';
import LoadingSpinner from '../components/common/LoadingSpinner';
import Modal from '../components/common/Modal';
import {
  PencilSquareIcon,
  ShieldCheckIcon,
  CheckBadgeIcon,
  XCircleIcon,
  LockClosedIcon,
} from '@heroicons/react/24/outline';

export default function DigitalSignaturePage() {
  const [profiles, setProfiles] = useState([]);
  const [pending, setPending] = useState([]);
  const [activeTab, setActiveTab] = useState('pending'); // pending | profiles
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [successMsg, setSuccessMsg] = useState('');

  // Profile modal
  const [profileModalOpen, setProfileModalOpen] = useState(false);
  const [officialName, setOfficialName] = useState('');
  const [officialNip, setOfficialNip] = useState('');
  const [officialPosition, setOfficialPosition] = useState('');

  async function loadData() {
    setLoading(true);
    try {
      const [profRes, pendRes] = await Promise.allSettled([
        getSignatureProfiles(),
        getPendingSignatures(),
      ]);

      if (profRes.status === 'fulfilled') {
        const pList = profRes.value?.data || profRes.value || [];
        setProfiles(Array.isArray(pList) ? pList : pList.data || []);
      }
      if (pendRes.status === 'fulfilled') {
        const qList = pendRes.value?.data || pendRes.value || [];
        setPending(Array.isArray(qList) ? qList : qList.data || []);
      }
    } catch (err) {
      console.error('Failed to load digital signature data', err);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadData();
  }, []);

  async function handleSign(signatureId) {
    if (!window.confirm('Tandatangani dokumen elektronik ini secara digital menggunakan profil spesimen Anda?')) return;
    setActionLoading(true);
    try {
      await signSignature(signatureId);
      setSuccessMsg('Dokumen berhasil ditandatangani secara elektronik (TTE Sah)!');
      await loadData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menandatangani dokumen.');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleReject(signatureId) {
    const reason = window.prompt('Masukkan alasan penolakan TTE:');
    if (!reason) return;

    setActionLoading(true);
    try {
      await rejectSignature(signatureId, { reason });
      setSuccessMsg('Permohonan TTE dokumen telah ditolak.');
      await loadData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menolak TTE.');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleCreateProfile(e) {
    e.preventDefault();
    setActionLoading(true);
    try {
      await createSignatureProfile({
        signer_name: officialName,
        nip: officialNip,
        position: officialPosition,
      });
      setProfileModalOpen(false);
      setOfficialName('');
      setOfficialNip('');
      setOfficialPosition('');
      setSuccessMsg('Profil spesimen TTE pejabat berhasil didaftarkan.');
      await loadData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal mendaftarkan spesimen TTE.');
    } finally {
      setActionLoading(false);
    }
  }

  const pendingColumns = [
    {
      title: 'Judul Dokumen / Naskah',
      key: 'document_title',
      render: (val, row) => (
        <div>
          <p className="font-bold text-slate-900">{val || row.title || 'Surat Keputusan / BAST'}</p>
          <p className="text-xs text-slate-500 font-mono">ID: {row.id?.slice(0, 8)}</p>
        </div>
      ),
    },
    {
      title: 'Jenis Dokumen',
      key: 'document_type',
      render: (val, row) => (
        <span className="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
          {val || row.type || 'Naskah SK Penetapan'}
        </span>
      ),
    },
    {
      title: 'Tanggal Permohonan',
      key: 'created_at',
      render: (val) => <span className="text-xs text-slate-500">{val || '—'}</span>,
    },
    {
      title: 'Aksi TTE Pejabat',
      key: 'action',
      render: (_, row) => (
        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => handleSign(row.id)}
            disabled={actionLoading}
            className="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700 transition"
          >
            <CheckBadgeIcon className="h-4 w-4" />
            <span>Tandatangani (TTE)</span>
          </button>
          <button
            type="button"
            onClick={() => handleReject(row.id)}
            disabled={actionLoading}
            className="rounded-lg border border-rose-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50"
          >
            Tolak
          </button>
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Tanda Tangan Elektronik (TTE) Pejabat</h1>
          <p className="text-xs text-slate-500 mt-1">
            Penandatanganan dokumen elektronik SK, BAST, dan naskah dinas dengan verifikasi kriptografi terintegrasi.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => setProfileModalOpen(true)}
            className="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-blue-700 transition"
          >
            <PencilSquareIcon className="h-4 w-4" />
            <span>+ Daftarkan Profil Spesimen TTE</span>
          </button>
        </div>
      </div>

      {successMsg && (
        <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-700">
          <CheckBadgeIcon className="h-5 w-5 shrink-0 text-emerald-600" />
          <span>{successMsg}</span>
        </div>
      )}

      {/* Tabs */}
      <div className="border-b border-slate-200">
        <nav className="flex space-x-6 text-xs font-bold">
          <button
            onClick={() => setActiveTab('pending')}
            className={`pb-3 border-b-2 transition ${
              activeTab === 'pending'
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-slate-500 hover:text-slate-700'
            }`}
          >
            Antrean Dokumen Butuh TTE ({pending.length})
          </button>
          <button
            onClick={() => setActiveTab('profiles')}
            className={`pb-3 border-b-2 transition ${
              activeTab === 'profiles'
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-slate-500 hover:text-slate-700'
            }`}
          >
            Profil Spesimen TTE Pejabat ({profiles.length})
          </button>
        </nav>
      </div>

      {/* TAB 1: PENDING QUEUE */}
      {activeTab === 'pending' && (
        <DataTable
          columns={pendingColumns}
          data={pending}
          loading={loading}
          searchPlaceholder="Cari dokumen yang butuh penandatanganan..."
          emptyTitle="Tidak Ada Antrean TTE"
          emptyDescription="Saat ini tidak ada permohonan tanda tangan elektronik yang menunggu persetujuan Anda."
        />
      )}

      {/* TAB 2: PROFILES */}
      {activeTab === 'profiles' && (
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {profiles.length === 0 ? (
            <div className="col-span-full rounded-xl border border-dashed border-slate-300 p-8 text-center text-xs text-slate-500">
              Belum ada profil spesimen TTE terdaftar. Klik "+ Daftarkan Profil Spesimen TTE" untuk menambahkan.
            </div>
          ) : (
            profiles.map((prof) => (
              <div
                key={prof.id}
                className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4"
              >
                <div className="flex items-center justify-between">
                  <span className="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 border border-emerald-200">
                    Spesimen Aktif
                  </span>
                  <LockClosedIcon className="h-4 w-4 text-slate-400" />
                </div>

                <div>
                  <h3 className="text-base font-bold text-slate-900">{prof.signer_name || prof.name}</h3>
                  <p className="text-xs text-slate-500">NIP: {prof.nip || '—'}</p>
                  <p className="text-xs text-slate-600 font-medium mt-1">{prof.position || 'Kepala Dinas / Pimpinan'}</p>
                </div>

                <div className="border-t border-slate-100 pt-3 flex items-center justify-between text-[11px] text-slate-400">
                  <span>Enkripsi RSA-2048</span>
                  <span className="font-mono text-blue-600 font-semibold">TTE Valid</span>
                </div>
              </div>
            ))
          )}
        </div>
      )}

      {/* MODAL PROFIL */}
      <Modal
        isOpen={profileModalOpen}
        onClose={() => setProfileModalOpen(false)}
        title="Daftarkan Profil Spesimen TTE Pejabat"
      >
        <form onSubmit={handleCreateProfile} className="space-y-4">
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Nama Lengkap Pejabat *
            </label>
            <input
              type="text"
              value={officialName}
              onChange={(e) => setOfficialName(e.target.value)}
              placeholder="Contoh: Dr. H. Fulan, M.Si."
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-xs focus:border-blue-500 focus:outline-hidden"
            />
          </div>
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Nomor Induk Pegawai (NIP) *
            </label>
            <input
              type="text"
              value={officialNip}
              onChange={(e) => setOfficialNip(e.target.value)}
              placeholder="19800101 200501 1 001"
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-xs font-mono focus:border-blue-500 focus:outline-hidden"
            />
          </div>
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Jabatan Resmi Kedinasan *
            </label>
            <input
              type="text"
              value={officialPosition}
              onChange={(e) => setOfficialPosition(e.target.value)}
              placeholder="Contoh: Kepala Badan Keuangan dan Aset Daerah"
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-xs focus:border-blue-500 focus:outline-hidden"
            />
          </div>
          <div className="flex justify-end gap-2 pt-4">
            <button
              type="button"
              onClick={() => setProfileModalOpen(false)}
              className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={actionLoading}
              className="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700"
            >
              Simpan Profil TTE
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}

