import { useState, useEffect } from 'react';
import api from '../../services/api';
import Card from '../../components/ui/Card';
import Badge from '../../components/ui/Badge';
import Button from '../../components/ui/Button';
import Modal from '../../components/ui/Modal';
import Textarea from '../../components/ui/Textarea';
import Input from '../../components/ui/Input';
import Table from '../../components/ui/Table';
import Spinner from '../../components/feedback/Spinner';
import Alert from '../../components/feedback/Alert';
import EmptyState from '../../components/feedback/EmptyState';
import { useToast } from '../../context/ToastContext';
import { useAuth } from '../../context/AuthContext';
import { formatDateIndo } from '../../utils/formatters';
import {
  Cog6ToothIcon,
  DocumentDuplicateIcon,
  CheckCircleIcon,
  ArrowPathIcon,
  EyeIcon,
  PlusIcon,
  ShieldCheckIcon,
  ClockIcon
} from '@heroicons/react/24/outline';

export default function PolicyConfigurationPage() {
  const { toast } = useToast();
  const { isSuperAdmin } = useAuth();
  
  const [configs, setConfigs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedConfig, setSelectedConfig] = useState(null);
  const [versions, setVersions] = useState([]);
  const [loadingVersions, setLoadingVersions] = useState(false);
  
  // Modals
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showJsonModal, setShowJsonModal] = useState(false);
  const [activeJsonView, setActiveJsonView] = useState(null);
  
  // Draft Form State
  const [draftData, setDraftData] = useState({
    version_number: '',
    effective_from: '',
    effective_until: '',
    configuration_data: '{}'
  });
  const [submittingDraft, setSubmittingDraft] = useState(false);
  const [formError, setFormError] = useState('');

  // Approve / Action states
  const [actionLoading, setActionLoading] = useState({});

  const fetchConfigs = async () => {
    setLoading(true);
    try {
      const res = await api.get('/policy-configurations');
      const data = res.data.data || res.data || [];
      setConfigs(data);
      if (data.length > 0 && !selectedConfig) {
        handleSelectConfig(data[0]);
      }
    } catch (err) {
      toast.error('Gagal memuat konfigurasi kebijakan: ' + (err.response?.data?.message || err.message));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchConfigs();
  }, []);

  const handleSelectConfig = async (config) => {
    setSelectedConfig(config);
    setLoadingVersions(true);
    try {
      const res = await api.get(`/policy-configurations/${config.code}/versions`);
      setVersions(res.data.data || res.data || []);
    } catch (err) {
      toast.error('Gagal memuat riwayat versi: ' + (err.response?.data?.message || err.message));
    } finally {
      setLoadingVersions(false);
    }
  };

  const handleOpenCreateDraft = () => {
    setFormError('');
    // Prefill with active or latest version data
    const latestVersion = versions[0] || {};
    setDraftData({
      version_number: `v${(versions.length + 1)}.0`,
      effective_from: new Date().toISOString().split('T')[0],
      effective_until: '',
      configuration_data: JSON.stringify(latestVersion.configuration_data || selectedConfig?.default_configuration || {}, null, 2)
    });
    setShowCreateModal(true);
  };

  const handleSaveDraft = async (e) => {
    e.preventDefault();
    setFormError('');

    let parsedConfig = {};
    try {
      parsedConfig = JSON.parse(draftData.configuration_data);
    } catch (err) {
      setFormError('Format JSON tidak valid: ' + err.message);
      return;
    }

    setSubmittingDraft(true);
    try {
      await api.post(`/policy-configurations/${selectedConfig.code}/versions`, {
        version_number: draftData.version_number,
        effective_from: draftData.effective_from || null,
        effective_until: draftData.effective_until || null,
        configuration_data: parsedConfig
      });
      toast.success('Draft versi kebijakan berhasil dibuat!');
      setShowCreateModal(false);
      handleSelectConfig(selectedConfig);
      fetchConfigs();
    } catch (err) {
      setFormError(err.response?.data?.message || err.message);
    } finally {
      setSubmittingDraft(false);
    }
  };

  const handleApproveVersion = async (versionId) => {
    setActionLoading(prev => ({ ...prev, [versionId]: true }));
    try {
      await api.post(`/policy-configurations/versions/${versionId}/approve`, {
        notes: 'Disetujui melalui Maker-Checker Governance Console'
      });
      toast.success('Versi kebijakan berhasil disetujui (APPROVED)');
      handleSelectConfig(selectedConfig);
    } catch (err) {
      toast.error('Gagal menyetujui versi: ' + (err.response?.data?.message || err.message));
    } finally {
      setActionLoading(prev => ({ ...prev, [versionId]: false }));
    }
  };

  const handleActivateVersion = async (versionId) => {
    setActionLoading(prev => ({ ...prev, [versionId]: true }));
    try {
      await api.post(`/policy-configurations/versions/${versionId}/activate`);
      toast.success('Versi kebijakan sekarang AKTIF dan berlaku dalam sistem!');
      handleSelectConfig(selectedConfig);
      fetchConfigs();
    } catch (err) {
      toast.error('Gagal mengaktifkan versi: ' + (err.response?.data?.message || err.message));
    } finally {
      setActionLoading(prev => ({ ...prev, [versionId]: false }));
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <Cog6ToothIcon className="w-7 h-7 text-gov-navy" />
            Konfigurasi Kebijakan & Tata Kelola
          </h1>
          <p className="text-sm text-slate-500 mt-1">
            Manajemen parameter scoring rubrik, ambang batas anggaran, dan audit trail Maker-Checker Pemprov Sulut.
          </p>
        </div>
        {selectedConfig && (
          <Button
            onClick={handleOpenCreateDraft}
            leftIcon={<PlusIcon className="w-4 h-4" />}
          >
            Buat Versi Baru
          </Button>
        )}
      </div>

      {loading ? (
        <div className="flex justify-center p-12">
          <Spinner size="lg" />
        </div>
      ) : configs.length === 0 ? (
        <Card>
          <EmptyState
            title="Tidak Ada Konfigurasi Kebijakan"
            description="Belum ada parameter kebijakan yang terdaftar dalam basis data."
          />
        </Card>
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Configs List Sidebar */}
          <div className="space-y-3">
            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400 px-1">
              Daftar Domain Kebijakan
            </h3>
            {configs.map((cfg) => {
              const isSelected = selectedConfig?.id === cfg.id;
              const activeVer = cfg.versions?.find(v => v.is_active);
              return (
                <div
                  key={cfg.id}
                  onClick={() => handleSelectConfig(cfg)}
                  className={`p-4 rounded-xl border cursor-pointer transition-all ${
                    isSelected
                      ? 'border-gov-navy bg-gov-navy/5 shadow-sm'
                      : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'
                  }`}
                >
                  <div className="flex items-start justify-between">
                    <div>
                      <span className="text-xs font-mono font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                        {cfg.code}
                      </span>
                      <h4 className="font-semibold text-slate-900 mt-1.5 text-sm">
                        {cfg.name}
                      </h4>
                    </div>
                    {activeVer && (
                      <Badge variant="success" size="sm">
                        {activeVer.version_number || 'v1.0'} Aktif
                      </Badge>
                    )}
                  </div>
                  <p className="text-xs text-slate-500 mt-2 line-clamp-2">
                    {cfg.description || 'Tidak ada keterangan spesifik.'}
                  </p>
                  <div className="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                    <span>{cfg.versions?.length || 0} riwayat revisi</span>
                    <span>Tipe: {cfg.category || 'General'}</span>
                  </div>
                </div>
              );
            })}
          </div>

          {/* Config Details & Version History */}
          <div className="lg:col-span-2 space-y-6">
            {selectedConfig ? (
              <>
                {/* Active Info Card */}
                <Card>
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                      <div className="flex items-center gap-2">
                        <span className="text-xs font-mono font-bold px-2 py-0.5 rounded bg-gov-gold/20 text-gov-gold">
                          {selectedConfig.code}
                        </span>
                        <h2 className="text-lg font-bold text-slate-900">
                          {selectedConfig.name}
                        </h2>
                      </div>
                      <p className="text-xs text-slate-500 mt-1">
                        {selectedConfig.description}
                      </p>
                    </div>
                    <Button
                      size="sm"
                      variant="outline"
                      onClick={() => handleSelectConfig(selectedConfig)}
                      leftIcon={<ArrowPathIcon className="w-3.5 h-3.5" />}
                    >
                      Segarkan
                    </Button>
                  </div>

                  <div className="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                    <div className="p-3 bg-slate-50 rounded-lg">
                      <span className="text-slate-400 block">Kategori</span>
                      <span className="font-semibold text-slate-800 uppercase">{selectedConfig.category || 'REGULATION'}</span>
                    </div>
                    <div className="p-3 bg-slate-50 rounded-lg">
                      <span className="text-slate-400 block">Status Penguncian</span>
                      <span className="font-semibold text-slate-800">
                        {selectedConfig.is_locked ? 'Terkunci (System)' : 'Dapat Diubah'}
                      </span>
                    </div>
                    <div className="p-3 bg-slate-50 rounded-lg">
                      <span className="text-slate-400 block">Total Versi</span>
                      <span className="font-semibold text-slate-800">{versions.length} Versi</span>
                    </div>
                    <div className="p-3 bg-slate-50 rounded-lg">
                      <span className="text-slate-400 block">Model Maker-Checker</span>
                      <span className="font-semibold text-emerald-600 flex items-center gap-1">
                        <ShieldCheckIcon className="w-3.5 h-3.5" /> 2-Tier Sign
                      </span>
                    </div>
                  </div>
                </Card>

                {/* Versions Table */}
                <Card>
                  <div className="flex items-center justify-between mb-4">
                    <div>
                      <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <ClockIcon className="w-4 h-4 text-slate-500" />
                        Riwayat Versi & Audit Maker-Checker
                      </h3>
                      <p className="text-xs text-slate-500 mt-0.5">
                        Prinsip Maker-Checker: pembuat draft tidak dapat menyetujui versi sendiri.
                      </p>
                    </div>
                  </div>

                  {loadingVersions ? (
                    <div className="flex justify-center p-8">
                      <Spinner />
                    </div>
                  ) : versions.length === 0 ? (
                    <EmptyState
                      title="Belum Ada Versi"
                      description="Belum ada riwayat versi untuk konfigurasi kebijakan ini."
                    />
                  ) : (
                    <div className="overflow-x-auto">
                      <Table>
                        <thead>
                          <tr className="border-b border-slate-200 text-left text-xs font-semibold text-slate-500">
                            <th className="pb-3 px-3">Versi</th>
                            <th className="pb-3 px-3">Status</th>
                            <th className="pb-3 px-3">Masa Berlaku</th>
                            <th className="pb-3 px-3">Pembuat (Maker)</th>
                            <th className="pb-3 px-3">Penyetuju (Checker)</th>
                            <th className="pb-3 px-3 text-right">Aksi</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 text-xs">
                          {versions.map((ver) => {
                            const isBusy = actionLoading[ver.id];
                            return (
                              <tr key={ver.id} className="hover:bg-slate-50/70">
                                <td className="py-3 px-3">
                                  <div className="font-mono font-bold text-slate-900">
                                    {ver.version_number || `v${ver.id}`}
                                  </div>
                                  <div className="text-[10px] text-slate-400">
                                    {formatDateIndo(ver.created_at)}
                                  </div>
                                </td>
                                <td className="py-3 px-3">
                                  {ver.is_active ? (
                                    <Badge variant="success" size="sm">AKTIF</Badge>
                                  ) : ver.status === 'APPROVED' ? (
                                    <Badge variant="info" size="sm">APPROVED</Badge>
                                  ) : ver.status === 'DRAFT' ? (
                                    <Badge variant="warning" size="sm">DRAFT</Badge>
                                  ) : (
                                    <Badge variant="default" size="sm">{ver.status || 'ARCHIVED'}</Badge>
                                  )}
                                </td>
                                <td className="py-3 px-3">
                                  <div>
                                    {ver.effective_from ? formatDateIndo(ver.effective_from) : 'Langsung'}
                                  </div>
                                  <div className="text-[10px] text-slate-400">
                                    s.d. {ver.effective_until ? formatDateIndo(ver.effective_until) : 'Seterusnya'}
                                  </div>
                                </td>
                                <td className="py-3 px-3">
                                  <span className="font-medium text-slate-700">
                                    {ver.creator?.name || 'System'}
                                  </span>
                                </td>
                                <td className="py-3 px-3">
                                  <span className="text-slate-600">
                                    {ver.approver?.name || '-'}
                                  </span>
                                </td>
                                <td className="py-3 px-3 text-right space-x-1">
                                  <Button
                                    size="xs"
                                    variant="outline"
                                    onClick={() => {
                                      setActiveJsonView({
                                        title: `${selectedConfig.name} (${ver.version_number})`,
                                        data: ver.configuration_data
                                      });
                                      setShowJsonModal(true);
                                    }}
                                    leftIcon={<EyeIcon className="w-3 h-3" />}
                                  >
                                    JSON
                                  </Button>

                                  {/* Maker-Checker Approve */}
                                  {ver.status === 'DRAFT' && isSuperAdmin && (
                                    <Button
                                      size="xs"
                                      variant="success"
                                      loading={isBusy}
                                      onClick={() => handleApproveVersion(ver.id)}
                                      leftIcon={<CheckCircleIcon className="w-3 h-3" />}
                                    >
                                      Approve
                                    </Button>
                                  )}

                                  {/* Activate Version */}
                                  {ver.status === 'APPROVED' && !ver.is_active && isSuperAdmin && (
                                    <Button
                                      size="xs"
                                      variant="primary"
                                      loading={isBusy}
                                      onClick={() => handleActivateVersion(ver.id)}
                                    >
                                      Aktifkan
                                    </Button>
                                  )}
                                </td>
                              </tr>
                            );
                          })}
                        </tbody>
                      </Table>
                    </div>
                  )}
                </Card>
              </>
            ) : (
              <Card>
                <div className="p-8 text-center text-slate-400">
                  Pilih konfigurasi kebijakan di sebelah kiri untuk melihat detail versi.
                </div>
              </Card>
            )}
          </div>
        </div>
      )}

      {/* JSON Viewer Modal */}
      <Modal
        isOpen={showJsonModal}
        onClose={() => setShowJsonModal(false)}
        title={activeJsonView?.title || 'Data Konfigurasi JSON'}
        size="lg"
      >
        <div className="space-y-4">
          <pre className="p-4 bg-slate-900 text-emerald-400 font-mono text-xs rounded-lg overflow-x-auto max-h-96 border border-slate-800">
            {JSON.stringify(activeJsonView?.data, null, 2)}
          </pre>
          <div className="flex justify-end">
            <Button variant="outline" onClick={() => setShowJsonModal(false)}>
              Tutup
            </Button>
          </div>
        </div>
      </Modal>

      {/* Create Draft Modal */}
      <Modal
        isOpen={showCreateModal}
        onClose={() => setShowCreateModal(false)}
        title={`Buat Draft Versi Baru: ${selectedConfig?.name}`}
        size="lg"
      >
        <form onSubmit={handleSaveDraft} className="space-y-4">
          {formError && <Alert variant="danger">{formError}</Alert>}

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <Input
              label="Nomor Versi"
              value={draftData.version_number}
              onChange={(e) => setDraftData(prev => ({ ...prev, version_number: e.target.value }))}
              placeholder="Contoh: v2.0"
              required
            />
            <Input
              type="date"
              label="Berlaku Mulai"
              value={draftData.effective_from}
              onChange={(e) => setDraftData(prev => ({ ...prev, effective_from: e.target.value }))}
            />
            <Input
              type="date"
              label="Berlaku Sampai"
              value={draftData.effective_until}
              onChange={(e) => setDraftData(prev => ({ ...prev, effective_until: e.target.value }))}
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">
              Parameter JSON Kebijakan <span className="text-red-500">*</span>
            </label>
            <p className="text-[11px] text-slate-500 mb-2">
              Pastikan format JSON valid. Berisi bobot kriteria, formula TAPD, batasan nominal, atau parameter penomoran.
            </p>
            <Textarea
              rows={12}
              value={draftData.configuration_data}
              onChange={(e) => setDraftData(prev => ({ ...prev, configuration_data: e.target.value }))}
              className="font-mono text-xs text-slate-800"
              required
            />
          </div>

          <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
            <Button
              type="button"
              variant="outline"
              onClick={() => setShowCreateModal(false)}
              disabled={submittingDraft}
            >
              Batal
            </Button>
            <Button
              type="submit"
              loading={submittingDraft}
              leftIcon={<DocumentDuplicateIcon className="w-4 h-4" />}
            >
              Simpan Sebagai Draft
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  );
}

