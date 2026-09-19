import React, { useEffect, useState } from 'react';
import {
  getUsers,
  createUser,
  toggleUserActive,
  assignUserRole,
  removeUserRole,
  resetUserPassword,
} from '../api/users';

import DataTable from '../components/common/DataTable';
import Modal from '../components/common/Modal';

import {
  UserPlusIcon,
  KeyIcon,
  CheckBadgeIcon,
} from '@heroicons/react/24/outline';

const ALL_ROLES = [
  'SUPER_ADMIN',
  'ADMIN',
  'PEMOHON',
  'VERIFIKATOR',
  'EVALUATOR',
  'SURVEYOR',
  'APPROVER',
  'AUDITOR',
];

export default function UserManagementPage() {
  // =========================================================
  // STATE
  // =========================================================

  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [successMsg, setSuccessMsg] = useState('');

  // Modal create user
  const [createModalOpen, setCreateModalOpen] = useState(false);

  const [userName, setUserName] = useState('');
  const [userEmail, setUserEmail] = useState('');
  const [userPassword, setUserPassword] = useState('');
  const [userRole, setUserRole] = useState('VERIFIKATOR');

  // Modal role
  const [roleModalOpen, setRoleModalOpen] = useState(false);
  const [selectedUser, setSelectedUser] = useState(null);
  const [newRoleToAssign, setNewRoleToAssign] = useState('EVALUATOR');

  // =========================================================
  // HELPER
  // Memastikan data selalu berupa array
  // =========================================================

  function normalizeUsers(response) {
    // 1. Response langsung berupa array
    if (Array.isArray(response)) {
      return response;
    }

    // 2. { data: [...] }
    if (Array.isArray(response?.data)) {
      return response.data;
    }

    // 3. { data: { data: [...] } }
    if (Array.isArray(response?.data?.data)) {
      return response.data.data;
    }

    // 4. { users: [...] }
    if (Array.isArray(response?.users)) {
      return response.users;
    }

    // 5. { data: { users: [...] } }
    if (Array.isArray(response?.data?.users)) {
      return response.data.users;
    }

    // Tidak ada array yang ditemukan
    return [];
  }

  // =========================================================
  // LOAD USERS
  // =========================================================

  async function loadUsers() {
    setLoading(true);

    try {
      const response = await getUsers();

      console.log('GET /users response:', response);

      const normalizedUsers = normalizeUsers(response);

      console.log('Normalized users:', normalizedUsers);

      // PENTING:
      // users selalu dipastikan array
      setUsers(
        Array.isArray(normalizedUsers)
          ? normalizedUsers
          : []
      );
    } catch (err) {
      console.error('Failed to load users:', err);

      // Jangan biarkan state menjadi object/null
      setUsers([]);

      const message =
        err?.response?.data?.message ||
        err?.message ||
        'Gagal mengambil data pengguna.';

      alert(message);
    } finally {
      setLoading(false);
    }
  }

  // =========================================================
  // INITIAL LOAD
  // =========================================================

  useEffect(() => {
    loadUsers();
  }, []);

  // =========================================================
  // SUCCESS MESSAGE
  // =========================================================

  function showSuccess(message) {
    setSuccessMsg(message);

    setTimeout(() => {
      setSuccessMsg('');
    }, 4000);
  }

  // =========================================================
  // CREATE USER
  // =========================================================

  async function handleCreateUser(e) {
    e.preventDefault();

    if (actionLoading) return;

    setActionLoading(true);

    try {
      await createUser({
        name: userName,
        email: userEmail,
        password: userPassword,
        role: userRole,
        roles: [userRole],
      });

      setCreateModalOpen(false);

      setUserName('');
      setUserEmail('');
      setUserPassword('');
      setUserRole('VERIFIKATOR');

      showSuccess('Pengguna baru berhasil didaftarkan.');

      await loadUsers();
    } catch (err) {
      console.error('Create user error:', err);

      alert(
        err?.response?.data?.message ||
          'Gagal mendaftarkan pengguna.'
      );
    } finally {
      setActionLoading(false);
    }
  }

  // =========================================================
  // TOGGLE ACTIVE
  // =========================================================

  async function handleToggleActive(user) {
    if (!user?.id) {
      alert('ID pengguna tidak ditemukan.');
      return;
    }

    const statusText =
      user.is_active !== false
        ? 'Nonaktifkan'
        : 'Aktifkan';

    const confirmed = window.confirm(
      `${statusText} akun ${user.name || 'pengguna ini'}?`
    );

    if (!confirmed) return;

    if (actionLoading) return;

    setActionLoading(true);

    try {
      await toggleUserActive(user.id);

      showSuccess(
        `Status akun ${user.name || 'pengguna'} berhasil diperbarui.`
      );

      await loadUsers();
    } catch (err) {
      console.error('Toggle active error:', err);

      alert(
        err?.response?.data?.message ||
          'Gagal mengubah status aktif.'
      );
    } finally {
      setActionLoading(false);
    }
  }

  // =========================================================
  // RESET PASSWORD
  // =========================================================

  async function handleResetPassword(user) {
    if (!user?.id) {
      alert('ID pengguna tidak ditemukan.');
      return;
    }

    const confirmed = window.confirm(
      `Reset kata sandi untuk pengguna ${
        user.name || 'ini'
      }?\n\nKata sandi baru akan di-generate otomatis.`
    );

    if (!confirmed) return;

    if (actionLoading) return;

    setActionLoading(true);

    try {
      const response = await resetUserPassword(user.id);

      console.log('Reset password response:', response);

      const generatedPassword =
        response?.data?.temporary_password ||
        response?.data?.password ||
        response?.temporary_password ||
        response?.password;

      if (generatedPassword) {
        alert(
          `Kata sandi berhasil di-reset.\n\nKata sandi sementara:\n${generatedPassword}`
        );
      } else {
        alert('Kata sandi berhasil di-reset.');
      }
    } catch (err) {
      console.error('Reset password error:', err);

      alert(
        err?.response?.data?.message ||
          'Gagal mereset kata sandi.'
      );
    } finally {
      setActionLoading(false);
    }
  }

  // =========================================================
  // ASSIGN ROLE
  // =========================================================

  async function handleAssignRole(e) {
    e.preventDefault();

    if (!selectedUser?.id) {
      alert('Pengguna belum dipilih.');
      return;
    }

    if (!newRoleToAssign) {
      alert('Silakan pilih role.');
      return;
    }

    if (actionLoading) return;

    setActionLoading(true);

    try {
      await assignUserRole(
        selectedUser.id,
        newRoleToAssign
      );

      setRoleModalOpen(false);

      showSuccess(
        `Role ${newRoleToAssign} berhasil ditambahkan ke ${
          selectedUser.name || 'pengguna'
        }.`
      );

      setSelectedUser(null);

      await loadUsers();
    } catch (err) {
      console.error('Assign role error:', err);

      alert(
        err?.response?.data?.message ||
          'Gagal menambahkan role.'
      );
    } finally {
      setActionLoading(false);
    }
  }

  // =========================================================
  // REMOVE ROLE
  // =========================================================

  async function handleRemoveRole(user, roleCode) {
    if (!user?.id) {
      alert('ID pengguna tidak ditemukan.');
      return;
    }

    if (!roleCode) {
      alert('Role tidak ditemukan.');
      return;
    }

    const confirmed = window.confirm(
      `Hapus role ${roleCode} dari ${
        user.name || 'pengguna ini'
      }?`
    );

    if (!confirmed) return;

    if (actionLoading) return;

    setActionLoading(true);

    try {
      await removeUserRole(user.id, roleCode);

      showSuccess(
        `Role ${roleCode} berhasil dihapus dari ${
          user.name || 'pengguna'
        }.`
      );

      await loadUsers();
    } catch (err) {
      console.error('Remove role error:', err);

      alert(
        err?.response?.data?.message ||
          'Gagal menghapus role.'
      );
    } finally {
      setActionLoading(false);
    }
  }

  // =========================================================
  // TABLE COLUMNS
  // =========================================================

  const columns = [
    {
      title: 'Nama & Email Pengguna',
      key: 'name',

      render: (val, row) => (
        <div>
          <p className="font-bold text-slate-900">
            {val || '-'}
          </p>

          <p className="text-xs text-slate-500">
            {row?.email || '-'}
          </p>
        </div>
      ),
    },

    {
      title: 'Peran & Multi-Role',
      key: 'roles',

      render: (val, row) => {
        const roles = Array.isArray(val)
          ? val
          : [];

        return (
          <div className="flex flex-wrap items-center gap-1">
            {roles.length > 0 ? (
              roles.map((role, index) => {
                const code =
                  typeof role === 'string'
                    ? role
                    : role?.code ||
                      role?.name ||
                      '';

                if (!code) return null;

                return (
                  <span
                    key={`${code}-${index}`}
                    className="group inline-flex items-center gap-1 rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700"
                  >
                    <span>{code}</span>

                    <button
                      type="button"
                      disabled={actionLoading}
                      onClick={() =>
                        handleRemoveRole(row, code)
                      }
                      className="ml-0.5 font-bold text-blue-400 hover:text-rose-600 disabled:cursor-not-allowed disabled:opacity-50"
                      title="Hapus role"
                    >
                      ×
                    </button>
                  </span>
                );
              })
            ) : (
              <span className="text-xs text-slate-400">
                Belum ada role
              </span>
            )}

            <button
              type="button"
              disabled={actionLoading}
              onClick={() => {
                setSelectedUser(row);
                setNewRoleToAssign('EVALUATOR');
                setRoleModalOpen(true);
              }}
              className="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-50"
            >
              + Role
            </button>
          </div>
        );
      },
    },

    {
      title: 'Status Akun',
      key: 'is_active',

      render: (val) => {
        const active = val !== false;

        return (
          <span
            className={`rounded-full border px-2.5 py-0.5 text-xs font-semibold ${
              active
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                : 'border-rose-200 bg-rose-50 text-rose-700'
            }`}
          >
            {active ? 'Aktif' : 'Nonaktif'}
          </span>
        );
      },
    },

    {
      title: 'Aksi Kelola',
      key: 'action',

      render: (_, row) => (
        <div className="flex items-center gap-2">
          <button
            type="button"
            disabled={actionLoading}
            onClick={() =>
              handleToggleActive(row)
            }
            className="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
          >
            {row?.is_active !== false
              ? 'Nonaktifkan'
              : 'Aktifkan'}
          </button>

          <button
            type="button"
            disabled={actionLoading}
            onClick={() =>
              handleResetPassword(row)
            }
            className="rounded-md border border-slate-200 bg-white p-1 text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            title="Reset Password"
          >
            <KeyIcon className="h-4 w-4" />
          </button>
        </div>
      ),
    },
  ];

  // =========================================================
  // RENDER
  // =========================================================

  return (
    <div className="space-y-6">
      {/* HEADER */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">
            Kelola Pengguna & Multi-Role
          </h1>

          <p className="mt-1 text-xs text-slate-500">
            Manajemen akun pegawai, verifikator,
            pimpinan, multi-role assignment, dan
            kontrol status aktif.
          </p>
        </div>

        <button
          type="button"
          onClick={() =>
            setCreateModalOpen(true)
          }
          className="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-blue-700"
        >
          <UserPlusIcon className="h-4 w-4" />

          <span>
            + Daftarkan Pengguna Baru
          </span>
        </button>
      </div>

      {/* SUCCESS MESSAGE */}
      {successMsg && (
        <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-700">
          <CheckBadgeIcon className="h-5 w-5 shrink-0 text-emerald-600" />

          <span>{successMsg}</span>
        </div>
      )}

      {/* USER TABLE */}
      <DataTable
        columns={columns}

        /*
         * PENTING:
         * DataTable SELALU menerima array.
         * Jika API mengembalikan object/null,
         * halaman tidak akan crash.
         */
        data={
          Array.isArray(users)
            ? users
            : []
        }

        loading={loading}
        searchPlaceholder="Cari pengguna berdasarkan nama atau email..."
        emptyTitle="Belum Ada Pengguna"
        emptyDescription="Daftar akun pengguna sistem SIKOMANDO akan muncul di sini."
      />

      {/* =====================================================
          MODAL USER BARU
          ===================================================== */}

      <Modal
        isOpen={createModalOpen}
        onClose={() => {
          if (!actionLoading) {
            setCreateModalOpen(false);
          }
        }}
        title="Daftarkan Akun Pengguna Baru"
      >
        <form
          onSubmit={handleCreateUser}
          className="space-y-4"
        >
          {/* Nama */}
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Nama Lengkap *
            </label>

            <input
              type="text"
              value={userName}
              onChange={(e) =>
                setUserName(e.target.value)
              }
              required
              disabled={actionLoading}
              className="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-blue-500 focus:outline-hidden disabled:bg-slate-100"
            />
          </div>

          {/* Email */}
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Alamat Email *
            </label>

            <input
              type="email"
              value={userEmail}
              onChange={(e) =>
                setUserEmail(e.target.value)
              }
              required
              disabled={actionLoading}
              className="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-blue-500 focus:outline-hidden disabled:bg-slate-100"
            />
          </div>

          {/* Password */}
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Kata Sandi Awal *
            </label>

            <input
              type="password"
              value={userPassword}
              onChange={(e) =>
                setUserPassword(e.target.value)
              }
              required
              disabled={actionLoading}
              minLength={8}
              className="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-blue-500 focus:outline-hidden disabled:bg-slate-100"
            />

            <p className="mt-1 text-[10px] text-slate-400">
              Minimal 8 karakter.
            </p>
          </div>

          {/* Role */}
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Peran Awal *
            </label>

            <select
              value={userRole}
              onChange={(e) =>
                setUserRole(e.target.value)
              }
              disabled={actionLoading}
              className="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold focus:border-blue-500 focus:outline-hidden disabled:bg-slate-100"
            >
              {ALL_ROLES.map((role) => (
                <option
                  key={role}
                  value={role}
                >
                  {role}
                </option>
              ))}
            </select>
          </div>

          {/* Buttons */}
          <div className="flex justify-end gap-2 pt-4">
            <button
              type="button"
              disabled={actionLoading}
              onClick={() =>
                setCreateModalOpen(false)
              }
              className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
            >
              Batal
            </button>

            <button
              type="submit"
              disabled={actionLoading}
              className="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {actionLoading
                ? 'Menyimpan...'
                : 'Simpan Pengguna'}
            </button>
          </div>
        </form>
      </Modal>

      {/* =====================================================
          MODAL ASSIGN ROLE
          ===================================================== */}

      <Modal
        isOpen={roleModalOpen}
        onClose={() => {
          if (!actionLoading) {
            setRoleModalOpen(false);
            setSelectedUser(null);
          }
        }}
        title={`Tambah Role untuk ${
          selectedUser?.name || 'Pengguna'
        }`}
      >
        <form
          onSubmit={handleAssignRole}
          className="space-y-4"
        >
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Pilih Role Baru *
            </label>

            <select
              value={newRoleToAssign}
              onChange={(e) =>
                setNewRoleToAssign(
                  e.target.value
                )
              }
              disabled={actionLoading}
              className="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold focus:border-blue-500 focus:outline-hidden disabled:bg-slate-100"
            >
              {ALL_ROLES.map((role) => (
                <option
                  key={role}
                  value={role}
                >
                  {role}
                </option>
              ))}
            </select>
          </div>

          <div className="rounded-lg border border-blue-100 bg-blue-50 p-3">
            <p className="text-xs text-blue-700">
              Role akan ditambahkan ke akun
              pengguna tanpa menghapus role
              yang sudah dimiliki.
            </p>
          </div>

          <div className="flex justify-end gap-2 pt-4">
            <button
              type="button"
              disabled={actionLoading}
              onClick={() => {
                setRoleModalOpen(false);
                setSelectedUser(null);
              }}
              className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
            >
              Batal
            </button>

            <button
              type="submit"
              disabled={actionLoading}
              className="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {actionLoading
                ? 'Menambahkan...'
                : 'Tambahkan Role'}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}