import React, { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import api from '../../services/api';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { SULUT_REGENCIES, ORGANIZATION_TYPES } from '../../utils/constants';
import { formatCurrency, formatDate } from '../../utils/formatters';
import RabBuilder from './components/RabBuilder';
import Card, { CardBody, CardHeader, CardFooter } from '../../components/ui/Card';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';
import Textarea from '../../components/ui/Textarea';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import Alert from '../../components/feedback/Alert';
import PageHeader from '../../components/layout/PageHeader';
import {
  DocumentPlusIcon,
  CheckIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  BuildingLibraryIcon,
  DocumentTextIcon,
  BanknotesIcon,
  PaperClipIcon,
  ShieldCheckIcon,
  SparklesIcon,
} from '@heroicons/react/24/outline';

export function ProposalWizardPage() {
  const navigate = useNavigate();
  const toast = useToast();
  const { user } = useAuth();

  const [currentStep, setCurrentStep] = useState(1);
  const [isLoading, setIsLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  // Step 1: Active Grant Programs
  const [programs, setPrograms] = useState([]);
  const [selectedProgram, setSelectedProgram] = useState(null);
  const [programsLoading, setProgramsLoading] = useState(true);

  // Available Organizations
  const [organizations, setOrganizations] = useState([]);

  // Form State
  const [formData, setFormData] = useState({
    // Step 1
    grant_program_id: '',
    // Step 2: Org Info
    organization_id: '',
    org_name: '',
    org_type: ORGANIZATION_TYPES[0],
    registration_number: '',
    npwp: '',
    bank_name: 'Bank SulutGo (BSG)',
    bank_account_number: '',
    bank_account_holder: '',
    regency: SULUT_REGENCIES[0],
    address: '',
    phone: '',
    // Step 3: Proposal Narrative
    title: '',
    background: '',
    objectives: '',
    benefits: '',
    activities: '',
    expected_outputs: '',
    // Step 4: Budget Items
    budget_items: [
      {
        id: '1',
        category: 'Bahan dan Material Kegiatan',
        item_name: 'Spanduk & Banner Sosialisasi',
        specification: 'Bahan Flexi 340gr, ukuran 3x1m',
        quantity: 5,
        unit: 'Pcs',
        unit_price: 150000,
        total_price: 750000,
      },
    ],
    // Step 5: Documents
    documents: {},
    statementAccepted: false,
  });

  // Load programs & existing user organization on mount
  useEffect(() => {
    let isMounted = true;
    async function loadPrerequisites() {
      setProgramsLoading(true);
      try {
        const [progRes, propRes] = await Promise.allSettled([
          api.get('/public/grant-programs'),
          api.get('/proposals'),
        ]);

        if (!isMounted) return;

        if (progRes.status === 'fulfilled' && progRes.value?.data) {
          const active = progRes.value.data.filter((p) => p.is_active);
          setPrograms(active);
          if (active.length > 0) {
            setSelectedProgram(active[0]);
            setFormData((prev) => ({ ...prev, grant_program_id: active[0].id }));
          }
        }

        // Check if user has an existing organization from profile or prior proposals
        let foundOrg = null;
        if (user?.organizations && user.organizations.length > 0) {
          foundOrg = user.organizations[0];
          setOrganizations(user.organizations);
        } else if (propRes.status === 'fulfilled' && propRes.value?.data && propRes.value.data.length > 0) {
          foundOrg = propRes.value.data[0]?.organization;
          if (foundOrg) {
            setOrganizations([foundOrg]);
          }
        }

        if (foundOrg) {
          setFormData((prev) => ({
            ...prev,
            organization_id: foundOrg.id,
            org_name: foundOrg.name || prev.org_name,
            registration_number: foundOrg.registration_number || prev.registration_number,
            org_type: foundOrg.organization_type || prev.org_type,
            address: foundOrg.address || prev.address,
            phone: foundOrg.phone || prev.phone,
          }));
        }
      } catch (err) {
        console.error('Failed to load wizard data:', err);
      } finally {
        if (isMounted) setProgramsLoading(false);
      }
    }

    loadPrerequisites();
    return () => { isMounted = false; };
  }, [user]);

  const handleProgramSelect = (prog) => {
    setSelectedProgram(prog);
    setFormData((prev) => ({ ...prev, grant_program_id: prog.id }));
  };

  const handleFieldChange = (field, val) => {
    setFormData((prev) => ({ ...prev, [field]: val }));
  };

  const handleFileChange = (key, file) => {
    setFormData((prev) => ({
      ...prev,
      documents: {
        ...prev.documents,
        [key]: file,
      },
    }));
  };

  // Step Validation before progressing
  const validateStep = (step) => {
    setErrorMsg('');
    if (step === 1) {
      if (!formData.grant_program_id) {
        setErrorMsg('Silakan pilih salah satu program hibah aktif.');
        return false;
      }
    }
    if (step === 2) {
      if (!formData.org_name.trim()) {
        setErrorMsg('Nama organisasi / lembaga wajib diisi.');
        return false;
      }
    }
    if (step === 3) {
      if (!formData.title.trim()) {
        setErrorMsg('Judul usulan proposal wajib diisi.');
        return false;
      }
    }
    if (step === 4) {
      if (formData.budget_items.length === 0) {
        setErrorMsg('Rincian Anggaran Biaya (RAB) minimal memiliki 1 item.');
        return false;
      }
    }
    if (step === 5) {
      if (!formData.statementAccepted) {
        setErrorMsg('Anda wajib menyetujui pernyataan keabsahan dokumen sebelum mengajukan usulan.');
        return false;
      }
    }
    return true;
  };

  const handleNext = () => {
    if (validateStep(currentStep)) {
      setCurrentStep((prev) => Math.min(5, prev + 1));
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  const handleBack = () => {
    setErrorMsg('');
    setCurrentStep((prev) => Math.max(1, prev - 1));
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleSubmitProposal = async () => {
    if (!validateStep(5)) return;

    setIsLoading(true);
    setErrorMsg('');

    try {
      // Step A: Submit Proposal Details
      // Ensure organization_id is available (or use existing/fallback)
      let orgId = formData.organization_id;
      if (!orgId && organizations.length > 0) {
        orgId = organizations[0].id;
      }
      if (!orgId && user?.organizations?.length > 0) {
        orgId = user.organizations[0].id;
      }

      const budgetItems = (formData.budget_items || []).map((it, idx) => ({
        category: it.category || 'Bahan dan Material Kegiatan',
        item_name: it.item_name || `Item ${idx + 1}`,
        description: it.specification || it.description || null,
        quantity: parseFloat(it.quantity) || 1,
        unit: it.unit || 'Unit',
        unit_price: parseFloat(it.unit_price) || 0,
        sort_order: idx,
      }));

      const payload = {
        grant_program_id: formData.grant_program_id,
        organization_id: orgId || 'e6ec21b5-b930-484a-b957-a7d28031173d', // default seeded ormas if new
        title: formData.title,
        background: formData.background || 'Latar belakang pelaksanaan kegiatan di Sulawesi Utara.',
        objectives: formData.objectives || 'Meningkatkan partisipasi dan kemandirian masyarakat.',
        benefits: formData.benefits || 'Masyarakat luas di daerah penerima.',
        activities: formData.activities || 'Sosialisasi, pengadaan barang, pelatihan teknis.',
        expected_outputs: formData.expected_outputs || 'Tersedianya sarana prasarana penunjang kegiatan.',
        org_name: formData.org_name,
        org_type: formData.org_type,
        registration_number: formData.registration_number,
        budget_items: budgetItems,
      };

      if (orgId) {
        payload.organization_id = orgId;
      }

      const res = await api.post('/proposals', payload);
      const createdProposal = res?.data;
      const proposalId = createdProposal?.id;

      if (!proposalId) {
        throw new Error('Gagal menerima identitas usulan yang dibuat.');
      }

      // Step B: Submit uploaded documents if any
      const docEntries = Object.entries(formData.documents);
      for (const [key, file] of docEntries) {
        if (file) {
          const docForm = new FormData();
          docForm.append('document', file);
          docForm.append('document_type', key);
          try {
            await api.post(`/proposals/${proposalId}/documents`, docForm);
          } catch {
            // Non-blocking for single document
          }
        }
      }

      // Step C: Officially submit proposal for verification workflow
      try {
        await api.post(`/proposals/${proposalId}/submit`);
      } catch {
        // If already submitted or transitions automatically
      }

      toast.success('Usulan proposal hibah Anda berhasil diajukan!');
      navigate(`/proposals/${proposalId}`);
    } catch (err) {
      setErrorMsg(err.message || 'Terjadi kesalahan saat mengirimkan usulan.');
    } finally {
      setIsLoading(false);
    }
  };

  const steps = [
    { num: 1, title: 'Pilih Program' },
    { num: 2, title: 'Profil Lembaga' },
    { num: 3, title: 'Narasi Usulan' },
    { num: 4, title: 'Rincian RAB' },
    { num: 5, title: 'Dokumen & Kirim' },
  ];

  return (
    <div className="max-w-4xl mx-auto space-y-8 pb-16">
      <PageHeader
        title="Pengajuan Usulan Hibah Daerah Baru"
        subtitle="Selesaikan 5 langkah berikut untuk mengajukan permohonan bantuan hibah ke Pemerintah Provinsi Sulawesi Utara"
        breadcrumbs={[
          { label: 'Daftar Usulan', to: '/proposals' },
          { label: 'Buat Usulan Baru' },
        ]}
      />

      {/* Stepper Progress Indicator */}
      <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
        <div className="grid grid-cols-5 gap-2">
          {steps.map((step) => {
            const isDone = currentStep > step.num;
            const isCurrent = currentStep === step.num;

            return (
              <div key={step.num} className="flex flex-col items-center text-center gap-1.5">
                <div
                  className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all ${
                    isDone
                      ? 'bg-emerald-600 text-white'
                      : isCurrent
                      ? 'bg-blue-600 text-white ring-4 ring-blue-100'
                      : 'bg-slate-100 text-slate-400'
                  }`}
                >
                  {isDone ? <CheckIcon className="w-4 h-4 stroke-3" /> : step.num}
                </div>
                <span className={`text-[11px] font-semibold truncate w-full ${isCurrent ? 'text-blue-600' : 'text-slate-500'}`}>
                  {step.title}
                </span>
              </div>
            );
          })}
        </div>
      </div>

      {errorMsg && <Alert type="danger">{errorMsg}</Alert>}

      {/* STEP 1: PROGRAM SELECTION */}
      {currentStep === 1 && (
        <Card className="border-slate-200 shadow-sm">
          <CardHeader
            title="Langkah 1: Pilih Program Hibah Aktif"
            subtitle="Pilih kategori program bantuan hibah yang sesuai dengan bidang kegiatan organisasi Anda"
          />
          <CardBody className="space-y-4">
            {programsLoading ? (
              <Spinner label="Memuat program hibah aktif..." />
            ) : programs.length === 0 ? (
              <div className="p-8 text-center text-xs text-slate-500">
                Saat ini belum ada program hibah aktif yang dibuka.
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {programs.map((prog) => {
                  const isSelected = selectedProgram?.id === prog.id;
                  return (
                    <div
                      key={prog.id}
                      onClick={() => handleProgramSelect(prog)}
                      className={`p-5 rounded-2xl border-2 transition-all cursor-pointer flex flex-col justify-between ${
                        isSelected
                          ? 'border-blue-600 bg-blue-50/50 shadow-md'
                          : 'border-slate-200 hover:border-slate-300 bg-white'
                      }`}
                    >
                      <div className="space-y-2">
                        <div className="flex items-center justify-between">
                          <span className="font-mono text-xs font-bold px-2 py-0.5 rounded bg-blue-100 text-blue-800">
                            {prog.code}
                          </span>
                          <span className="text-[11px] font-bold text-emerald-700">T.A. {prog.fiscal_year}</span>
                        </div>
                        <h4 className="text-sm font-bold text-slate-900 leading-snug">{prog.name}</h4>
                        <p className="text-xs text-slate-500 line-clamp-2">{prog.description}</p>
                      </div>

                      <div className="mt-4 pt-3 border-t border-slate-100 text-xs flex items-center justify-between">
                        <span className="text-slate-400">Maksimum Pagu:</span>
                        <span className="font-bold font-mono text-emerald-700">
                          {formatCurrency(prog.maximum_amount)}
                        </span>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </CardBody>
        </Card>
      )}

      {/* STEP 2: ORGANIZATION PROFILE */}
      {currentStep === 2 && (
        <Card className="border-slate-200 shadow-sm">
          <CardHeader
            title="Langkah 2: Data Legalitas Organisasi Pemohon"
            subtitle="Informasi profil hukum, nomor rekening bank, dan domisili sekretariat di Provinsi Sulawesi Utara"
          />
          <CardBody className="space-y-4">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <Input
                label="Nama Resmi Organisasi / Lembaga"
                value={formData.org_name}
                onChange={(e) => handleFieldChange('org_name', e.target.value)}
                placeholder="Contoh: Yayasan Sam Ratulangi Peduli"
                required
              />
              <Select
                label="Bentuk Badan Hukum / Tipe Organisasi"
                value={formData.org_type}
                onChange={(e) => handleFieldChange('org_type', e.target.value)}
                options={ORGANIZATION_TYPES}
                required
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <Input
                label="Nomor SK Kemenkumham / Izin Operasional"
                value={formData.registration_number}
                onChange={(e) => handleFieldChange('registration_number', e.target.value)}
                placeholder="AHU-0012948.AH.01.04.2021"
              />
              <Input
                label="Nomor Pokok Wajib Pajak (NPWP)"
                value={formData.npwp}
                onChange={(e) => handleFieldChange('npwp', e.target.value)}
                placeholder="00.000.000.0-000.000"
                mono
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <Input
                label="Nama Bank Rekening Resmi"
                value={formData.bank_name}
                onChange={(e) => handleFieldChange('bank_name', e.target.value)}
                placeholder="Bank SulutGo / BRI / Mandiri"
                required
              />
              <Input
                label="Nomor Rekening Bank"
                value={formData.bank_account_number}
                onChange={(e) => handleFieldChange('bank_account_number', e.target.value)}
                placeholder="001020304050"
                required
                mono
              />
              <Input
                label="Nama Pemilik Rekening (Sesuai Buku Tabungan)"
                value={formData.bank_account_holder}
                onChange={(e) => handleFieldChange('bank_account_holder', e.target.value)}
                placeholder="Atas Nama Organisasi"
                required
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <Select
                label="Kabupaten / Kota di Sulawesi Utara"
                value={formData.regency}
                onChange={(e) => handleFieldChange('regency', e.target.value)}
                options={SULUT_REGENCIES}
                required
              />
              <Input
                label="Nomor Telepon / WhatsApp Sekretariat"
                value={formData.phone}
                onChange={(e) => handleFieldChange('phone', e.target.value)}
                placeholder="0812-3456-7890"
              />
            </div>

            <Textarea
              label="Alamat Lengkap Kantor Sekretariat"
              value={formData.address}
              onChange={(e) => handleFieldChange('address', e.target.value)}
              placeholder="Jl. 17 Agustus, Kelurahan Wenang, Kota Manado..."
              rows={2}
            />
          </CardBody>
        </Card>
      )}

      {/* STEP 3: PROPOSAL NARRATIVE */}
      {currentStep === 3 && (
        <Card className="border-slate-200 shadow-sm">
          <CardHeader
            title="Langkah 3: Narasi Substansi & Rencana Kegiatan"
            subtitle="Uraikan urgensi kegiatan, sasaran penerima manfaat, dan indikator keberhasilan program"
          />
          <CardBody className="space-y-4">
            <Input
              label="Judul Usulan Proposal Kegiatan"
              value={formData.title}
              onChange={(e) => handleFieldChange('title', e.target.value)}
              placeholder="Contoh: Penguatan Kapasitas Vokasi Pemuda dan Pelatihan Digital Kreatif Minahasa 2026"
              required
            />

            <Textarea
              label="Latar Belakang Permasalahan & Urgensi"
              value={formData.background}
              onChange={(e) => handleFieldChange('background', e.target.value)}
              placeholder="Jelaskan kondisi faktual di lapangan mengapa kegiatan ini perlu didukung dana hibah..."
              rows={3}
              required
            />

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <Textarea
                label="Maksud dan Tujuan Kegiatan"
                value={formData.objectives}
                onChange={(e) => handleFieldChange('objectives', e.target.value)}
                placeholder="1. Meningkatkan keterampilan 100 pemuda lokal..."
                rows={3}
              />
              <Textarea
                label="Manfaat yang Dihasilkan Bagi Masyarakat"
                value={formData.benefits}
                onChange={(e) => handleFieldChange('benefits', e.target.value)}
                placeholder="Terbukanya lapangan kerja mandiri dan bertambahnya pendapatan keluarga..."
                rows={3}
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <Textarea
                label="Tahapan Rangkaian Pelaksanaan"
                value={formData.activities}
                onChange={(e) => handleFieldChange('activities', e.target.value)}
                placeholder="Persiapan modul, pelatihan 3 batch, uji kompetensi, pameran hasil..."
                rows={3}
              />
              <Textarea
                label="Target Luaran / Output yang Diharapkan"
                value={formData.expected_outputs}
                onChange={(e) => handleFieldChange('expected_outputs', e.target.value)}
                placeholder="100 sertifikat kompetensi terbit, terbentuk 10 kelompok wirausaha..."
                rows={3}
              />
            </div>
          </CardBody>
        </Card>
      )}

      {/* STEP 4: DYNAMIC RAB BUILDER */}
      {currentStep === 4 && (
        <Card className="border-slate-200 shadow-sm">
          <CardHeader
            title="Langkah 4: Penyusunan Rencana Anggaran Biaya (RAB)"
            subtitle="Susun item pengeluaran secara rinci, proporsional, dan sesuai dengan standar biaya umum"
          />
          <CardBody>
            <RabBuilder
              items={formData.budget_items}
              onChange={(updated) => handleFieldChange('budget_items', updated)}
              maxBudget={selectedProgram?.maximum_amount}
            />
          </CardBody>
        </Card>
      )}

      {/* STEP 5: DOCUMENT UPLOAD & FINAL SUBMIT */}
      {currentStep === 5 && (
        <Card className="border-slate-200 shadow-sm">
          <CardHeader
            title="Langkah 5: Unggah Dokumen Legalitas & Pernyataan Sah"
            subtitle="Lampirkan berkas persyaratan resmi dalam format PDF (maksimal 5MB per berkas)"
          />
          <CardBody className="space-y-6">
            <div className="space-y-3">
              {[
                { key: 'surat_permohonan', label: 'Surat Permohonan Hibah Resmi (Bertanda Tangan & Cap)' },
                { key: 'akta_notaris', label: 'Akta Notaris Pendirian & Perubahan Terakhir' },
                { key: 'sk_kemenkumham', label: 'SK Pengesahan Kemenkumham / Izin Operasional Instansi' },
                { key: 'npwp_ormas', label: 'Kartu NPWP Organisasi' },
                { key: 'rekening_bank', label: 'Buku Rekening Bank Atas Nama Lembaga' },
                { key: 'surat_domisili', label: 'Surat Keterangan Domisili dari Lurah / Hukum Tua' },
              ].map((doc) => {
                const currentFile = formData.documents[doc.key];
                return (
                  <div
                    key={doc.key}
                    className="p-3.5 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50"
                  >
                    <div>
                      <span className="text-xs font-bold text-slate-800 block">{doc.label}</span>
                      <span className="text-[11px] text-slate-400">Wajib format PDF asli</span>
                    </div>

                    <div className="flex items-center gap-2 shrink-0">
                      <label className="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-xs font-semibold text-slate-700 transition">
                        <PaperClipIcon className="w-3.5 h-3.5 text-slate-500" />
                        <span>{currentFile ? currentFile.name : 'Pilih Berkas PDF'}</span>
                        <input
                          type="file"
                          accept=".pdf"
                          className="hidden"
                          onChange={(e) => handleFileChange(doc.key, e.target.files?.[0] || null)}
                        />
                      </label>
                      {currentFile && (
                        <button
                          type="button"
                          onClick={() => handleFileChange(doc.key, null)}
                          className="text-xs text-rose-600 hover:underline px-1"
                        >
                          Hapus
                        </button>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Legal Statement Checkbox */}
            <div className="p-4 rounded-xl bg-blue-50 border border-blue-200 space-y-2">
              <label className="flex items-start gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  checked={formData.statementAccepted}
                  onChange={(e) => handleFieldChange('statementAccepted', e.target.checked)}
                  className="mt-0.5 rounded-sm border-blue-300 text-blue-600 focus:ring-blue-500"
                />
                <span className="text-xs text-blue-950 font-medium leading-relaxed">
                  Saya menyatakan dengan sesungguhnya bahwa seluruh data profil, narasi usulan kegiatan, rincian anggaran biaya (RAB), dan lampiran dokumen yang diunggah adalah sah, benar, dan dapat dipertanggungjawabkan di hadapan hukum Pemerintah Provinsi Sulawesi Utara.
                </span>
              </label>
            </div>
          </CardBody>
        </Card>
      )}

      {/* Wizard Footer Navigation Controls */}
      <div className="flex items-center justify-between gap-4 pt-4 border-t border-slate-200">
        <div>
          {currentStep > 1 && (
            <Button
              variant="outline"
              size="md"
              icon={ChevronLeftIcon}
              onClick={handleBack}
              disabled={isLoading}
            >
              Langkah Sebelumnya
            </Button>
          )}
        </div>

        <div>
          {currentStep < 5 ? (
            <Button
              variant="primary"
              size="md"
              icon={ChevronRightIcon}
              iconPosition="right"
              onClick={handleNext}
            >
              Lanjut ke Langkah {currentStep + 1}
            </Button>
          ) : (
            <Button
              variant="success"
              size="lg"
              icon={SparklesIcon}
              onClick={handleSubmitProposal}
              isLoading={isLoading}
              className="font-bold shadow-lg shadow-emerald-600/20"
            >
              Ajukan Usulan Proposal Resmi
            </Button>
          )}
        </div>
      </div>
    </div>
  );
}

export default ProposalWizardPage;

