import React, { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import {
  getFieldSurvey,
  updateSurveySchedule,
  startFieldSurvey,
  updateSurveyItem,
  storeSurveyFinding,
  storeSurveyDocument,
  fillSurveyResult,
  submitSurvey,
  completeSurvey,
  getFieldSurveyPdfUrl,
} from '../api/fieldSurveys';
import { getProposal } from '../api/proposals';
import LoadingSpinner from '../components/common/LoadingSpinner';
import StatusBadge from '../components/common/StatusBadge';
import Modal from '../components/common/Modal';
import FileUpload from '../components/common/FileUpload';
import {
  ArrowLeftIcon,
  PrinterIcon,
  MapPinIcon,
  CalendarIcon,
  CameraIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  PlayIcon,
  DocumentCheckIcon,
} from '@heroicons/react/24/outline';

export default function FieldSurveyDetailPage() {
  const { proposalId, surveyId } = useParams();
  const navigate = useNavigate();

  const [survey, setSurvey] = useState(null);
  const [proposal, setProposal] = useState(null);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState('');
  const [successMsg, setSuccessMsg] = useState('');

  // Modals
  const [scheduleModalOpen, setScheduleModalOpen] = useState(false);
  const [newScheduleDate, setNewScheduleDate] = useState('');
  const [findingModalOpen, setFindingModalOpen] = useState(false);
  const [findingDescription, setFindingDescription] = useState('');
  const [photoModalOpen, setPhotoModalOpen] = useState(false);
  const [selectedPhoto, setSelectedPhoto] = useState(null);
  const [resultModalOpen, setResultModalOpen] = useState(false);
  const [conclusionStatus, setConclusionStatus] = useState('recommended'); // recommended | revision | rejected
  const [conclusionNotes, setConclusionNotes] = useState('');

  async function loadData() {
    try {
      const [survRes, propRes] = await Promise.allSettled([
        getFieldSurvey(proposalId, surveyId),
        getProposal(proposalId),
      ]);

      if (survRes.status === 'fulfilled') setSurvey(survRes.value?.data || survRes.value);
      if (propRes.status === 'fulfilled') setProposal(propRes.value?.data || propRes.value);
    } catch (err) {
      setError('Gagal memuat detail survei lapangan.');
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadData();
  }, [proposalId, surveyId]);

  async function handleStartSurvey() {
    setActionLoading(true);
    try {
      await startFieldSurvey(proposalId, surveyId);
      setSuccessMsg('Survei lapangan dimulai secara resmi!');
      await loadData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal memulai survei.');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleScheduleSubmit(e) {
    e.preventDefault();
    setActionLoading(true);
    try {
      await updateSurveySchedule(proposalId, surveyId, {
        scheduled_at: newScheduleDate,
        schedule_date: newScheduleDate,
      });
      setScheduleModalOpen(false);
      setSuccessMsg('Jadwal survei berhasil diperbarui.');
      await loadData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menjadwalkan survei.');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleFindingSubmit(e) {
    e.preventDefault();
    setActionLoading(true);
    try {
      await storeSurveyFinding(proposalId, surveyId, {
        description: findingDescription,
        finding_text: findingDescription,
      });
      setFindingModalOpen(false);
      setFindingDescription('');
      setSuccessMsg('Temuan lapangan berhasil ditambahkan.');
      await loadData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menyimpan temuan.');
    } finally {
      setActionLoading(false);
    }
  }

  async function handlePhotoUpload(e) {
    e.preventDefault();
    if (!selectedPhoto) return;
    setActionLoading(true);

    const formData = new FormData();
    formData.append('document_file', selectedPhoto);
    formData.append('file', selectedPhoto);
    formData.append('document_type', 'Foto Bukti Lapangan');

    try {
      await storeSurveyDocument(proposalId, surveyId, formData);
      setPhotoModalOpen(false);
      setSelectedPhoto(null);
      setSuccessMsg('Foto bukti lapangan berhasil diunggah.');
      await loadData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal mengunggah foto.');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleResultSubmit(e) {
    e.preventDefault();
    setActionLoading(true);
    try {
      await fillSurveyResult(proposalId, surveyId, {
        recommendation: conclusionStatus,
        result: conclusionStatus,
        notes: conclusionNotes,
      });
      await completeSurvey(proposalId, surveyId);
      setResultModalOpen(false);
      setSuccessMsg('Laporan hasil survei lapangan berhasil diselesaikan!');
      await loadData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menyimpan kesimpulan.');
    } finally {
      setActionLoading(false);
    }
  }

  if (loading) {
    return <LoadingSpinner text="Memuat lembar kerja survei lapangan..." />;
  }

  const findings = survey?.findings || [];
  const photos = survey?.documents || survey?.photos || [];
  const isCompleted = survey?.status === 'completed';

  return (
    <div className="space-y-6">
      <Link
        to="/field-surveys"
        className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition"
      >
        <ArrowLeftIcon className="h-3.5 w-3.5" /> Kembali ke Penugasan Survei
      </Link>

      {/* Header */}
      <div className="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div className="flex items-center gap-2">
              <span className="font-mono text-xs font-semibold text-purple-600">
                #{proposal?.proposal_number || proposalId?.slice(0, 8)}
              </span>
              <StatusBadge status={survey?.status || 'survey'} />
            </div>
            <h1 className="mt-1.5 text-xl font-bold text-slate-900 sm:text-2xl">
              Survei Faktual Lapangan: {proposal?.title}
            </h1>
            <p className="mt-1 text-xs text-slate-500 flex items-center gap-2">
              <MapPinIcon className="h-4 w-4 text-slate-400" />
              <span>{survey?.location_address || proposal?.organization?.address || 'Sekretariat Lembaga'}</span>
            </p>
          </div>

          <div className="flex flex-wrap items-center gap-3">
            <a
              href={getFieldSurveyPdfUrl(proposalId, surveyId)}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition"
            >
              <PrinterIcon className="h-4 w-4 text-slate-500" />
              <span>Cetak BAP Survei</span>
            </a>

            {!isCompleted && survey?.status !== 'in_progress' && (
              <button
                type="button"
                onClick={handleStartSurvey}
                disabled={actionLoading}
                className="inline-flex items-center gap-1.5 rounded-xl bg-purple-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-purple-700 transition"
              >
                <PlayIcon className="h-4 w-4" />
                <span>Mulai Survei Lapangan</span>
              </button>
            )}

            {!isCompleted && (
              <button
                type="button"
                onClick={() => setResultModalOpen(true)}
                className="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-slate-800 transition"
              >
                <DocumentCheckIcon className="h-4 w-4" />
                <span>Simpan Kesimpulan</span>
              </button>
            )}
          </div>
        </div>
      </div>

      {successMsg && (
        <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-700">
          <CheckCircleIcon className="h-5 w-5 shrink-0 text-emerald-600" />
          <span>{successMsg}</span>
        </div>
      )}

      {/* Detail Cards Grid */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Left 2 Cols: Temuan & Bukti Foto */}
        <div className="space-y-6 lg:col-span-2">
          {/* Temuan Lapangan */}
          <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-sm font-bold uppercase text-slate-800 tracking-wider">
                Catatan Temuan Fisik & Verifikasi Faktual
              </h3>
              {!isCompleted && (
                <button
                  type="button"
                  onClick={() => setFindingModalOpen(true)}
                  className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                >
                  + Tambah Temuan
                </button>
              )}
            </div>

            {findings.length === 0 ? (
              <p className="text-xs text-slate-400 italic">Belum ada catatan temuan yang ditambahkan.</p>
            ) : (
              <div className="space-y-3">
                {findings.map((f, idx) => (
                  <div key={f.id || idx} className="rounded-lg border border-slate-100 bg-slate-50 p-3.5 text-xs text-slate-700">
                    <p className="font-semibold text-slate-800">Temuan #{idx + 1}:</p>
                    <p className="mt-1 leading-relaxed">{f.description || f.finding_text}</p>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Foto Dokumentasi Lapangan */}
          <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-sm font-bold uppercase text-slate-800 tracking-wider">
                Dokumentasi & Foto Lokasi
              </h3>
              {!isCompleted && (
                <button
                  type="button"
                  onClick={() => setPhotoModalOpen(true)}
                  className="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                >
                  <CameraIcon className="h-4 w-4 text-purple-600" />
                  <span>Unggah Foto Bukti</span>
                </button>
              )}
            </div>

            {photos.length === 0 ? (
              <p className="text-xs text-slate-400 italic">Belum ada foto dokumentasi lapangan yang diunggah.</p>
            ) : (
              <div className="grid grid-cols-2 gap-4 sm:grid-cols-3">
                {photos.map((ph, idx) => (
                  <div key={ph.id || idx} className="rounded-xl border border-slate-200 overflow-hidden bg-slate-100 p-2 text-center">
                    <div className="h-28 flex items-center justify-center bg-slate-200 rounded-lg text-slate-400">
                      <CameraIcon className="h-8 w-8" />
                    </div>
                    <p className="mt-2 text-[11px] font-semibold text-slate-700 truncate">
                      {ph.name || ph.file_name || `Foto Bukti #${idx + 1}`}
                    </p>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Right Col: Jadwal & Status */}
        <div className="space-y-6">
          <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4 text-xs">
            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400">
              Jadwal & Pelaksanaan
            </h3>

            <div>
              <p className="text-slate-500">Tanggal Survei Lapangan</p>
              <p className="text-sm font-bold text-slate-900 mt-0.5">
                {survey?.scheduled_at || survey?.schedule_date || 'Belum Ditentukan'}
              </p>
              {!isCompleted && (
                <button
                  type="button"
                  onClick={() => setScheduleModalOpen(true)}
                  className="mt-2 text-purple-600 font-semibold hover:underline"
                >
                  Ubah Jadwal Survei
                </button>
              )}
            </div>

            <div className="border-t border-slate-100 pt-3">
              <p className="text-slate-500">Hasil Rekomendasi</p>
              <p className="font-bold text-slate-800 mt-0.5">
                {survey?.recommendation || survey?.result || 'Dalam Proses Penelaahan'}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* MODAL JADWAL */}
      <Modal
        isOpen={scheduleModalOpen}
        onClose={() => setScheduleModalOpen(false)}
        title="Atur Jadwal Survei Lapangan"
      >
        <form onSubmit={handleScheduleSubmit} className="space-y-4">
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Pilih Tanggal & Waktu Survei
            </label>
            <input
              type="date"
              value={newScheduleDate}
              onChange={(e) => setNewScheduleDate(e.target.value)}
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2.5 px-3 text-xs focus:border-purple-500 focus:outline-hidden"
            />
          </div>
          <div className="flex justify-end gap-2 pt-4">
            <button
              type="button"
              onClick={() => setScheduleModalOpen(false)}
              className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={actionLoading}
              className="rounded-lg bg-purple-600 px-4 py-2 text-xs font-semibold text-white hover:bg-purple-700"
            >
              Simpan Jadwal
            </button>
          </div>
        </form>
      </Modal>

      {/* MODAL TEMUAN */}
      <Modal
        isOpen={findingModalOpen}
        onClose={() => setFindingModalOpen(false)}
        title="Tambah Temuan Lapangan Faktual"
      >
        <form onSubmit={handleFindingSubmit} className="space-y-4">
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Uraian Temuan Fisik
            </label>
            <textarea
              rows={4}
              value={findingDescription}
              onChange={(e) => setFindingDescription(e.target.value)}
              placeholder="Catat kondisi fisik sekretariat, sarana prasarana, plang nama lembaga, atau wawancara pengurus..."
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 p-3 text-xs focus:border-purple-500 focus:outline-hidden"
            />
          </div>
          <div className="flex justify-end gap-2 pt-4">
            <button
              type="button"
              onClick={() => setFindingModalOpen(false)}
              className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={actionLoading}
              className="rounded-lg bg-purple-600 px-4 py-2 text-xs font-semibold text-white hover:bg-purple-700"
            >
              Simpan Temuan
            </button>
          </div>
        </form>
      </Modal>

      {/* MODAL FOTO */}
      <Modal
        isOpen={photoModalOpen}
        onClose={() => setPhotoModalOpen(false)}
        title="Unggah Foto Bukti Faktual Lapangan"
      >
        <form onSubmit={handlePhotoUpload} className="space-y-4">
          <FileUpload
            label="Pilih Foto Bukti (JPG/PNG)"
            accept="image/*"
            selectedFile={selectedPhoto}
            onFileSelect={(f) => setSelectedPhoto(f)}
            onFileRemove={() => setSelectedPhoto(null)}
          />
          <div className="flex justify-end gap-2 pt-4">
            <button
              type="button"
              onClick={() => setPhotoModalOpen(false)}
              className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={actionLoading || !selectedPhoto}
              className="rounded-lg bg-purple-600 px-4 py-2 text-xs font-semibold text-white hover:bg-purple-700 disabled:opacity-50"
            >
              Unggah Foto
            </button>
          </div>
        </form>
      </Modal>

      {/* MODAL KESIMPULAN */}
      <Modal
        isOpen={resultModalOpen}
        onClose={() => setResultModalOpen(false)}
        title="Tetapkan Kesimpulan Hasil Survei Lapangan"
      >
        <form onSubmit={handleResultSubmit} className="space-y-4">
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Rekomendasi Hasil Survei *
            </label>
            <select
              value={conclusionStatus}
              onChange={(e) => setConclusionStatus(e.target.value)}
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-xs font-semibold focus:border-purple-500 focus:outline-hidden"
            >
              <option value="recommended">Layak Direkomendasikan Bantuan Hibah</option>
              <option value="revision">Perlu Penyesuaian / Revisi Lapangan</option>
              <option value="rejected">Tidak Layak (Organisasi Fiktif / Tidak Ditemukan)</option>
            </select>
          </div>

          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Catatan Kesimpulan Surveyor
            </label>
            <textarea
              rows={4}
              value={conclusionNotes}
              onChange={(e) => setConclusionNotes(e.target.value)}
              placeholder="Tuliskan justifikasi kelayakan faktual di lapangan..."
              className="mt-1.5 w-full rounded-lg border border-slate-300 p-3 text-xs focus:border-purple-500 focus:outline-hidden"
            />
          </div>

          <div className="flex justify-end gap-2 pt-4">
            <button
              type="button"
              onClick={() => setResultModalOpen(false)}
              className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={actionLoading}
              className="rounded-lg bg-purple-600 px-4 py-2 text-xs font-semibold text-white hover:bg-purple-700 disabled:opacity-50"
            >
              Simpan & Rampungkan
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}

