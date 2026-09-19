import React, { useEffect, useState } from 'react';
import { useNavigate, useSearchParams, Link } from 'react-router-dom';
import { createProposal } from '../api/proposals';
import { getPublicGrantPrograms } from '../api/public';
import { useAuth } from '../context/useAuth';
import { ArrowLeftIcon, DocumentCheckIcon, ExclamationCircleIcon } from '@heroicons/react/24/outline';
import LoadingSpinner from '../components/common/LoadingSpinner';

const initialForm = {
  grant_program_id: '',
  organization_id: '',
  title: '',
  background: '',
  objectives: '',
  benefits: '',
  activities: '',
  expected_outputs: '',
};

export default function ProposalCreatePage() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const { user } = useAuth();

  const [form, setForm] = useState({
    ...initialForm,
    grant_program_id: searchParams.get('program') || '',
  });

  const [programs, setPrograms] = useState([]);
  const [loadingPrograms, setLoadingPrograms] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [fieldErrors, setFieldErrors] = useState({});

  useEffect(() => {
    async function loadPrograms() {
      try {
        const res = await getPublicGrantPrograms();
        const list = res?.data || res || [];
        setPrograms(Array.isArray(list) ? list : list.data || []);
      } catch (err) {
        console.error('Failed to load grant programs', err);
      } finally {
        setLoadingPrograms(false);
      }
    }

    loadPrograms();
  }, []);

  function handleChange(e) {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
    setFieldErrors((prev) => ({ ...prev, [name]: undefined }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setSubmitting(true);
    setError('');
    setFieldErrors({});

    const payload = {
      grant_program_id: form.grant_program_id,
      organization_id: form.organization_id || user?.organization_id || user?.id,
      title: form.title.trim(),
      background: form.background.trim() || null,
      objectives: form.objectives.trim() || null,
      benefits: form.benefits.trim() || null,
      activities: form.activities.trim() || null,
      expected_outputs: form.expected_outputs.trim() || null,
    };

    try {
      const response = await createProposal(payload);
      const created = response?.data || response;
      if (created?.id) {
        navigate(`/proposals/${created.id}`);
        return;
      }
      navigate('/proposals');
    } catch (err) {
      const resp = err.response?.data;
      setError(resp?.message || 'Gagal menyimpan proposal. Periksa kembali kelengkapan form.');
      if (resp?.errors) {
        setFieldErrors(resp.errors);
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <Link
        to="/proposals"
        className="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-800 transition"
      >
        <ArrowLeftIcon className="h-3.5 w-3.5" /> Kembali ke Daftar Usulan
      </Link>

      <div className="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs">
        <div className="border-b border-slate-200 pb-5">
          <h1 className="text-xl font-bold text-slate-900 sm:text-2xl">
            Formulir Pengajuan Usulan Hibah Baru
          </h1>
          <p className="mt-1 text-xs text-slate-500">
            Lengkapi rincian usulan kegiatan organisasi sesuai ketentuan regulasi hibah daerah.
          </p>
        </div>

        {error && (
          <div className="mt-6 flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-700">
            <ExclamationCircleIcon className="h-5 w-5 shrink-0 text-rose-500" />
            <span>{error}</span>
          </div>
        )}

        <form onSubmit={handleSubmit} className="mt-6 space-y-6">
          {/* Program Selection */}
          <div>
            <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
              Pilih Program Hibah *
            </label>
            {loadingPrograms ? (
              <LoadingSpinner size="sm" text="Memuat program hibah..." />
            ) : (
              <select
                name="grant_program_id"
                value={form.grant_program_id}
                onChange={handleChange}
                required
                className="mt-1.5 w-full rounded-lg border border-slate-300 py-2.5 px-3 text-sm focus:border-blue-500 focus:outline-hidden"
              >
                <option value="">-- Pilih Program Hibah Terbuka --</option>
                {programs.map((p) => (
                  <option key={p.id} value={p.id}>
                    {p.name} (TA {p.fiscal_year || '2026'})
                  </option>
                ))}
              </select>
            )}
            {fieldErrors.grant_program_id && (
              <p className="mt-1 text-xs text-rose-600">{fieldErrors.grant_program_id[0]}</p>
            )}
          </div>

          {/* Organization ID */}
          <div>
            <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
              ID Organisasi Pemohon *
            </label>
            <input
              type="text"
              name="organization_id"
              value={form.organization_id}
              onChange={handleChange}
              placeholder="Masukkan UUID Organisasi (atau ID Profil Anda)..."
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2.5 px-3 text-sm font-mono focus:border-blue-500 focus:outline-hidden"
            />
            <p className="mt-1 text-[11px] text-slate-400">
              Gunakan ID entitas organisasi terdaftar Anda di sistem.
            </p>
            {fieldErrors.organization_id && (
              <p className="mt-1 text-xs text-rose-600">{fieldErrors.organization_id[0]}</p>
            )}
          </div>

          {/* Proposal Title */}
          <div>
            <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
              Judul Usulan Proposal *
            </label>
            <input
              type="text"
              name="title"
              value={form.title}
              onChange={handleChange}
              placeholder="Contoh: Bantuan Pengadaan Sarana & Prasarana Pelatihan Kepemudaan..."
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2.5 px-3 text-sm focus:border-blue-500 focus:outline-hidden"
            />
            {fieldErrors.title && (
              <p className="mt-1 text-xs text-rose-600">{fieldErrors.title[0]}</p>
            )}
          </div>

          {/* Background */}
          <div>
            <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
              Latar Belakang
            </label>
            <textarea
              name="background"
              rows={3}
              value={form.background}
              onChange={handleChange}
              placeholder="Jelaskan kondisi faktual dan alasan mengapa kegiatan ini perlu mendapatkan bantuan hibah..."
              className="mt-1.5 w-full rounded-lg border border-slate-300 p-3 text-sm focus:border-blue-500 focus:outline-hidden"
            />
          </div>

          {/* Objectives & Benefits Grid */}
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                Maksud & Tujuan
              </label>
              <textarea
                name="objectives"
                rows={3}
                value={form.objectives}
                onChange={handleChange}
                placeholder="Tujuan spesifik yang ingin dicapai melalui kegiatan..."
                className="mt-1.5 w-full rounded-lg border border-slate-300 p-3 text-sm focus:border-blue-500 focus:outline-hidden"
              />
            </div>

            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                Manfaat Kegiatan
              </label>
              <textarea
                name="benefits"
                rows={3}
                value={form.benefits}
                onChange={handleChange}
                placeholder="Manfaat yang diperoleh penerima manfaat dan masyarakat luas..."
                className="mt-1.5 w-full rounded-lg border border-slate-300 p-3 text-sm focus:border-blue-500 focus:outline-hidden"
              />
            </div>
          </div>

          {/* Activities & Outputs */}
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                Rencana Aktivitas
              </label>
              <textarea
                name="activities"
                rows={3}
                value={form.activities}
                onChange={handleChange}
                placeholder="Tahapan pelaksanaan kegiatan secara runtut..."
                className="mt-1.5 w-full rounded-lg border border-slate-300 p-3 text-sm focus:border-blue-500 focus:outline-hidden"
              />
            </div>

            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                Target Output / Keluaran
              </label>
              <textarea
                name="expected_outputs"
                rows={3}
                value={form.expected_outputs}
                onChange={handleChange}
                placeholder="Kuantitas output terukur (misal: 50 peserta, 1 set alat lab)..."
                className="mt-1.5 w-full rounded-lg border border-slate-300 p-3 text-sm focus:border-blue-500 focus:outline-hidden"
              />
            </div>
          </div>

          <div className="rounded-xl border border-blue-200 bg-blue-50/60 p-4 text-xs text-blue-800">
            <p className="font-semibold">Catatan Pengunggahan Berkas & RAB:</p>
            <p className="mt-1">
              Setelah draft usulan berhasil disimpan, Anda akan diarahkan ke halaman detail usulan
              untuk mengunggah berkas persyaratan resmi, menyusun Rincian Anggaran Biaya (RAB), dan
              mengirim usulan ke tahap verifikasi.
            </p>
          </div>

          {/* Submit Actions */}
          <div className="flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
            <Link
              to="/proposals"
              className="rounded-lg border border-slate-300 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
            >
              Batal
            </Link>
            <button
              type="submit"
              disabled={submitting}
              className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-blue-700 transition disabled:opacity-50"
            >
              <DocumentCheckIcon className="h-4 w-4" />
              <span>{submitting ? 'Menyimpan Usulan...' : 'Simpan Draft Usulan'}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}