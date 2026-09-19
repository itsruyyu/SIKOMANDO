import React, { useEffect, useState } from 'react';
import { Link, useParams, useNavigate } from 'react-router-dom';
import {
  getProposal,
  submitProposal,
  getProposalDocuments,
  uploadProposalDocument,
  deleteProposalDocument,
  getProposalRevisions,
  getProposalPdfUrl,
} from '../api/proposals';
import WorkflowStepper from '../components/workflow/WorkflowStepper';
import StatusBadge from '../components/common/StatusBadge';
import LoadingSpinner from '../components/common/LoadingSpinner';
import EmptyState from '../components/common/EmptyState';
import FileUpload from '../components/common/FileUpload';
import Modal from '../components/common/Modal';
import {
  ArrowLeftIcon,
  DocumentArrowUpIcon,
  PrinterIcon,
  PaperAirplaneIcon,
  ArrowDownTrayIcon,
  TrashIcon,
  ExclamationCircleIcon,
  CheckCircleIcon,
  BanknotesIcon,
  BuildingOffice2Icon,
  CalendarDaysIcon,
} from '@heroicons/react/24/outline';

export default function ProposalDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();

  const [proposal, setProposal] = useState(null);
  const [documents, setDocuments] = useState([]);
  const [revisions, setRevisions] = useState([]);
  const [activeTab, setActiveTab] = useState('overview');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [actionLoading, setActionLoading] = useState(false);
  const [actionSuccess, setActionSuccess] = useState('');

  // Upload modal state
  const [isUploadModalOpen, setIsUploadModalOpen] = useState(false);
  const [uploadFileType, setUploadFileType] = useState('Surat Permohonan');
  const [selectedUploadFile, setSelectedUploadFile] = useState(null);
  const [uploadError, setUploadError] = useState('');

  async function loadData() {
    try {
      const [propRes, docRes, revRes] = await Promise.allSettled([
        getProposal(id),
        getProposalDocuments(id),
        getProposalRevisions(id),
      ]);

      if (propRes.status === 'fulfilled') {
        setProposal(propRes.value?.data || propRes.value);
      } else {
        setError('Gagal memuat detail proposal.');
      }

      if (docRes.status === 'fulfilled') {
        const docs = docRes.value?.data || docRes.value || [];
        setDocuments(Array.isArray(docs) ? docs : docs.data || []);
      }

      if (revRes.status === 'fulfilled') {
        const revs = revRes.value?.data || revRes.value || [];
        setRevisions(Array.isArray(revs) ? revs : revs.data || []);
      }
    } catch (err) {
      setError('Terjadi kesalahan saat memuat data.');
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadData();
  }, [id]);

  async function handleSubmitProposal() {
    if (!window.confirm('Apakah Anda yakin ingin mengajukan proposal ini ke tahap verifikasi administrasi? Setelah diajukan, proposal tidak dapat diubah.')) {
      return;
    }

    setActionLoading(true);
    setActionSuccess('');
    setError('');

    try {
      await submitProposal(id);
      setActionSuccess('Proposal berhasil diajukan ke tahap verifikasi administrasi!');
      await loadData();
    } catch (err) {
      setError(err.response?.data?.message || 'Gagal mengajukan proposal.');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleUploadDocument(e) {
    e.preventDefault();
    if (!selectedUploadFile) {
      setUploadError('Pilih berkas terlebih dahulu.');
      return;
    }

    setActionLoading(true);
    setUploadError('');

    const formData = new FormData();
    formData.append('document_file', selectedUploadFile);
    formData.append('file', selectedUploadFile);
    formData.append('document_type', uploadFileType);
    formData.append('title', uploadFileType);

    try {
      await uploadProposalDocument(id, formData);
      setIsUploadModalOpen(false);
      setSelectedUploadFile(null);
      setActionSuccess('Dokumen berhasil diunggah.');
      const docRes = await getProposalDocuments(id);
      const docs = docRes?.data || docRes || [];
      setDocuments(Array.isArray(docs) ? docs : docs.data || []);
    } catch (err) {
      setUploadError(err.response?.data?.message || 'Gagal mengunggah dokumen.');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleDeleteDocument(docId) {
    if (!window.confirm('Apakah Anda yakin ingin menghapus dokumen ini?')) return;
    try {
      await deleteProposalDocument(id, docId);
      setDocuments((prev) => prev.filter((d) => d.id !== docId));
      setActionSuccess('Dokumen berhasil dihapus.');
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menghapus dokumen.');
    }
  }

  if (loading) {
    return <LoadingSpinner text="Memuat berkas usulan proposal..." />;
  }

  if (error && !proposal) {
    return (
      <div className="rounded-xl border border-rose-200 bg-rose-50 p-6 text-sm text-rose-700">
        <p className="font-semibold">{error}</p>
        <Link to="/proposals" className="mt-3 inline-block font-bold underline">
          Kembali ke daftar usulan
        </Link>
      </div>
    );
  }

  const isDraft = proposal.status === 'draft';
  const isRevision = proposal.status === 'revision';

  return (
    <div className="space-y-6">
      {/* Back link & Title Bar */}
      <div>
        <Link
          to="/proposals"
          className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition"
        >
          <ArrowLeftIcon className="h-3.5 w-3.5" /> Kembali ke Daftar Usulan
        </Link>

        <div className="mt-3 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <div className="flex items-center gap-2">
              <span className="font-mono text-xs font-semibold text-blue-600">
                #{proposal.proposal_number || proposal.id?.slice(0, 8)}
              </span>
              <StatusBadge status={proposal.status} size="lg" />
            </div>
            <h1 className="mt-1.5 text-2xl font-black text-slate-900 sm:text-3xl">
              {proposal.title}
            </h1>
            <p className="text-xs text-slate-500 mt-1 flex items-center gap-2">
              <span>{proposal.organization?.name || 'Organisasi Pemohon'}</span>
              <span>•</span>
              <span>Program: {proposal.grant_program?.name || 'Hibah Daerah'}</span>
            </p>
          </div>

          {/* Action Buttons */}
          <div className="flex flex-wrap items-center gap-3">
            {isDraft && (
              <button
                type="button"
                onClick={handleSubmitProposal}
                disabled={actionLoading}
                className="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-blue-700 transition disabled:opacity-50"
              >
                <PaperAirplaneIcon className="h-4 w-4" />
                <span>Ajukan Proposal</span>
              </button>
            )}

            {isRevision && revisions.length > 0 && (
              <Link
                to={`/proposals/${id}/revisions/${revisions[0].id}`}
                className="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-amber-700 transition"
              >
                <span>Tindak Lanjut Revisi</span>
              </Link>
            )}

            <a
              href={`http://127.0.0.1:8000/api/v1/pdf/proposals/${id}`}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition"
            >
              <PrinterIcon className="h-4 w-4 text-slate-500" />
              <span>Cetak PDF Resmi</span>
            </a>
          </div>
        </div>
      </div>

      {/* Notifications / Alerts */}
      {actionSuccess && (
        <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-700">
          <CheckCircleIcon className="h-5 w-5 shrink-0 text-emerald-600" />
          <span>{actionSuccess}</span>
        </div>
      )}

      {error && (
        <div className="flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-700">
          <ExclamationCircleIcon className="h-5 w-5 shrink-0 text-rose-600" />
          <span>{error}</span>
        </div>
      )}

      {/* Workflow Stepper Card */}
      <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
        <h2 className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
          Tahapan Alur Hibah (SRS Lifecycle)
        </h2>
        <WorkflowStepper currentStatus={proposal.status} />
      </div>

      {/* Tabs Navigation */}
      <div className="border-b border-slate-200">
        <nav className="flex space-x-6 overflow-x-auto text-xs font-medium">
          {[
            { id: 'overview', name: 'Ringkasan Usulan' },
            { id: 'documents', name: `Dokumen Persyaratan (${documents.length})` },
            { id: 'budget', name: `Rencana Anggaran Biaya (RAB)` },
            { id: 'revisions', name: `Catatan Revisi (${revisions.length})` },
            { id: 'realizations', name: 'Realisasi & Kuitansi' },
          ].map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`pb-3.5 font-bold transition whitespace-nowrap border-b-2 ${
                activeTab === tab.id
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'
              }`}
            >
              {tab.name}
            </button>
          ))}
        </nav>
      </div>

      {/* TAB 1: OVERVIEW */}
      {activeTab === 'overview' && (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <div className="space-y-6 lg:col-span-2">
            <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
              <h3 className="text-sm font-bold uppercase text-slate-800 tracking-wider">
                Latar Belakang & Rasional Usulan
              </h3>
              <p className="text-xs text-slate-600 leading-relaxed whitespace-pre-line">
                {proposal.background || 'Latar belakang kegiatan belum diuraikan.'}
              </p>
            </div>

            <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
              <h3 className="text-sm font-bold uppercase text-slate-800 tracking-wider">
                Maksud, Tujuan & Manfaat
              </h3>
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 text-xs">
                <div>
                  <h4 className="font-semibold text-slate-700">Maksud & Tujuan:</h4>
                  <p className="mt-1 text-slate-600 leading-relaxed whitespace-pre-line">
                    {proposal.objectives || 'Belum terisi.'}
                  </p>
                </div>
                <div>
                  <h4 className="font-semibold text-slate-700">Manfaat:</h4>
                  <p className="mt-1 text-slate-600 leading-relaxed whitespace-pre-line">
                    {proposal.benefits || 'Belum terisi.'}
                  </p>
                </div>
              </div>
            </div>

            <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
              <h3 className="text-sm font-bold uppercase text-slate-800 tracking-wider">
                Rencana Aktivitas & Target Output
              </h3>
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 text-xs">
                <div>
                  <h4 className="font-semibold text-slate-700">Rencana Aktivitas:</h4>
                  <p className="mt-1 text-slate-600 leading-relaxed whitespace-pre-line">
                    {proposal.activities || 'Belum terisi.'}
                  </p>
                </div>
                <div>
                  <h4 className="font-semibold text-slate-700">Target Output / Keluaran:</h4>
                  <p className="mt-1 text-slate-600 leading-relaxed whitespace-pre-line">
                    {proposal.outputs || proposal.expected_outputs || 'Belum terisi.'}
                  </p>
                </div>
              </div>
            </div>
          </div>

          {/* Sidebar Meta Card */}
          <div className="space-y-6">
            <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4 text-xs">
              <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400">
                Informasi Anggaran & Pengesahan
              </h3>

              <div>
                <p className="text-slate-500">Jumlah Dimohon (RAB)</p>
                <p className="text-base font-bold text-slate-900 mt-0.5">
                  {proposal.requested_amount
                    ? `Rp ${Number(proposal.requested_amount).toLocaleString('id-ID')}`
                    : 'Belum dihitung'}
                </p>
              </div>

              <div className="border-t border-slate-100 pt-3">
                <p className="text-slate-500">Tanggal Pengajuan</p>
                <p className="font-semibold text-slate-800 mt-0.5">
                  {proposal.submitted_at || 'Draft (Belum diajukan)'}
                </p>
              </div>

              <div className="border-t border-slate-100 pt-3">
                <p className="text-slate-500">ID Usulan Kriptografi</p>
                <p className="font-mono text-[11px] text-slate-600 break-all mt-0.5">
                  {proposal.id}
                </p>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* TAB 2: DOCUMENTS */}
      {activeTab === 'documents' && (
        <div className="space-y-6">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-base font-bold text-slate-900">Berkas Persyaratan Wajib</h3>
              <p className="text-xs text-slate-500">
                Unggah berkas legalitas, surat permohonan, SPTJM, dan naskah proposal resmi.
              </p>
            </div>
            {isDraft && (
              <button
                type="button"
                onClick={() => setIsUploadModalOpen(true)}
                className="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-blue-700 transition"
              >
                <DocumentArrowUpIcon className="h-4 w-4" />
                <span>Unggah Dokumen Baru</span>
              </button>
            )}
          </div>

          <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
            {documents.length === 0 ? (
              <EmptyState
                title="Belum Ada Dokumen"
                description="Unggah dokumen persyaratan untuk melengkapi kelayakan administrasi proposal Anda."
                actionLabel={isDraft ? 'Unggah Dokumen' : null}
                onAction={() => setIsUploadModalOpen(true)}
              />
            ) : (
              <table className="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead className="bg-slate-50 font-semibold uppercase text-slate-500">
                  <tr>
                    <th className="px-6 py-3.5">Jenis Dokumen</th>
                    <th className="px-6 py-3.5">Nama Berkas</th>
                    <th className="px-6 py-3.5">Ukuran</th>
                    <th className="px-6 py-3.5">Tanggal Unggah</th>
                    <th className="px-6 py-3.5 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 bg-white">
                  {documents.map((doc) => (
                    <tr key={doc.id} className="hover:bg-slate-50 transition">
                      <td className="px-6 py-4 font-bold text-slate-800">
                        {doc.document_type || doc.title || 'Berkas Dokumen'}
                      </td>
                      <td className="px-6 py-4 font-mono text-slate-600">{doc.file_name || doc.name}</td>
                      <td className="px-6 py-4 text-slate-500">
                        {doc.file_size ? `${(doc.file_size / 1024).toFixed(1)} KB` : '—'}
                      </td>
                      <td className="px-6 py-4 text-slate-500">
                        {doc.created_at || '—'}
                      </td>
                      <td className="px-6 py-4 text-right space-x-2">
                        <a
                          href={`http://127.0.0.1:8000/api/v1/proposals/${id}/documents/${doc.id}/download`}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="inline-flex items-center gap-1 rounded-md border border-slate-200 px-2.5 py-1 text-slate-700 hover:bg-slate-50"
                        >
                          <ArrowDownTrayIcon className="h-3.5 w-3.5" />
                          <span>Unduh</span>
                        </a>
                        {isDraft && (
                          <button
                            type="button"
                            onClick={() => handleDeleteDocument(doc.id)}
                            className="inline-flex items-center gap-1 rounded-md border border-rose-200 px-2.5 py-1 text-rose-600 hover:bg-rose-50"
                          >
                            <TrashIcon className="h-3.5 w-3.5" />
                            <span>Hapus</span>
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        </div>
      )}

      {/* TAB 3: BUDGET (RAB) */}
      {activeTab === 'budget' && (
        <div className="space-y-6">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h3 className="text-base font-bold text-slate-900">Rencana Anggaran Biaya (RAB)</h3>
              <p className="text-xs text-slate-500">
                Daftar rincian kebutuhan belanja barang, modal, dan operasional kegiatan.
              </p>
            </div>

            <div className="flex items-center gap-2">
              <span className="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-800 border border-amber-200">
                BLOCKED BY BACKEND: PUT /api/v1/proposals/:id/budget-items
              </span>
            </div>
          </div>

          <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
            {(!proposal.budget_items || proposal.budget_items.length === 0) ? (
              <EmptyState
                title="Rincian Anggaran Kosong"
                description="RAB terintegrasi secara otomatis saat pembuatan paket usulan. Pengeditan mandiri membutuhkan rute REST API budget-items."
              />
            ) : (
              <table className="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead className="bg-slate-50 font-semibold uppercase text-slate-500">
                  <tr>
                    <th className="px-6 py-3.5">Uraian / Nama Item</th>
                    <th className="px-6 py-3.5">Volume</th>
                    <th className="px-6 py-3.5">Harga Satuan (Rp)</th>
                    <th className="px-6 py-3.5 text-right">Subtotal (Rp)</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 bg-white">
                  {proposal.budget_items.map((item, idx) => (
                    <tr key={item.id || idx}>
                      <td className="px-6 py-4 font-semibold text-slate-800">{item.item_name}</td>
                      <td className="px-6 py-4 text-slate-600">{item.quantity}</td>
                      <td className="px-6 py-4 text-slate-600">
                        {Number(item.unit_price).toLocaleString('id-ID')}
                      </td>
                      <td className="px-6 py-4 text-right font-bold text-slate-900">
                        {Number(item.subtotal || item.quantity * item.unit_price).toLocaleString('id-ID')}
                      </td>
                    </tr>
                  ))}
                  <tr className="bg-slate-50 font-bold">
                    <td colSpan={3} className="px-6 py-4 text-right text-slate-800">
                      TOTAL USULAN RAB
                    </td>
                    <td className="px-6 py-4 text-right text-blue-700 text-sm">
                      {Number(proposal.requested_amount || 0).toLocaleString('id-ID')}
                    </td>
                  </tr>
                </tbody>
              </table>
            )}
          </div>
        </div>
      )}

      {/* TAB 4: REVISIONS */}
      {activeTab === 'revisions' && (
        <div className="space-y-6">
          <div>
            <h3 className="text-base font-bold text-slate-900">Riwayat Catatan Revisi Verifikator</h3>
            <p className="text-xs text-slate-500">
              Daftar catatan perbaikan yang diberikan oleh tim verifikator administrasi.
            </p>
          </div>

          {revisions.length === 0 ? (
            <EmptyState
              title="Tidak Ada Catatan Revisi"
              description="Proposal tidak sedang dalam status permintaan revisi perbaikan berkas."
            />
          ) : (
            <div className="space-y-4">
              {revisions.map((rev) => (
                <div
                  key={rev.id}
                  className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-3"
                >
                  <div className="flex items-center justify-between text-xs">
                    <span className="font-bold text-amber-700 bg-amber-50 px-2.5 py-0.5 rounded-full border border-amber-200">
                      Revisi #{rev.id?.slice(0, 6)}
                    </span>
                    <span className="text-slate-500">
                      Tenggat: {rev.deadline || 'Sesuai Ketentuan'}
                    </span>
                  </div>

                  <div>
                    <h4 className="text-xs font-semibold text-slate-700">Catatan Perbaikan:</h4>
                    <p className="mt-1 text-xs text-slate-600 bg-slate-50 p-3 rounded-lg border border-slate-100 whitespace-pre-line">
                      {rev.notes || rev.reason || 'Mohon lengkapi berkas yang belum sesuai.'}
                    </p>
                  </div>

                  {rev.status !== 'completed' && (
                    <div className="border-t border-slate-100 pt-3 flex justify-end">
                      <Link
                        to={`/proposals/${id}/revisions/${rev.id}`}
                        className="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700 transition"
                      >
                        Tindak Lanjut & Kirim Revisi
                      </Link>
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* TAB 5: REALIZATIONS & RECEIPTS */}
      {activeTab === 'realizations' && (
        <div className="rounded-2xl border border-slate-200 bg-white p-8 text-center space-y-4">
          <BanknotesIcon className="mx-auto h-12 w-12 text-blue-600" />
          <h3 className="text-base font-bold text-slate-900">
            Pelaksanaan Realisasi Fisik & Kuitansi Pembelanjaan
          </h3>
          <p className="text-xs text-slate-500 max-w-md mx-auto">
            Setelah dana hibah disalurkan melalui SP2D, pemohon mencatat paket pengadaan barang,
            item fisik ber-QR, kuitansi bukti bayar, dan menerbitkan Berita Acara Serah Terima (BAST).
          </p>
          <div className="pt-2">
            <Link
              to={`/proposals/${id}/realizations`}
              className="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-blue-700 transition"
            >
              <span>Buka Modul Realisasi & Kuitansi</span>
            </Link>
          </div>
        </div>
      )}

      {/* MODAL UPLOAD DOCUMENT */}
      <Modal
        isOpen={isUploadModalOpen}
        onClose={() => setIsUploadModalOpen(false)}
        title="Unggah Berkas Persyaratan Usulan"
      >
        <form onSubmit={handleUploadDocument} className="space-y-4">
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Jenis Berkas Dokumen
            </label>
            <select
              value={uploadFileType}
              onChange={(e) => setUploadFileType(e.target.value)}
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-xs focus:border-blue-500 focus:outline-hidden"
            >
              <option value="Surat Permohonan Hibah">Surat Permohonan Hibah</option>
              <option value="Proposal & RAB Lengkap">Proposal & RAB Lengkap</option>
              <option value="Akta Notaris / Legalitas">Akta Notaris / Legalitas Lembaga</option>
              <option value="NPWP Lembaga">NPWP Lembaga</option>
              <option value="Buku Rekening Bank">Buku Rekening Bank Lembaga</option>
              <option value="SPTJM">Surat Pernyataan Tanggung Jawab Mutlak (SPTJM)</option>
              <option value="Dokumen Pendukung Lainnya">Dokumen Pendukung Lainnya</option>
            </select>
          </div>

          <FileUpload
            label="Pilih File Berkas"
            selectedFile={selectedUploadFile}
            onFileSelect={(file) => setSelectedUploadFile(file)}
            onFileRemove={() => setSelectedUploadFile(null)}
          />

          {uploadError && <p className="text-xs text-rose-600">{uploadError}</p>}

          <div className="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
            <button
              type="button"
              onClick={() => setIsUploadModalOpen(false)}
              className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={actionLoading || !selectedUploadFile}
              className="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
            >
              {actionLoading ? 'Mengunggah...' : 'Unggah Sekarang'}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}