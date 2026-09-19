import React, { useEffect, useState } from 'react';
import {
  getPolicyConfigurations,
  getPolicyVersions,
  storePolicyVersion,
  approvePolicyVersion,
  activatePolicyVersion,
} from '../api/policies';
import DataTable from '../components/common/DataTable';
import LoadingSpinner from '../components/common/LoadingSpinner';
import Modal from '../components/common/Modal';
import {
  Cog6ToothIcon,
  ShieldCheckIcon,
  CheckBadgeIcon,
  ArrowPathIcon,
} from '@heroicons/react/24/outline';

export default function SettingsPage() {
  const [policies, setPolicies] = useState([]);
  const [selectedPolicyCode, setSelectedPolicyCode] = useState(null);
  const [versions, setVersions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [successMsg, setSuccessMsg] = useState('');

  // Version modal
  const [versionModalOpen, setVersionModalOpen] = useState(false);
  const [newVersionValue, setNewVersionValue] = useState('');
  const [versionNotes, setVersionNotes] = useState('');

  async function loadPolicies() {
    setLoading(true);
    try {
      const res = await getPolicyConfigurations();
      const list = res?.data || res || [];
      const arr = Array.isArray(list) ? list : list.data || [];
      setPolicies(arr);

      if (arr.length > 0 && !selectedPolicyCode) {
        setSelectedPolicyCode(arr[0].code);
      }
    } catch (err) {
      console.error('Failed to load policies', err);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadPolicies();
  }, []);

  async function loadVersions(code) {
    if (!code) return;
    try {
      const res = await getPolicyVersions(code);
      const list = res?.data || res || [];
      setVersions(Array.isArray(list) ? list : list.data || []);
    } catch {
      setVersions([]);
    }
  }

  useEffect(() => {
    if (selectedPolicyCode) {
      loadVersions(selectedPolicyCode);
    }
  }, [selectedPolicyCode]);

  async function handleCreateVersion(e) {
    e.preventDefault();
    if (!selectedPolicyCode) return;
    setActionLoading(true);
    try {
      await storePolicyVersion(selectedPolicyCode, {
        value: newVersionValue,
        notes: versionNotes,
      });
      setVersionModalOpen(false);
      setNewVersionValue('');
      setVersionNotes('');
      setSuccessMsg('Versi kebijakan baru berhasil diusulkan!');
      await loadVersions(selectedPolicyCode);
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal mengusulkan versi kebijakan.');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleActivate(versionId) {
    if (!window.confirm('Aktifkan versi kebijakan ini sebagai konfigurasi operasional sistem yang berlaku?')) return;
    setActionLoading(true);
    try {
      await activatePolicyVersion(versionId);
      setSuccessMsg('Versi kebijakan berhasil diaktifkan!');
      await loadPolicies();
      await loadVersions(selectedPolicyCode);
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal mengaktifkan versi.');
    } finally {
      setActionLoading(false);
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Tata Kelola Kebijakan & Konfigurasi Sistem</h1>
          <p className="text-xs text-slate-500 mt-1">
            Pengaturan ambang batas penilaian, batas pagu bantuan, batas waktu revisi, dan riwayat versi kebijakan.
          </p>
        </div>

        {selectedPolicyCode && (
          <button
            type="button"
            onClick={() => setVersionModalOpen(true)}
            className="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-blue-700 transition"
          >
            <Cog6ToothIcon className="h-4 w-4" />
            <span>+ Usulkan Versi Kebijakan Baru</span>
          </button>
        )}
      </div>

      {successMsg && (
        <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-700">
          <CheckBadgeIcon className="h-5 w-5 shrink-0 text-emerald-600" />
          <span>{successMsg}</span>
        </div>
      )}

      {loading ? (
        <LoadingSpinner text="Memuat konfigurasi kebijakan..." />
      ) : (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
          {/* Policy List Menu */}
          <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs space-y-2">
            <h2 className="text-xs font-bold uppercase tracking-wider text-slate-400 px-2 pb-2">
              Daftar Konfigurasi Kebijakan
            </h2>
            {policies.length === 0 ? (
              <p className="text-xs text-slate-400 p-2">Belum ada kebijakan sistem terdaftar.</p>
            ) : (
              policies.map((pol) => (
                <button
                  key={pol.code || pol.id}
                  onClick={() => setSelectedPolicyCode(pol.code)}
                  className={`w-full text-left rounded-xl p-3 text-xs font-medium transition ${
                    selectedPolicyCode === pol.code
                      ? 'bg-blue-50 text-blue-700 border border-blue-200 font-bold'
                      : 'hover:bg-slate-50 text-slate-700'
                  }`}
                >
                  <p className="font-bold">{pol.name || pol.code}</p>
                  <p className="text-[11px] text-slate-500 font-mono mt-0.5">{pol.code}</p>
                </button>
              ))
            )}
          </div>

          {/* Versions Table */}
          <div className="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
              <div>
                <h3 className="text-sm font-bold text-slate-900">
                  Riwayat Versi Kebijakan: <span className="font-mono text-blue-700">{selectedPolicyCode}</span>
                </h3>
                <p className="text-xs text-slate-500">
                  Setiap perubahan nilai diarsipkan dan memerlukan persetujuan sebelum aktif.
                </p>
              </div>
            </div>

            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead className="bg-slate-50 font-semibold uppercase text-slate-500">
                  <tr>
                    <th className="px-4 py-3">Versi</th>
                    <th className="px-4 py-3">Nilai Parameter</th>
                    <th className="px-4 py-3">Catatan Perubahan</th>
                    <th className="px-4 py-3">Status</th>
                    <th className="px-4 py-3 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 bg-white">
                  {versions.length === 0 ? (
                    <tr>
                      <td colSpan={5} className="px-4 py-6 text-center text-slate-400">
                        Belum ada versi tercatat untuk kebijakan ini.
                      </td>
                    </tr>
                  ) : (
                    versions.map((ver, idx) => (
                      <tr key={ver.id || idx}>
                        <td className="px-4 py-3 font-mono font-bold text-slate-700">v{ver.version || idx + 1}</td>
                        <td className="px-4 py-3 font-bold text-slate-900">{ver.value || '—'}</td>
                        <td className="px-4 py-3 text-slate-500 max-w-xs">{ver.notes || 'Penyesuaian parameter.'}</td>
                        <td className="px-4 py-3">
                          <span
                            className={`rounded-full px-2 py-0.5 text-[10px] font-semibold ${
                              ver.is_active
                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                : 'bg-slate-100 text-slate-500'
                            }`}
                          >
                            {ver.is_active ? 'Aktif' : 'Arsip'}
                          </span>
                        </td>
                        <td className="px-4 py-3 text-right">
                          {!ver.is_active && (
                            <button
                              type="button"
                              onClick={() => handleActivate(ver.id)}
                              disabled={actionLoading}
                              className="rounded-md border border-slate-200 bg-white px-2 py-1 text-slate-700 hover:bg-slate-50 font-semibold text-[11px]"
                            >
                              Aktifkan
                            </button>
                          )}
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* MODAL VERSI BARU */}
      <Modal
        isOpen={versionModalOpen}
        onClose={() => setVersionModalOpen(false)}
        title={`Usulkan Versi Baru: ${selectedPolicyCode}`}
      >
        <form onSubmit={handleCreateVersion} className="space-y-4">
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Nilai Parameter Baru *
            </label>
            <input
              type="text"
              value={newVersionValue}
              onChange={(e) => setNewVersionValue(e.target.value)}
              placeholder="Contoh: 75 (passing grade) atau 14 (tenggat hari)..."
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-xs font-bold focus:border-blue-500 focus:outline-hidden"
            />
          </div>
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Catatan Justifikasi Perubahan *
            </label>
            <textarea
              rows={3}
              value={versionNotes}
              onChange={(e) => setVersionNotes(e.target.value)}
              placeholder="Jelaskan dasar hukum atau pertimbangan penyesuaian parameter..."
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 p-3 text-xs focus:border-blue-500 focus:outline-hidden"
            />
          </div>
          <div className="flex justify-end gap-2 pt-4">
            <button
              type="button"
              onClick={() => setVersionModalOpen(false)}
              className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={actionLoading}
              className="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700"
            >
              Simpan Usulan Versi
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}

