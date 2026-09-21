import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import api from '../../services/api';
import { formatCurrency, formatDate, formatDateTime, formatFileSize } from '../../utils/formatters';
import { openPdf, downloadPdf } from '../../utils/pdf';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Badge from '../../components/ui/Badge';
import Button from '../../components/ui/Button';
import Tabs from '../../components/ui/Tabs';
import Table, { TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '../../components/ui/Table';
import Spinner from '../../components/feedback/Spinner';
import Alert from '../../components/feedback/Alert';
import RevisionModal from './components/RevisionModal';
import WorkflowStepper from '../../components/workflow/WorkflowStepper';
import {
  DocumentTextIcon,
  BanknotesIcon,
  PaperClipIcon,
  ClockIcon,
  PrinterIcon,
  ArrowUpTrayIcon,
  CheckBadgeIcon,
  BuildingLibraryIcon,
  UserCircleIcon,
  CalendarDaysIcon,
  EyeIcon,
  ArrowDownTrayIcon,
  XMarkIcon,
} from '@heroicons/react/24/outline';

export function ProposalDetailPage() {
  const { id } = useParams();
  const { user, isPemohon, isVerifikator, isEvaluator, isSurveyor, isApprover } = useAuth();
  const toast = useToast();

  const [proposal, setProposal] = useState(null);
  const [documents, setDocuments] = useState([]);
  const [revisions, setRevisions] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [errorMsg, setErrorMsg] = useState('');
  const [activeTab, setActiveTab] = useState('summary');
  const [revisionModalOpen, setRevisionModalOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [previewDoc, setPreviewDoc] = useState(null);
  const [downloadingDocId, setDownloadingDocId] = useState(null);

  const fetchProposalData = async () => {
    setIsLoading(true);
    setErrorMsg('');
    try {
      const [propRes, docRes, revRes] = await Promise.allSettled([
        api.get(`/proposals/${id}`),
        api.get(`/proposals/${id}/documents`),
        api.get(`/proposals/${id}/revisions`),
      ]);

      if (propRes.status === 'fulfilled' && propRes.value?.data) {
        setProposal(propRes.value.data);
      } else {
        throw new Error('Proposal tidak ditemukan atau Anda tidak memiliki hak akses.');
      }

      if (docRes.status === 'fulfilled' && docRes.value?.data) {
        setDocuments(docRes.value.data);
      }

      if (revRes.status === 'fulfilled' && revRes.value?.data) {
        setRevisions(revRes.value.data);
      }
    } catch (err) {
      setErrorMsg(err.message || 'Gagal memuat detail proposal.');
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchProposalData();
  }, [id]);

  const handleSubmitDraft = async () => {
    setIsSubmitting(true);
    try {
      await api.post(`/proposals/${id}/submit`);
      toast.success('Proposal berhasil diajukan untuk verifikasi administrasi.');
      fetchProposalData();
    } catch (err) {
      toast.error(err.message || 'Gagal mengajukan proposal.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handlePreviewDocument = async (doc) => {
    const filename = (doc.original_filename || '').toLowerCase();
    const mime = (doc.mime_type || '').toLowerCase();
    const isPdf = mime.includes('pdf') || filename.endsWith('.pdf');
    const isImage = mime.startsWith('image/') || filename.endsWith('.jpg') || filename.endsWith('.jpeg') || filename.endsWith('.png') || filename.endsWith('.webp');

    if (!isPdf && !isImage) {
      toast.info('Format berkas tidak mendukung pratinjau langsung di peramban. Silakan gunakan tombol Unduh untuk membuka berkas.');
      return;
    }

    setPreviewDoc({
      isOpen: true,
      title: doc.original_filename || 'Pratinjau Dokumen',
      url: null,
      isImage,
      isLoading: true,
      doc,
    });

    try {
      const res = await api.get(`/proposals/${proposal.id}/documents/${doc.id}/download`, {
        responseType: 'blob',
      });
      const blobMime = isPdf ? 'application/pdf' : (res.headers?.['content-type'] || 'image/jpeg');
      const blob = new Blob([res.data || res], { type: blobMime });
      const objectUrl = window.URL.createObjectURL(blob);
      setPreviewDoc((prev) => (prev?.isOpen ? { ...prev, url: objectUrl, isLoading: false } : prev));
    } catch (err) {
      toast.error(err.message || 'Gagal memuat pratinjau berkas.');
      setPreviewDoc(null);
    }
  };

  const handleClosePreview = () => {
    if (previewDoc?.url) {
      window.URL.revokeObjectURL(previewDoc.url);
    }
    setPreviewDoc(null);
  };

  const handleDownloadDocument = async (doc) => {
    setDownloadingDocId(doc.id);
    try {
      const res = await api.get(`/proposals/${proposal.id}/documents/${doc.id}/download`, {
        responseType: 'blob',
      });

      let filename = doc.original_filename || `dokumen-${doc.id}.pdf`;
      const disposition = res.headers?.['content-disposition'];
      if (disposition && disposition.includes('filename=')) {
        const match = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
        if (match?.[1]) {
          filename = match[1].replace(/['"]/g, '');
        }
      }

      const mimeType = doc.mime_type || res.headers?.['content-type'] || 'application/octet-stream';
      const blob = new Blob([res.data || res], { type: mimeType });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      link.remove();
      setTimeout(() => window.URL.revokeObjectURL(url), 2000);
      toast.success(`Berkas "${filename}" berhasil diunduh.`);
    } catch (err) {
      toast.error(err.message || 'Gagal mengunduh berkas persyaratan.');
    } finally {
      setDownloadingDocId(null);
    }
  };

  useEffect(() => {
    return () => {
      if (previewDoc?.url) {
        window.URL.revokeObjectURL(previewDoc.url);
      }
    };
  }, [previewDoc?.url]);

  if (isLoading) {
    return (
      <div className="py-20">
        <Spinner size="lg" label="Memuat rincian usulan proposal..." />
      </div>
    );
  }

  if (errorMsg || !proposal) {
    return (
      <div className="max-w-2xl mx-auto py-16">
        <Alert type="danger" title="Kesalahan Memuat Usulan">
          <p className="mb-4">{errorMsg || 'Proposal tidak ditemukan.'}</p>
          <Link to="/proposals">
            <Button variant="secondary" size="sm">
              Kembali ke Daftar Usulan
            </Button>
          </Link>
        </Alert>
      </div>
    );
  }

  const tabs = [
    { id: 'summary', label: 'Ringkasan & Narasi', icon: DocumentTextIcon },
    { id: 'rab', label: 'Rincian Anggaran (RAB)', icon: BanknotesIcon, count: proposal.budget_items?.length || 0 },
    { id: 'documents', label: 'Dokumen Persyaratan', icon: PaperClipIcon, count: documents.length },
    { id: 'revisions', label: 'Riwayat Revisi', icon: ClockIcon, count: revisions.length },
  ];

  return (
    <div className="space-y-6 pb-16">
      <PageHeader
        title={proposal?.title || 'Detail Usulan'}
        subtitle={`Nomor Registrasi: ${proposal?.proposal_number || 'DRAFT'} • Diajukan: ${formatDate(proposal?.submitted_at || proposal?.created_at)}`}
        breadcrumbs={[
          { label: 'Daftar Usulan', to: '/proposals' },
          { label: proposal?.proposal_number || 'Detail Usulan' },
        ]}
        badge={<Badge status={proposal?.status} />}
        action={
          <div className="flex items-center gap-2">
            {/* Draft Submission */}
            {proposal.status === 'draft' && isPemohon && (
              <Button
                variant="primary"
                size="sm"
                icon={ArrowUpTrayIcon}
                onClick={handleSubmitDraft}
                isLoading={isSubmitting}
              >
                Kirim Usulan Sekarang
              </Button>
            )}

            {/* Revision Action */}
            {proposal.status === 'revision' && (
              <Button
                variant="warning"
                size="sm"
                icon={ClockIcon}
                onClick={() => setRevisionModalOpen(true)}
              >
                Kirim Perbaikan Revisi
              </Button>
            )}

            {/* Contextual Workspace Buttons */}
            {isVerifikator && proposal.status === 'verification' && (
              <Link to={`/verifications/${proposal.id}`}>
                <Button variant="primary" size="sm">
                  Proses Verifikasi
                </Button>
              </Link>
            )}

            {isEvaluator && proposal.status === 'evaluation' && (
              <Link to={`/evaluations/${proposal.id}`}>
                <Button variant="primary" size="sm">
                  Proses Evaluasi
                </Button>
              </Link>
            )}

            {isSurveyor && proposal.status === 'survey' && (
              <Link to={`/field-surveys/${proposal.id}`}>
                <Button variant="primary" size="sm">
                  Input Hasil Survei
                </Button>
              </Link>
            )}

            {['disbursed', 'implementation', 'lpj_submitted'].includes(proposal.status) && (
              <Link to={`/realizations/${proposal.id}`}>
                <Button variant="secondary" size="sm">
                  Realisasi Belanja
                </Button>
              </Link>
            )}

            {/* PDF Generation */}
            <Button
              variant="outline"
              size="sm"
              icon={PrinterIcon}
              onClick={async () => {
                try {
                  await openPdf(`/pdf/proposals/${proposal.id}`);
                } catch {
                  toast.error('Gagal mengunduh berkas PDF ringkasan usulan.');
                }
              }}
            >
              Unduh PDF
            </Button>
          </div>
        }
      />

      {/* Tahapan Alur Kerja Usulan Hibah */}
      <Card className="p-4 border-slate-200 shadow-xs">
        <div className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">
          Tahapan Progres Usulan
        </div>
        <WorkflowStepper currentStatus={proposal?.status} />
      </Card>

      {/* Official Cryptographic QR Stamp */}
      {proposal.qr && (
        <div className="p-4 rounded-xl bg-emerald-50/70 border border-emerald-200 flex flex-col sm:flex-row items-center gap-4 shadow-2xs">
          <div className="w-20 h-20 bg-white p-2 rounded-xl border border-emerald-300 shadow-2xs flex items-center justify-center shrink-0">
            {proposal.qr.svg_url ? (
              <img
                src={proposal.qr.svg_url}
                alt="QR Code Kriptografis"
                className="w-full h-full object-contain"
              />
            ) : (
              <CheckBadgeIcon className="w-10 h-10 text-emerald-600" />
            )}
          </div>
          <div className="flex-1 text-center sm:text-left space-y-1">
            <div className="flex items-center justify-center sm:justify-start gap-2">
              <span className="text-xs font-bold text-emerald-900">QR Code Verifikasi Resmi SIKOMANDO</span>
              <span className="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                Kriptografis Aktif
              </span>
            </div>
            <p className="text-xs text-slate-600">
              Usulan proposal ini telah memiliki stempel digital resmi Pemprov Sulawesi Utara untuk pencegahan manipulasi data.
            </p>
            <div className="pt-1 flex flex-wrap items-center gap-2 justify-center sm:justify-start">
              <Link
                to={`/verify/${proposal.qr.token}`}
                className="text-xs font-semibold text-blue-700 hover:underline flex items-center gap-1"
              >
                <span>Validasi Status Dokumen</span>
                <span>→</span>
              </Link>
              <span className="text-slate-300">•</span>
              <span className="text-[11px] font-mono text-slate-500">
                Token: {proposal.qr.token?.substring(0, 20)}...
              </span>
            </div>
          </div>
        </div>
      )}

      {/* Overview Stat Strip */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="p-4 rounded-xl bg-white border border-slate-200/80 shadow-2xs">
          <span className="text-[11px] font-semibold text-slate-400 block">Organisasi Pemohon:</span>
          <span className="font-bold text-slate-900 text-sm block mt-0.5">{proposal.organization?.name || '-'}</span>
          <span className="text-[11px] text-slate-500">Ketua: {proposal.applicant?.name || '-'}</span>
        </div>

        <div className="p-4 rounded-xl bg-white border border-slate-200/80 shadow-2xs">
          <span className="text-[11px] font-semibold text-slate-400 block">Program Hibah:</span>
          <span className="font-bold text-blue-900 text-sm block mt-0.5">{proposal.grant_program?.name || '-'}</span>
          <span className="text-[11px] text-slate-500">T.A. {proposal.grant_program?.fiscal_year || '2026'}</span>
        </div>

        <div className="p-4 rounded-xl bg-white border border-slate-200/80 shadow-2xs">
          <span className="text-[11px] font-semibold text-slate-400 block">Anggaran Dimohon:</span>
          <span className="font-mono font-black text-slate-900 text-base block mt-0.5">
            {formatCurrency(proposal.requested_amount)}
          </span>
          <span className="text-[11px] text-slate-500">Berdasarkan akumulasi RAB</span>
        </div>

        <div className="p-4 rounded-xl bg-emerald-50 border border-emerald-200 shadow-2xs">
          <span className="text-[11px] font-semibold text-emerald-800 block">Pagu Disetujui (SK):</span>
          <span className="font-mono font-black text-emerald-950 text-base block mt-0.5">
            {formatCurrency(proposal.approved_amount || 0)}
          </span>
          <span className="text-[11px] text-emerald-700">
            {proposal.approved_amount ? 'Ketetapan Pimpinan' : 'Dalam Proses Penilaian'}
          </span>
        </div>
      </div>

      {/* Tabs */}
      <Tabs tabs={tabs} activeTab={activeTab} onChange={setActiveTab} />

      {/* TAB 1: SUMMARY & NARRATIVE */}
      {activeTab === 'summary' && (
        <div className="space-y-6">
          <Card className="border-slate-200">
            <CardHeader title="Narasi Substansi Proposal" subtitle="Uraian rencana kegiatan dan sasaran manfaat" />
            <CardBody className="space-y-5 text-sm">
              <div>
                <h4 className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">
                  Latar Belakang & Urgensi Kegiatan
                </h4>
                <p className="text-slate-800 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100">
                  {proposal.background || 'Tidak ada uraian latar belakang yang diisi.'}
                </p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <h4 className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">
                    Maksud & Tujuan
                  </h4>
                  <p className="text-slate-800 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100">
                    {proposal.objectives || '-'}
                  </p>
                </div>
                <div>
                  <h4 className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">
                    Manfaat Bagi Masyarakat
                  </h4>
                  <p className="text-slate-800 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100">
                    {proposal.benefits || '-'}
                  </p>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <h4 className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">
                    Tahapan Rangkaian Aktivitas
                  </h4>
                  <p className="text-slate-800 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100">
                    {proposal.activities || '-'}
                  </p>
                </div>
                <div>
                  <h4 className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">
                    Target Output / Luaran
                  </h4>
                  <p className="text-slate-800 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100">
                    {proposal.expected_outputs || proposal.outputs || '-'}
                  </p>
                </div>
              </div>
            </CardBody>
          </Card>
        </div>
      )}

      {/* TAB 2: RAB BUDGET ITEMS */}
      {activeTab === 'rab' && (
        <Card className="border-slate-200">
          <CardHeader
            title="Rincian Anggaran Biaya (RAB)"
            subtitle={`Total Pengajuan: ${formatCurrency(proposal.requested_amount)}`}
          />
          {(!proposal.budget_items || proposal.budget_items.length === 0) ? (
            <CardBody className="p-8 text-center text-slate-500 text-xs">
              Belum ada rincian item anggaran yang dicantumkan pada usulan ini.
            </CardBody>
          ) : (
            <Table>
              <TableHead>
                <tr>
                  <TableHeaderCell>No</TableHeaderCell>
                  <TableHeaderCell>Kategori</TableHeaderCell>
                  <TableHeaderCell>Uraian Barang / Kegiatan</TableHeaderCell>
                  <TableHeaderCell>Volume</TableHeaderCell>
                  <TableHeaderCell>Harga Satuan</TableHeaderCell>
                  <TableHeaderCell className="text-right">Subtotal</TableHeaderCell>
                </tr>
              </TableHead>
              <TableBody>
                {proposal.budget_items.map((item, idx) => (
                  <TableRow key={item.id || idx}>
                    <TableCell mono>{idx + 1}</TableCell>
                    <TableCell>
                      <span className="text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                        {item.category}
                      </span>
                    </TableCell>
                    <TableCell>
                      <div className="font-bold text-slate-900">{item.item_name}</div>
                      {(item.specification || item.description) && (
                        <div className="text-[11px] text-slate-400">{item.specification || item.description}</div>
                      )}
                    </TableCell>
                    <TableCell mono>
                      {item.quantity} {item.unit}
                    </TableCell>
                    <TableCell mono>{formatCurrency(item.unit_price)}</TableCell>
                    <TableCell mono className="text-right font-bold text-slate-900">
                      {formatCurrency(item.total_price ?? item.subtotal ?? (item.quantity * item.unit_price))}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </Card>
      )}

      {/* TAB 3: DOCUMENTS */}
      {activeTab === 'documents' && (
        <Card className="border-slate-200">
          <CardHeader title="Dokumen Legalitas & Persyaratan Terlampir" subtitle="Daftar berkas pendukung proposal" />
          {documents.length === 0 ? (
            <CardBody className="p-8 text-center text-slate-500 text-xs">
              Belum ada dokumen yang diunggah.
            </CardBody>
          ) : (
            <div className="divide-y divide-slate-100">
              {documents.map((doc) => (
                <div key={doc.id} className="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50 transition">
                  <div className="flex items-center gap-3">
                    <div className="p-2.5 rounded-xl bg-blue-50 text-blue-600 shrink-0">
                      <PaperClipIcon className="w-5 h-5" />
                    </div>
                    <div>
                      <div className="text-xs font-bold text-slate-800">{doc.original_filename || doc.document_type?.name || doc.document_type}</div>
                      <div className="text-[11px] text-slate-400 mt-0.5">
                        {doc.file_size ? `${formatFileSize(doc.file_size)} • ` : ''}Diunggah: {formatDate(doc.created_at)} • Status: <span className="font-semibold text-slate-600">{doc.verification_status || doc.status || 'Tersimpan'}</span>
                      </div>
                    </div>
                  </div>
                  <div className="flex items-center gap-2 self-end sm:self-center shrink-0">
                    <Button
                      variant="outline"
                      size="xs"
                      onClick={() => handlePreviewDocument(doc)}
                      icon={EyeIcon}
                    >
                      Lihat
                    </Button>
                    <Button
                      variant="outline"
                      size="xs"
                      onClick={() => handleDownloadDocument(doc)}
                      isLoading={downloadingDocId === doc.id}
                      icon={ArrowDownTrayIcon}
                    >
                      Unduh
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </Card>
      )}

      {/* TAB 4: REVISIONS */}
      {activeTab === 'revisions' && (
        <Card className="border-slate-200">
          <CardHeader title="Riwayat Catatan Revisi & Perbaikan" subtitle="Catatan hasil verifikasi dan perbaikan berkas" />
          {revisions.length === 0 ? (
            <CardBody className="p-8 text-center text-slate-500 text-xs">
              Tidak ada catatan revisi pada usulan ini. Berkas berjalan lancar.
            </CardBody>
          ) : (
            <div className="divide-y divide-slate-100">
              {revisions.map((rev) => (
                <div key={rev.id} className="p-5 space-y-2">
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-bold text-rose-800">
                      Revisi #{rev.revision_number || 1}
                    </span>
                    <span className="text-xs text-slate-400">{formatDateTime(rev.created_at)}</span>
                  </div>
                  <p className="text-xs text-slate-700 bg-amber-50 p-3 rounded-lg border border-amber-200">
                    <strong>Catatan Verifikator:</strong> {rev.notes || rev.reason || 'Mohon melengkapi berkas yang belum valid.'}
                  </p>
                  {rev.response_notes && (
                    <p className="text-xs text-slate-700 bg-emerald-50 p-3 rounded-lg border border-emerald-200 mt-2">
                      <strong>Tanggapan Pemohon:</strong> {rev.response_notes}
                    </p>
                  )}
                </div>
              ))}
            </div>
          )}
        </Card>
      )}

      {/* Revision Modal Dialog */}
      <RevisionModal
        isOpen={revisionModalOpen}
        onClose={() => setRevisionModalOpen(false)}
        proposalId={proposal.id}
        onSuccess={fetchProposalData}
      />

      {/* Document Preview Modal */}
      {previewDoc?.isOpen && (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
            {/* Modal Header */}
            <div className="p-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
              <div className="flex items-center gap-2.5 min-w-0 pr-4">
                <div className="p-2 rounded-lg bg-blue-100 text-blue-700 shrink-0">
                  <PaperClipIcon className="w-5 h-5" />
                </div>
                <div className="min-w-0">
                  <h3 className="text-sm font-bold text-slate-900 truncate">
                    {previewDoc.title}
                  </h3>
                  <span className="text-[11px] text-slate-400">
                    Pratinjau Berkas Digital SIKOMANDO
                  </span>
                </div>
              </div>
              <div className="flex items-center gap-2 shrink-0">
                {previewDoc.doc && (
                  <Button
                    variant="outline"
                    size="xs"
                    icon={ArrowDownTrayIcon}
                    onClick={() => handleDownloadDocument(previewDoc.doc)}
                  >
                    Unduh
                  </Button>
                )}
                <button
                  type="button"
                  onClick={handleClosePreview}
                  className="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200 transition cursor-pointer"
                  title="Tutup Pratinjau"
                >
                  <XMarkIcon className="w-5 h-5" />
                </button>
              </div>
            </div>

            {/* Modal Body */}
            <div className="p-4 flex-1 overflow-auto bg-slate-100 flex items-center justify-center min-h-[60vh]">
              {previewDoc.isLoading ? (
                <Spinner size="lg" label="Mengambil dan memuat berkas pratinjau..." />
              ) : previewDoc.isImage ? (
                <img
                  src={previewDoc.url}
                  alt={previewDoc.title}
                  className="max-h-[75vh] w-auto max-w-full rounded-lg shadow-md object-contain mx-auto"
                />
              ) : (
                <iframe
                  src={previewDoc.url}
                  title={previewDoc.title}
                  className="w-full h-[75vh] rounded-lg shadow-inner bg-white border border-slate-200"
                />
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default ProposalDetailPage;

