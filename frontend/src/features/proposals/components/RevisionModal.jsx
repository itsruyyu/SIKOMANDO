import React, { useState } from 'react';
import api from '../../../services/api';
import Modal from '../../../components/ui/Modal';
import Textarea from '../../../components/ui/Textarea';
import Button from '../../../components/ui/Button';
import Alert from '../../../components/feedback/Alert';
import { useToast } from '../../../context/ToastContext';
import { PaperClipIcon, ArrowUpTrayIcon } from '@heroicons/react/24/outline';

export function RevisionModal({
  isOpen,
  onClose,
  proposalId,
  revisionId,
  onSuccess,
}) {
  const toast = useToast();
  const [notes, setNotes] = useState('');
  const [file, setFile] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!notes.trim()) {
      setErrorMsg('Catatan tanggapan perbaikan wajib diisi.');
      return;
    }

    setIsLoading(true);
    setErrorMsg('');

    try {
      const formData = new FormData();
      formData.append('notes', notes);
      if (file) {
        formData.append('document', file);
      }

      const url = revisionId
        ? `/proposals/${proposalId}/revisions/${revisionId}/submit`
        : `/proposals/${proposalId}/revisions`;

      await api.post(url, formData);
      toast.success('Tanggapan perbaikan revisi berhasil dikirim.');
      if (onSuccess) onSuccess();
      onClose();
    } catch (err) {
      setErrorMsg(err.message || 'Gagal mengirimkan perbaikan revisi.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title="Kirim Perbaikan Berkas Revisi"
      subtitle="Jelaskan poin-poin yang telah diperbaiki sesuai catatan verifikator"
      size="md"
      footer={
        <>
          <Button variant="secondary" size="sm" onClick={onClose} disabled={isLoading}>
            Batal
          </Button>
          <Button
            variant="primary"
            size="sm"
            onClick={handleSubmit}
            isLoading={isLoading}
            icon={ArrowUpTrayIcon}
          >
            Kirim Perbaikan
          </Button>
        </>
      }
    >
      <form onSubmit={handleSubmit} className="space-y-4">
        {errorMsg && <Alert type="danger">{errorMsg}</Alert>}

        <Textarea
          label="Catatan Tindak Lanjut Perbaikan"
          name="notes"
          value={notes}
          onChange={(e) => setNotes(e.target.value)}
          placeholder="Jelaskan perubahan atau dokumen baru yang telah disesuaikan..."
          rows={4}
          required
        />

        <div className="space-y-1.5">
          <label className="text-xs font-semibold text-slate-700 block">
            Unggah Dokumen Perbaikan (Opsional, PDF max 5MB)
          </label>
          <div className="flex items-center gap-3">
            <label className="cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-300 bg-slate-50 hover:bg-slate-100 text-xs font-semibold text-slate-700 transition">
              <PaperClipIcon className="w-4 h-4 text-slate-500" />
              <span>{file ? file.name : 'Pilih Berkas PDF'}</span>
              <input
                type="file"
                accept=".pdf,.doc,.docx"
                className="hidden"
                onChange={(e) => setFile(e.target.files?.[0] || null)}
              />
            </label>
            {file && (
              <button
                type="button"
                onClick={() => setFile(null)}
                className="text-xs text-rose-600 hover:underline"
              >
                Hapus
              </button>
            )}
          </div>
        </div>
      </form>
    </Modal>
  );
}

export default RevisionModal;

