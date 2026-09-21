import React, { useState, useEffect } from 'react';
import api from '../../services/api';
import { useToast } from '../../context/ToastContext';
import { ROLES, ROLE_LABELS, ROLE_BADGE_COLORS } from '../../utils/constants';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody } from '../../components/ui/Card';
import Table, { TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '../../components/ui/Table';
import Button from '../../components/ui/Button';
import Modal from '../../components/ui/Modal';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';
import Spinner from '../../components/feedback/Spinner';
import ConfirmDialog from '../../components/feedback/ConfirmDialog';
import {
  UsersIcon,
  UserPlusIcon,
  MagnifyingGlassIcon,
  KeyIcon,
  CheckCircleIcon,
  NoSymbolIcon,
} from '@heroicons/react/24/outline';

export function UserManagementPage() {
  const toast = useToast();

  const [users, setUsers] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');

  // Create User Modal State
  const [createModalOpen, setCreateModalOpen] = useState(false);
  const [isCreating, setIsCreating] = useState(false);
  const [newUser, setNewUser] = useState({
    name: '',
    email: '',
    password: 'Password',
    role: 'PEMOHON',
  });

  // Toggle Active State
  const [selectedUser, setSelectedUser] = useState(null);
  const [confirmToggleOpen, setConfirmToggleOpen] = useState(false);

  const loadUsers = async () => {
    setIsLoading(true);
    try {
      const res = await api.get('/users');
      const list = Array.isArray(res?.data)
        ? res.data
        : Array.isArray(res?.data?.data)
        ? res.data.data
        : Array.isArray(res)
        ? res
        : [];
      setUsers(list);
    } catch (err) {
      console.error('Failed to load users:', err);
      setUsers([]);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadUsers();
  }, []);

  const handleToggleActive = async () => {
    if (!selectedUser) return;
    try {
      await api.post(`/users/${selectedUser.id}/toggle-active`);
      toast.success(`Status akun ${selectedUser.name} berhasil diubah.`);
      setConfirmToggleOpen(false);
      loadUsers();
    } catch (err) {
      toast.error(err.message || 'Gagal mengubah status pengguna.');
    }
  };

  const handleResetPassword = async (u) => {
    try {
      await api.post(`/users/${u.id}/reset-password`);
      toast.success(`Password pengguna ${u.name} berhasil direset menjadi "Password".`);
    } catch (err) {
      toast.error(err.message || 'Gagal mereset password.');
    }
  };

  const handleCreateUser = async (e) => {
    e.preventDefault();
    setIsCreating(true);
    try {
      const selectedRole = newUser.role || 'PEMOHON';
      await api.post('/users', {
        name: newUser.name,
        email: newUser.email,
        password: newUser.password,

        role: selectedRole,
        roles: [selectedRole],
      });
      toast.success('Pengguna baru berhasil didaftarkan!');
      setCreateModalOpen(false);
      setNewUser({ name: '', email: '', password: 'Password', role: 'PEMOHON' });
      loadUsers();
    } catch (err) {
      toast.error(err.message || 'Gagal mendaftarkan pengguna.');
    } finally {
      setIsCreating(false);
    }
  };

  const filtered = (Array.isArray(users) ? users : []).filter((u) =>
    u?.name?.toLowerCase().includes(search.toLowerCase()) ||
    u?.email?.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div className="space-y-6 pb-20">
      <PageHeader
        title="Manajemen Pengguna & Hak Akses RBAC"
        subtitle="Kelola akun aparatur sipil negara, tim pemeriksa teknis, dan perwakilan organisasi pemohon se-Sulut"
        breadcrumbs={[{ label: 'Manajemen Pengguna' }]}
        action={
          <Button
            variant="primary"
            size="md"
            icon={UserPlusIcon}
            onClick={() => setCreateModalOpen(true)}
          >
            Tambah Pengguna Baru
          </Button>
        }
      />

      {/* Search Bar */}
      <Card className="border-slate-200">
        <CardBody className="p-4">
          <div className="relative max-w-md">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
              <MagnifyingGlassIcon className="w-4 h-4" />
            </div>
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Cari nama atau email pengguna..."
              className="w-full pl-9 pr-3 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:bg-white focus:border-blue-500"
            />
          </div>
        </CardBody>
      </Card>

      {/* Users Table */}
      <Card className="border-slate-200 shadow-xs">
        {isLoading ? (
          <Spinner label="Memuat daftar pengguna..." />
        ) : (
          <Table>
            <TableHead>
              <tr>
                <TableHeaderCell>Nama Pengguna</TableHeaderCell>
                <TableHeaderCell>Email Login</TableHeaderCell>
                <TableHeaderCell>Peran Utama (RBAC)</TableHeaderCell>
                <TableHeaderCell>Status Akun</TableHeaderCell>
                <TableHeaderCell className="text-right">Tindakan</TableHeaderCell>
              </tr>
            </TableHead>
            <TableBody>
              {filtered.map((u) => {
                const firstRole = u.roles?.[0];
                // API may return roles as array of objects {id, code, name} or array of strings
                const primaryRoleCode = (typeof firstRole === 'object' && firstRole !== null)
                  ? (firstRole.code || firstRole.name || 'PEMOHON')
                  : (firstRole || 'PEMOHON');
                // Normalize to UPPER_CASE to match ROLE_LABELS / ROLE_BADGE_COLORS keys
                const roleKey = String(primaryRoleCode).toUpperCase();
                const roleLabel = ROLE_LABELS[roleKey] || String(primaryRoleCode);
                const roleBadgeColor = ROLE_BADGE_COLORS[roleKey] || 'bg-slate-100 text-slate-700 border-slate-200';

                return (
                  <TableRow key={u.id}>
                    <TableCell>
                      <div className="font-bold text-slate-900">{u.name}</div>
                    </TableCell>
                    <TableCell mono>{u.email}</TableCell>
                    <TableCell>
                      <span className={`text-[11px] font-bold px-2.5 py-0.5 rounded-full border ${roleBadgeColor}`}>
                        {roleLabel}
                      </span>
                    </TableCell>
                    <TableCell>
                      <span className={`text-xs font-semibold px-2 py-0.5 rounded-full ${
                        u.is_active !== false ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                      }`}>
                        {u.is_active !== false ? 'Aktif' : 'Non-Aktif'}
                      </span>
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex items-center justify-end gap-1.5">
                        <Button
                          variant="ghost"
                          size="xs"
                          icon={KeyIcon}
                          onClick={() => handleResetPassword(u)}
                          title="Reset password ke default"
                        >
                          Reset
                        </Button>
                        <Button
                          variant="outline"
                          size="xs"
                          onClick={() => {
                            setSelectedUser(u);
                            setConfirmToggleOpen(true);
                          }}
                        >
                          {u.is_active !== false ? 'Nonaktifkan' : 'Aktifkan'}
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        )}
      </Card>

      {/* Create User Modal */}
      <Modal
        isOpen={createModalOpen}
        onClose={() => setCreateModalOpen(false)}
        title="Daftarkan Pengguna Baru"
        subtitle="Tambahkan akun staf internal atau pemohon ke dalam sistem SIKOMANDO"
        size="md"
        footer={
          <>
            <Button variant="secondary" size="sm" onClick={() => setCreateModalOpen(false)}>
              Batal
            </Button>
            <Button
              variant="primary"
              size="sm"
              onClick={handleCreateUser}
              isLoading={isCreating}
            >
              Simpan Pengguna
            </Button>
          </>
        }
      >
        <form onSubmit={handleCreateUser} className="space-y-4">
          <Input
            label="Nama Lengkap & Gelar"
            value={newUser.name}
            onChange={(e) => setNewUser({ ...newUser, name: e.target.value })}
            placeholder="Contoh: Drs. Arthur Kotambunan"
            required
          />
          <Input
            label="Alamat Email"
            type="email"
            value={newUser.email}
            onChange={(e) => setNewUser({ ...newUser, email: e.target.value })}
            placeholder="nama@sulutprov.go.id"
            required
          />
          <Input
            label="Password Akun"
            type="password"
            value={newUser.password}
            onChange={(e) => setNewUser({ ...newUser, password: e.target.value })}
            required
          />
          <Select
            label="Peran Kewenangan (RBAC Role)"
            value={newUser.role}
            onChange={(e) => setNewUser({ ...newUser, role: e.target.value })}
            options={Object.entries(ROLE_LABELS).map(([code, label]) => ({
              value: code,
              label: `${label} (${code})`,
            }))}
            required
          />
        </form>
      </Modal>

      {/* Confirm Toggle Active Dialog */}
      <ConfirmDialog
        isOpen={confirmToggleOpen}
        onClose={() => setConfirmToggleOpen(false)}
        onConfirm={handleToggleActive}
        title="Ubah Status Pengguna"
        message={`Apakah Anda yakin ingin ${selectedUser?.is_active !== false ? 'menonaktifkan' : 'mengaktifkan kembali'} akses akun untuk ${selectedUser?.name}?`}
        confirmLabel="Ya, Ubah Status"
        variant={selectedUser?.is_active !== false ? 'danger' : 'success'}
      />
    </div>
  );
}

export default UserManagementPage;

