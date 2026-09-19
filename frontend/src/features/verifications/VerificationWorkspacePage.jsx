import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import api from '../../services/api';
import { useToast } from '../../context/ToastContext';
import { formatCurrency, formatDate } from '../../utils/formatters';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody, CardHeader, CardFooter } from '../../components/ui/Card';
import Badge from '../../components/ui/Badge';
import Button from '../../components/ui/Button';
import Textarea from '../../components/ui/Textarea';
import Spinner from '../../components/feedback/Spinner';
import Alert from '../../components/feedback/Alert';
import { openPdf } from '../../utils/pdf';
import {
  ClipboardDocumentCheckIcon,
  CheckCircleIcon,
  XCircleIcon,
  ArrowPathIcon,
  PaperClipIcon,
  DocumentTextIcon,
  ArrowLeftIcon,
  ShieldCheckIcon,
} from '@heroicons/react/24/outline';

export function VerificationWorkspacePage() {
  const { proposalId } = useParams();
  const navigate = useNavigate();
  const toast = useToast();

  const [proposal, setProposal] = useState(null);
  const [verification, setVerification] = useState(null);
  const [items, setItems] = useState([]);
  const [documents, setDocuments] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isCompleting, setIsCompleting] = useState(false);
  const [overallNotes, setOverallNotes] = useState('');
  const [conclusion, setConclusion] = useState('pass'); // 'pass', 'revision_required', 'reject'

  const loadVerificationData = async () => {
    setIsLoading(true);
    try {
      const [propRes, docRes, verListRes] = await Promise.all([
        api.get(`/proposals/${proposalId}`),
        api.get(`/proposals/${proposalId}/documents`),
        api.get(`/proposals/${proposalId}/verifications`),
      ]);

      setProposal(propRes.data);
      setDocuments(docRes.data || []);

      let activeVer = null;
      if (verListRes.data && verListRes.data.length > 0) {
        activeVer = verListRes.data[0];
      } else {
        // Initialize verification session
        try {
          const createRes = await api.post(`/proposals/${proposalId}/verifications`, {
            notes: 'Pemeriksaan administrasi berkas proposal.',
          });
          activeVer = createRes.data;
        } catch {
          // If creation fails due to existing or permission
        }
      }

      if (activeVer) {
        setVerification(activeVer);
        const mappedItems = (activeVer.items || []).map((it) => {
          let status = 'valid';
          const r = it.result?.value || it.result;
          if (r === 'pass') status = 'valid';
          else if (r === 'need_revision') status = 'revision_required';
          else if (r === 'fail') status = 'invalid';
          else status = r || 'pending';
          return { ...it, status };
        });
        setItems(mappedItems);
        setOverallNotes(activeVer.notes || '');
      }
    } catch (err) {
      console.error('Failed to load verification workspace:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadVerificationData();
  }, [proposalId]);

  const handleItemToggle = async (item, status) => {
    const mappedResult = status === 'valid' ? 'pass' : (status === 'revision_required' ? 'need_revision' : 'fail');
    const updated = items.map((it) => (it.id === item.id ? { ...it, status, result: mappedResult } : it));
    setItems(updated);

    if (verification) {
      try {
        await api.patch(
          `/proposals/${proposalId}/verifications/${verification.id}/items/${item.id}`,
          { result: mappedResult, status: mappedResult, notes: item.notes || '' }
        );
      } catch (err) {
        toast.error(err.message || 'Gagal menyimpan status item.');
      }
    }
  };

  const handleItemNotesChange = (item, notes) => {
    const updated = items.map((it) => (it.id === item.id ? { ...it, notes } : it));
    setItems(updated);
  };

  const handleItemNotesBlur = async (item) => {
    if (verification && item.id) {
      try {
        const mappedResult = item.status === 'valid' ? 'pass' : (item.status === 'revision_required' ? 'need_revision' : 'fail');
        await api.patch(
          `/proposals/${proposalId}/verifications/${verification.id}/items/${item.id}`,
          { result: mappedResult, status: mappedResult, notes: item.notes || '' }
        );
      } catch {
        // Silently handled
      }
    }
  };

  const handleCompleteVerification = async () => {
    if (!verification) return;

    if (items.length > 0) {
      const unverified = items.filter((it) => it.status === 'pending' || it.result === 'pending');
      if (unverified.length > 0) {
        toast.warning(`Masih ada ${unverified.length} butir checklist yang belum diperiksa.`);
        return;
      }
    }

    setIsCompleting(true);

    try {
      await api.post(`/proposals/${proposalId}/verifications/${verification.id}/complete`, {
        conclusion,
        notes: overallNotes || 'Pemeriksaan administrasi selesai dievaluasi.',
      });

      toast.success('Hasil verifikasi administrasi berhasil disimpan dan diterbitkan!');
      navigate('/verifications');
    } catch (err) {
      toast.error(err.message || 'Gagal menyelesaikan verifikasi.');
    } finally {
      setIsCompleting(false);
    }
  };

  if (isLoading) {
    return (
      <div className="py-20">
        <Spinner size="lg" label="Menyiapkan meja verifikasi administrasi..." />
      </div>
    );
  }

  return (
    <div className="space-y-6 pb-20">
      <PageHeader
        title={`Verifikasi Dokumen: ${proposal?.proposal_number || 'USULAN'}`}
        subtitle="Periksa keabsahan berkas fisik dan digital pemohon sesuai regulasi Permendagri & Pergub Sulut"
        breadcrumbs={[
          { label: 'Verifikasi Administrasi', to: '/verifications' },
          { label: 'Workspace' },
        ]}
        action={
          <Link to="/verifications">
            <Button variant="secondary" size="sm" icon={ArrowLeftIcon}>
              Kembali ke Antrean
            </Button>
          </Link>
        }
      />

      {/* Split-Screen Workspace Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {/* LEFT PANE (5 Cols): Proposal Overview & Attached Documents */}
        <div className="lg:col-span-5 space-y-6">
          <Card className="border-slate-200 shadow-xs">
            <CardHeader title="Profil Berkas Usulan" subtitle={proposal?.organization?.name} />
            <CardBody className="space-y-4 text-xs">
              <div>
                <span className="text-slate-400 block font-medium mb-0.5">Judul Usulan:</span>
                <span className="font-bold text-slate-800 text-sm leading-snug">{proposal?.title}</span>
              </div>
              <div className="grid grid-cols-2 gap-3 pt-2 border-t border-slate-100">
                <div>
                  <span className="text-slate-400 block font-medium">Program Hibah:</span>
                  <span className="font-bold text-blue-900">{proposal?.grant_program?.name}</span>
                </div>
                <div>
                  <span className="text-slate-400 block font-medium">Pagu Anggaran:</span>
                  <span className="font-mono font-bold text-slate-900">{formatCurrency(proposal?.requested_amount)}</span>
                </div>
              </div>
              <div className="pt-2 border-t border-slate-100">
                <span className="text-slate-400 block font-medium mb-1">Narasi Singkat Latar Belakang:</span>
                <p className="text-slate-700 line-clamp-4 leading-relaxed bg-slate-50 p-2.5 rounded-lg">
                  {proposal?.background || '-'}
                </p>
              </div>
            </CardBody>
          </Card>

          {/* Attached Documents List */}
          <Card className="border-slate-200 shadow-xs">
            <CardHeader
              title="Berkas Lampiran Pemohon"
              subtitle="Klik untuk mengunduh atau memeriksa isi dokumen PDF"
            />
            <CardBody className="p-0">
              {documents.length === 0 ? (
                <div className="p-6 text-center text-xs text-slate-500">
                  Tidak ada dokumen yang diunggah oleh pemohon.
                </div>
              ) : (
                <div className="divide-y divide-slate-100">
                  {documents.map((doc) => (
                    <div key={doc.id} className="p-3.5 flex items-center justify-between gap-3 hover:bg-slate-50 transition">
                      <div className="flex items-center gap-2.5 truncate">
                        <PaperClipIcon className="w-4 h-4 text-blue-600 shrink-0" />
                        <span className="text-xs font-semibold text-slate-800 truncate">
                          {doc.original_filename || doc.document_type}
                        </span>
                      </div>
                      <Button
                        variant="outline"
                        size="xs"
                        onClick={async () => {
                          try {
                            await openPdf(`/proposals/${proposal.id}/documents/${doc.id}/download`);
                          } catch {
                            toast.error('Gagal membuka berkas dokumen.');
                          }
                        }}
                      >
                        Unduh
                      </Button>
                    </div>
                  ))}
                </div>
              )}
            </CardBody>
          </Card>
        </div>

        {/* RIGHT PANE (7 Cols): Administrative Checklist & Actions */}
        <div className="lg:col-span-7 space-y-6">
          <Card className="border-slate-200 shadow-sm">
            <CardHeader
              title="Lembar Checklist Kelengkapan Administrasi"
              subtitle="Tentukan status kesesuaian setiap butir persyaratan hukum"
            />
            <CardBody className="space-y-4">
              {items.length === 0 ? (
                <div className="space-y-3">
                  {[
                    'Surat Permohonan Hibah ditujukan kepada Gubernur c.q. Kepala Biro Kesra',
                    'Akta Pendirian dari Notaris dan bukti pengesahan Kemenkumham',
                    'Nomor Pokok Wajib Pajak (NPWP) atas nama lembaga yang bersangkutan',
                    'Surat Keterangan Domisili dari Lurah / Hukum Tua setempat',
                    'Rincian Anggaran Biaya (RAB) terperinci dan wajar',
                    'Surat Pernyataan Tanggung Jawab Mutlak (SPTJM)',
                  ].map((reqText, idx) => (
                    <div
                      key={idx}
                      className="p-4 rounded-xl border border-slate-200 bg-white space-y-3 shadow-2xs"
                    >
                      <div className="flex items-start justify-between gap-3">
                        <span className="text-xs font-bold text-slate-800 flex items-center gap-2">
                          <span className="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px]">
                            {idx + 1}
                          </span>
                          <span>{reqText}</span>
                        </span>
                      </div>

                      <div className="flex items-center gap-2 pt-1 border-t border-slate-100">
                        <button
                          type="button"
                          className="px-3 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-300 hover:bg-emerald-100 transition"
                        >
                          Sesuai (Valid)
                        </button>
                        <button
                          type="button"
                          className="px-3 py-1 rounded-lg text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-300 hover:bg-amber-100 transition"
                        >
                          Perlu Revisi
                        </button>
                        <button
                          type="button"
                          className="px-3 py-1 rounded-lg text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-300 hover:bg-rose-100 transition"
                        >
                          Tidak Sesuai
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="space-y-3">
                  {items.map((it, idx) => (
                    <div
                      key={it.id || idx}
                      className="p-4 rounded-xl border border-slate-200 bg-white space-y-3 shadow-2xs"
                    >
                      <div className="flex items-start justify-between gap-3">
                        <span className="text-xs font-bold text-slate-800 flex items-center gap-2">
                          <span className="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px]">
                            {idx + 1}
                          </span>
                          <span>{it.requirement_name || it.item_name || `Persyaratan Administrasi #${idx + 1}`}</span>
                        </span>
                      </div>

                      <div className="flex items-center gap-2">
                        <button
                          type="button"
                          onClick={() => handleItemToggle(it, 'valid')}
                          className={`px-3 py-1 rounded-lg text-xs font-semibold transition cursor-pointer ${
                            it.status === 'valid' || it.result === 'pass'
                              ? 'bg-emerald-600 text-white shadow-xs'
                              : 'bg-emerald-50 text-emerald-800 border border-emerald-300 hover:bg-emerald-100'
                          }`}
                        >
                          Sesuai (Valid)
                        </button>
                        <button
                          type="button"
                          onClick={() => handleItemToggle(it, 'revision_required')}
                          className={`px-3 py-1 rounded-lg text-xs font-semibold transition cursor-pointer ${
                            it.status === 'revision_required' || it.result === 'need_revision'
                              ? 'bg-amber-600 text-white shadow-xs'
                              : 'bg-amber-50 text-amber-800 border border-amber-300 hover:bg-amber-100'
                          }`}
                        >
                          Perlu Revisi
                        </button>
                        <button
                          type="button"
                          onClick={() => handleItemToggle(it, 'invalid')}
                          className={`px-3 py-1 rounded-lg text-xs font-semibold transition cursor-pointer ${
                            it.status === 'invalid' || it.result === 'fail'
                              ? 'bg-rose-600 text-white shadow-xs'
                              : 'bg-rose-50 text-rose-800 border border-rose-300 hover:bg-rose-100'
                          }`}
                        >
                          Tidak Sesuai
                        </button>
                      </div>

                      <input
                        type="text"
                        value={it.notes || ''}
                        onChange={(e) => handleItemNotesChange(it, e.target.value)}
                        onBlur={() => handleItemNotesBlur(it)}
                        placeholder="Catatan verifikator untuk butir ini (opsional)..."
                        className="w-full text-xs p-2 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500"
                      />
                    </div>
                  ))}
                </div>
              )}

              {/* Conclusion Selector */}
              <div className="pt-4 border-t border-slate-200 space-y-3">
                <label className="text-xs font-bold text-slate-800 block">
                  Kesimpulan Hasil Verifikasi Administrasi:
                </label>
                <div className="grid grid-cols-3 gap-3">
                  <button
                    type="button"
                    onClick={() => setConclusion('pass')}
                    className={`p-3 rounded-xl border text-xs font-bold text-center transition cursor-pointer ${
                      conclusion === 'pass'
                        ? 'border-emerald-500 bg-emerald-50 text-emerald-900 ring-2 ring-emerald-500'
                        : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                    }`}
                  >
                    Lolos Administrasi
                  </button>
                  <button
                    type="button"
                    onClick={() => setConclusion('revision_required')}
                    className={`p-3 rounded-xl border text-xs font-bold text-center transition cursor-pointer ${
                      conclusion === 'revision_required'
                        ? 'border-amber-500 bg-amber-50 text-amber-900 ring-2 ring-amber-500'
                        : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                    }`}
                  >
                    Minta Revisi Berkas
                  </button>
                  <button
                    type="button"
                    onClick={() => setConclusion('reject')}
                    className={`p-3 rounded-xl border text-xs font-bold text-center transition cursor-pointer ${
                      conclusion === 'reject'
                        ? 'border-rose-500 bg-rose-50 text-rose-900 ring-2 ring-rose-500'
                        : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                    }`}
                  >
                    Tolak Administrasi
                  </button>
                </div>

                <Textarea
                  label="Catatan Kesimpulan Berita Acara"
                  value={overallNotes}
                  onChange={(e) => setOverallNotes(e.target.value)}
                  placeholder="Catatan umum mengenai kelayakan dokumen usulan ini..."
                  rows={3}
                />
              </div>

              {/* Submit Final Action */}
              <div className="pt-2">
                <Button
                  variant="primary"
                  size="lg"
                  className="w-full font-bold shadow-md"
                  onClick={handleCompleteVerification}
                  isLoading={isCompleting}
                  icon={ShieldCheckIcon}
                >
                  Selesaikan & Terbitkan Berita Acara Verifikasi
                </Button>
              </div>
            </CardBody>
          </Card>
        </div>
      </div>
    </div>
  );
}

export default VerificationWorkspacePage;

