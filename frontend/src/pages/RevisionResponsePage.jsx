import React, { useEffect, useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { getProposalRevision, submitProposalRevision } from '../api/proposals';
import { ArrowLeftIcon, PaperAirplaneIcon, DocumentTextIcon, ExclamationCircleIcon } from '@heroicons/react/24/outline';
import LoadingSpinner from '../components/common/LoadingSpinner';
import FileUpload from '../components/common/FileUpload';

export default function RevisionResponsePage() {
  const { id: proposalId, revisionId } = useParams();
  const navigate = useNavigate();

  const [revision, setRevision] = useState(null);
  const [responseNotes, setResponseNotes] = useState('');
  const [selectedFile, setSelectedFile] = useState(null);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    async function loadRevision() {
      try {
        const res = await getProposalRevision(proposalId, revisionId);
        setRevision(res?.data || res);
      } catch (err) {
        setError(err.response?.data?.message || 'Gagal memuat catatan revisi.');
      } finally {
        setLoading(false);
      }
    }

    loadRevision();
  }, [proposalId, revisionId]);

  async function handleSubmit(e) {
    e.preventDefault();
    if (!responseNotes.trim()) {
      setError('Harap tuliskan penjelasan perbaikan Anda.');
      return;
    }

    setSubmitting(true);
    setError('');

    try {
      const payload = {
        response_notes: responseNotes.trim(),
        notes: responseNotes.trim(),
      };

      await submitProposalRevision(proposalId, revisionId, payload);
      alert('Tanggapan revisi berhasil dikirim ke verifikator!');
      navigate(`/proposals/${proposalId}`);
    } catch (err) {
      setError(err.response?.data?.message || 'Gagal mengirimkan perbaikan revisi.');
    } finally {
      setSubmitting(false);
    }
  }

  if (loading) {
    return <LoadingSpinner text="Memuat lembar revisi..." />;
  }

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <Link
        to={`/proposals/${proposalId}`}
        className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition"
      >
        <ArrowLeftIcon className="h-3.5 w-3.5" /> Kembali ke Lembar Usulan
      </Link>

      <div className="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs">
        <div className="border-b border-slate-200 pb-5">
          <div className="flex items-center gap-2">
            <span className="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-700 border border-amber-200">
              Permintaan Perbaikan Berkas
            </span>
          </div>
          <h1 className="mt-2 text-xl font-bold text-slate-900 sm:text-2xl">
            Tindak Lanjut Catatan Revisi Verifikator
          </h1>
          <p className="mt-1 text-xs text-slate-500">
            Periksa catatan kekurangan dari verifikator dan kirimkan kembali berkas yang telah diperbaiki.
          </p>
        </div>

        {/* Catatan Verifikator */}
        <div className="mt-6 rounded-xl border border-amber-200 bg-amber-50/60 p-5 space-y-2">
          <h3 className="text-xs font-bold uppercase tracking-wider text-amber-800">
            Catatan dari Tim Verifikator:
          </h3>
          <p className="text-xs text-amber-900 leading-relaxed whitespace-pre-line bg-white/80 p-3 rounded-lg border border-amber-200/50">
            {revision?.notes || revision?.reason || 'Lengkapi berkas dan sesuaikan rincian data usulan.'}
          </p>
        </div>

        {error && (
          <div className="mt-4 flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-700">
            <ExclamationCircleIcon className="h-4 w-4 shrink-0" />
            <span>{error}</span>
          </div>
        )}

        <form onSubmit={handleSubmit} className="mt-6 space-y-6">
          <div>
            <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
              Uraian Tanggapan & Tindak Lanjut Perbaikan *
            </label>
            <textarea
              rows={4}
              value={responseNotes}
              onChange={(e) => setResponseNotes(e.target.value)}
              placeholder="Jelaskan perbaikan apa saja yang telah Anda lakukan terhadap berkas atau usulan..."
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 p-3 text-xs focus:border-blue-500 focus:outline-hidden"
            />
          </div>

          <FileUpload
            label="Unggah Berkas Perbaikan Tambahan (Opsional)"
            selectedFile={selectedFile}
            onFileSelect={(file) => setSelectedFile(file)}
            onFileRemove={() => setSelectedFile(null)}
          />

          <div className="flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
            <Link
              to={`/proposals/${proposalId}`}
              className="rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
            >
              Batal
            </Link>
            <button
              type="submit"
              disabled={submitting}
              className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-blue-700 transition disabled:opacity-50"
            >
              <PaperAirplaneIcon className="h-4 w-4" />
              <span>{submitting ? 'Mengirim...' : 'Kirim Jawaban Revisi'}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

