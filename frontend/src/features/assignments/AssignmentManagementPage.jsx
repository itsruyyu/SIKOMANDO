import React, { useState, useEffect } from 'react';
import api from '../../services/api';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Table, {
  TableHead,
  TableBody,
  TableRow,
  TableHeaderCell,
  TableCell,
} from '../../components/ui/Table';
import Badge from '../../components/ui/Badge';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import EmptyState from '../../components/feedback/EmptyState';
import { useToast } from '../../context/ToastContext';
import { formatDate } from '../../utils/formatters';
import {
  UserGroupIcon,
  PlusIcon,
  MagnifyingGlassIcon,
  XMarkIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ArrowPathIcon,
} from '@heroicons/react/24/outline';

const ASSIGNMENT_TYPES = [
  { value: 'VERIFICATION', label: 'Verifikasi Administrasi', roleCode: 'VERIFIKATOR' },
  { value: 'EVALUATION', label: 'Evaluasi Substantif', roleCode: 'EVALUATOR' },
  { value: 'FIELD_SURVEY', label: 'Survei Lapangan & GPS', roleCode: 'SURVEYOR' },
];

export default function AssignmentManagementPage() {
  const { addToast } = useToast();

  const [assignments, setAssignments] = useState([]);
  const [workload, setWorkload] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [typeFilter, setTypeFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');

  // Modal Create
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [proposals, setProposals] = useState([]);
  const [staffList, setStaffList] = useState([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formData, setFormData] = useState({
    proposal_id: '',
    assignment_type: 'VERIFICATION',
    assigned_user_id: '',
    notes: '',
  });

  // Modal Revoke
  const [revokeTarget, setRevokeTarget] = useState(null);
  const [revokeReason, setRevokeReason] = useState('');
  const [isRevoking, setIsRevoking] = useState(false);

  // Load Data
  const fetchData = async () => {
    setIsLoading(true);
    try {
      const [assignRes, workloadRes] = await Promise.allSettled([
        api.get('/assignments'),
        api.get('/assignments/workload'),
      ]);

      if (assignRes.status === 'fulfilled') {
        const list = Array.isArray(assignRes.value?.data)
          ? assignRes.value.data
          : Array.isArray(assignRes.value?.data?.data)
          ? assignRes.value.data.data
          : [];
        setAssignments(list);
      }

      if (workloadRes.status === 'fulfilled') {
        setWorkload(workloadRes.value?.data || null);
      }
    } catch (err) {
      console.error('Failed to load assignments:', err);
      addToast('Gagal memuat daftar penugasan.', 'error');
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, []);

  // Fetch proposals and staff when modal opens
  const openCreateModal = async () => {
    setIsCreateOpen(true);
    try {
      const [propRes, usersRes] = await Promise.allSettled([
        api.get('/proposals'),
        api.get('/users?is_active=true&per_page=100'),
      ]);

      if (propRes.status === 'fulfilled') {
        const pList = Array.isArray(propRes.value?.data)
          ? propRes.value.data
          : Array.isArray(propRes.value?.data?.data)
          ? propRes.value.data.data
          : [];
        setProposals(pList);
      }

      if (usersRes.status === 'fulfilled') {
        const uList = Array.isArray(usersRes.value?.data)
          ? usersRes.value.data
          : Array.isArray(usersRes.value?.data?.data)
          ? usersRes.value.data.data
          : [];
        setStaffList(uList);
      }
    } catch (err) {
      console.error('Failed to prepare assignment form:', err);
    }
  };

  // Filter staff by currently selected assignment type
  const availableStaff = staffList.filter((u) => {
    const target = ASSIGNMENT_TYPES.find((t) => t.value === formData.assignment_type);
    if (!target) return true;
    return u.roles?.some((r) => r.code === target.roleCode);
  });

  const handleCreateSubmit = async (e) => {
    e.preventDefault();
    if (!formData.proposal_id || !formData.assigned_user_id) {
      addToast('Harap pilih usulan dan staf yang ditugaskan.', 'warning');
      return;
    }

    setIsSubmitting(true);
    try {
      await api.post('/assignments', formData);
      addToast('Penugasan berhasil diberikan.', 'success');
      setIsCreateOpen(false);
      setFormData({
        proposal_id: '',
        assignment_type: 'VERIFICATION',
        assigned_user_id: '',
        notes: '',
      });
      fetchData();
    } catch (err) {
      console.error('Create assignment failed:', err);
      addToast(err.message || 'Gagal menyimpan penugasan.', 'error');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleRevokeSubmit = async (e) => {
    e.preventDefault();
    if (!revokeReason.trim()) {
      addToast('Harap sertakan alasan pencabutan tugas.', 'warning');
      return;
    }

    setIsRevoking(true);
    try {
      await api.post(`/assignments/${revokeTarget.id}/revoke`, {
        reason: revokeReason,
      });
      addToast('Penugasan berhasil dicabut.', 'success');
      setRevokeTarget(null);
      setRevokeReason('');
      fetchData();
    } catch (err) {
      console.error('Revoke assignment failed:', err);
      addToast(err.message || 'Gagal mencabut penugasan.', 'error');
    } finally {
      setIsRevoking(false);
    }
  };

  const filteredAssignments = assignments.filter((a) => {
    const matchSearch =
      a.proposal?.title?.toLowerCase().includes(search.toLowerCase()) ||
      a.proposal?.proposal_number?.toLowerCase().includes(search.toLowerCase()) ||
      a.assigned_user?.name?.toLowerCase().includes(search.toLowerCase());
    const matchType = !typeFilter || a.assignment_type === typeFilter;
    const matchStatus = !statusFilter || a.status === statusFilter;
    return matchSearch && matchType && matchStatus;
  });

  const getTypeBadge = (type) => {
    switch (type) {
      case 'VERIFICATION':
        return <Badge variant="info">Verifikasi</Badge>;
      case 'EVALUATION':
        return <Badge variant="warning">Evaluasi</Badge>;
      case 'FIELD_SURVEY':
        return <Badge variant="primary">Survei Lapangan</Badge>;
      default:
        return <Badge variant="default">{type}</Badge>;
    }
  };

  const getStatusBadge = (status) => {
    switch (status) {
      case 'ASSIGNED':
        return <Badge variant="warning">Ditugaskan</Badge>;
      case 'IN_PROGRESS':
        return <Badge variant="info">Dikerjakan</Badge>;
      case 'COMPLETED':
        return <Badge variant="success">Selesai</Badge>;
      case 'REVOKED':
        return <Badge variant="danger">Dicabut</Badge>;
      default:
        return <Badge variant="default">{status}</Badge>;
    }
  };

  return (
    <div className="space-y-6">
      <PageHeader
        title="Penugasan Staf Lapangan"
        subtitle="Mandat penugasan resmi Administrator kepada Verifikator, Evaluator, dan Surveyor Lapangan"
        breadcrumbs={[{ label: 'Tata Kelola' }, { label: 'Penugasan Staf Lapangan' }]}
        action={
          <Button variant="primary" onClick={openCreateModal} className="flex items-center gap-2">
            <PlusIcon className="w-5 h-5" />
            Tugaskan Staf Baru
          </Button>
        }
      />

      {/* Workload Stats Card */}
      {workload && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card className="p-4 border-slate-200">
            <div className="text-sm font-medium text-slate-500">Total Penugasan</div>
            <div className="text-2xl font-bold text-slate-900 mt-1">{assignments.length}</div>
          </Card>
          <Card className="p-4 border-slate-200">
            <div className="text-sm font-medium text-slate-500">Verifikasi Berjalan</div>
            <div className="text-2xl font-bold text-blue-600 mt-1">
              {assignments.filter((a) => a.assignment_type === 'VERIFICATION' && a.status !== 'REVOKED' && a.status !== 'COMPLETED').length}
            </div>
          </Card>
          <Card className="p-4 border-slate-200">
            <div className="text-sm font-medium text-slate-500">Evaluasi Berjalan</div>
            <div className="text-2xl font-bold text-amber-600 mt-1">
              {assignments.filter((a) => a.assignment_type === 'EVALUATION' && a.status !== 'REVOKED' && a.status !== 'COMPLETED').length}
            </div>
          </Card>
          <Card className="p-4 border-slate-200">
            <div className="text-sm font-medium text-slate-500">Survei Lapangan Berjalan</div>
            <div className="text-2xl font-bold text-emerald-600 mt-1">
              {assignments.filter((a) => a.assignment_type === 'FIELD_SURVEY' && a.status !== 'REVOKED' && a.status !== 'COMPLETED').length}
            </div>
          </Card>
        </div>
      )}

      {/* Filter & Search */}
      <Card className="p-4 border-slate-200">
        <div className="flex flex-col sm:flex-row gap-4 justify-between items-center">
          <div className="relative w-full sm:w-80">
            <MagnifyingGlassIcon className="w-5 h-5 absolute left-3 top-2.5 text-slate-400" />
            <input
              type="text"
              placeholder="Cari nomor usulan, judul, atau nama staf..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div className="flex gap-3 w-full sm:w-auto">
            <select
              value={typeFilter}
              onChange={(e) => setTypeFilter(e.target.value)}
              className="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Semua Tahap Penugasan</option>
              <option value="VERIFICATION">Verifikasi Administrasi</option>
              <option value="EVALUATION">Evaluasi Substantif</option>
              <option value="FIELD_SURVEY">Survei Lapangan</option>
            </select>
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Semua Status</option>
              <option value="ASSIGNED">Ditugaskan</option>
              <option value="IN_PROGRESS">Sedang Dikerjakan</option>
              <option value="COMPLETED">Selesai</option>
              <option value="REVOKED">Dicabut</option>
            </select>
            <Button variant="secondary" onClick={fetchData} className="px-3 py-2">
              <ArrowPathIcon className="w-5 h-5" />
            </Button>
          </div>
        </div>
      </Card>

      {/* Assignments Table */}
      <Card className="border-slate-200">
        {isLoading ? (
          <div className="py-12 flex justify-center">
            <Spinner size="lg" />
          </div>
        ) : filteredAssignments.length === 0 ? (
          <EmptyState
            icon={UserGroupIcon}
            title="Tidak Ada Data Penugasan"
            description="Belum ada penugasan staf lapangan yang terdaftar sesuai kriteria filter."
          />
        ) : (
          <Table>
            <TableHead>
              <TableRow>
                <TableHeaderCell>No. Usulan & Judul</TableHeaderCell>
                <TableHeaderCell>Tahap Tugas</TableHeaderCell>
                <TableHeaderCell>Staf Lapangan</TableHeaderCell>
                <TableHeaderCell>Ditugaskan Oleh</TableHeaderCell>
                <TableHeaderCell>Waktu Penugasan</TableHeaderCell>
                <TableHeaderCell>Status</TableHeaderCell>
                <TableHeaderCell className="text-right">Aksi</TableHeaderCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {filteredAssignments.map((item) => (
                <TableRow key={item.id}>
                  <TableCell>
                    <div className="font-semibold text-slate-900">
                      {item.proposal?.proposal_number || 'N/A'}
                    </div>
                    <div className="text-xs text-slate-500 line-clamp-1">
                      {item.proposal?.title || '-'}
                    </div>
                  </TableCell>
                  <TableCell>{getTypeBadge(item.assignment_type)}</TableCell>
                  <TableCell>
                    <div className="font-medium text-slate-800">
                      {item.assigned_user?.name || 'Staf Lapangan'}
                    </div>
                    <div className="text-xs text-slate-400">
                      {item.assigned_user?.email || '-'}
                    </div>
                  </TableCell>
                  <TableCell className="text-slate-600 text-sm">
                    {item.assigner?.name || 'Administrator'}
                  </TableCell>
                  <TableCell className="text-slate-500 text-xs">
                    {formatDate(item.assigned_at)}
                  </TableCell>
                  <TableCell>{getStatusBadge(item.status)}</TableCell>
                  <TableCell className="text-right">
                    {['ASSIGNED', 'IN_PROGRESS'].includes(item.status) ? (
                      <Button
                        variant="danger"
                        size="sm"
                        onClick={() => setRevokeTarget(item)}
                      >
                        Cabut Tugas
                      </Button>
                    ) : (
                      <span className="text-xs text-slate-400">-</span>
                    )}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </Card>

      {/* Modal Buat Penugasan Baru */}
      {isCreateOpen && (
        <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
          <div className="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 space-y-4">
            <div className="flex justify-between items-center border-b pb-3">
              <h3 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                <UserGroupIcon className="w-5 h-5 text-blue-600" />
                Beri Penugasan Staf Lapangan
              </h3>
              <button
                onClick={() => setIsCreateOpen(false)}
                className="text-slate-400 hover:text-slate-600"
              >
                <XMarkIcon className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleCreateSubmit} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">
                  Pilih Usulan Hibah
                </label>
                <select
                  required
                  value={formData.proposal_id}
                  onChange={(e) => setFormData({ ...formData, proposal_id: e.target.value })}
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">-- Pilih Usulan Yang Aktif --</option>
                  {proposals.map((p) => (
                    <option key={p.id} value={p.id}>
                      [{p.proposal_number || 'NO-NUM'}] {p.title} ({p.status})
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">
                  Tahap / Tipe Penugasan
                </label>
                <select
                  required
                  value={formData.assignment_type}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      assignment_type: e.target.value,
                      assigned_user_id: '',
                    })
                  }
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  {ASSIGNMENT_TYPES.map((t) => (
                    <option key={t.value} value={t.value}>
                      {t.label} (Role: {t.roleCode})
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">
                  Pilih Staf Lapangan (Role Sesuai)
                </label>
                <select
                  required
                  value={formData.assigned_user_id}
                  onChange={(e) =>
                    setFormData({ ...formData, assigned_user_id: e.target.value })
                  }
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">-- Pilih Petugas Lapangan --</option>
                  {availableStaff.map((u) => (
                    <option key={u.id} value={u.id}>
                      {u.name} ({u.email})
                    </option>
                  ))}
                </select>
                {availableStaff.length === 0 && (
                  <p className="text-xs text-amber-600 mt-1">
                    Belum ditemukan user dengan role yang sesuai untuk tipe penugasan ini.
                  </p>
                )}
              </div>

              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">
                  Catatan / Instruksi Khusus (Opsional)
                </label>
                <textarea
                  rows={3}
                  value={formData.notes}
                  onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                  placeholder="Catatan prioritas atau instruksi teknis kepada petugas..."
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div className="flex justify-end gap-3 pt-3 border-t">
                <Button
                  type="button"
                  variant="secondary"
                  onClick={() => setIsCreateOpen(false)}
                >
                  Batal
                </Button>
                <Button type="submit" variant="primary" disabled={isSubmitting}>
                  {isSubmitting ? 'Menyimpan...' : 'Kirim Mandat Penugasan'}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal Cabut Penugasan */}
      {revokeTarget && (
        <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
          <div className="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
            <div className="flex items-center gap-3 text-red-600">
              <ExclamationTriangleIcon className="w-6 h-6" />
              <h3 className="text-lg font-bold text-slate-900">Cabut Penugasan</h3>
            </div>
            <p className="text-sm text-slate-600">
              Anda akan mencabut penugasan dari staf{' '}
              <span className="font-semibold text-slate-800">
                {revokeTarget.assigned_user?.name}
              </span>{' '}
              pada usulan{' '}
              <span className="font-semibold text-slate-800">
                {revokeTarget.proposal?.proposal_number}
              </span>
              . Staf tidak akan lagi memiliki akses ke berkas usulan ini.
            </p>

            <form onSubmit={handleRevokeSubmit} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">
                  Alasan Pencabutan Penugasan <span className="text-red-500">*</span>
                </label>
                <textarea
                  required
                  rows={3}
                  value={revokeReason}
                  onChange={(e) => setRevokeReason(e.target.value)}
                  placeholder="Contoh: Rotasi staf wilayah atau pergantian tim teknis..."
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                />
              </div>

              <div className="flex justify-end gap-3 pt-3 border-t">
                <Button
                  type="button"
                  variant="secondary"
                  onClick={() => {
                    setRevokeTarget(null);
                    setRevokeReason('');
                  }}
                >
                  Batal
                </Button>
                <Button type="submit" variant="danger" disabled={isRevoking}>
                  {isRevoking ? 'Mencabut...' : 'Konfirmasi Pencabutan'}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

