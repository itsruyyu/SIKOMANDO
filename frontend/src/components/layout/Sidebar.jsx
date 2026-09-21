import React from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { ROLES, ROLE_LABELS, ROLE_BADGE_COLORS } from '../../utils/constants';
import { TripleBrandHeader } from '../ui/Logos';
import {
  Squares2X2Icon,
  DocumentPlusIcon,
  DocumentTextIcon,
  ClipboardDocumentCheckIcon,
  AcademicCapIcon,
  MapPinIcon,
  TrophyIcon,
  CheckBadgeIcon,
  DocumentArrowDownIcon,
  BanknotesIcon,
  ShoppingBagIcon,
  ClipboardDocumentListIcon,
  UsersIcon,
  UserGroupIcon,
  ShieldCheckIcon,
  QrCodeIcon,
  BellIcon,
  Cog6ToothIcon,
  CpuChipIcon,
  ArrowLeftOnRectangleIcon,
  XMarkIcon,
} from '@heroicons/react/24/outline';

export function Sidebar({ isOpen, onClose }) {
  const { user, primaryRole, logout, hasRole } = useAuth();
  const navigate = useNavigate();

  const isSuperAdmin = hasRole(ROLES.SUPER_ADMIN);
  const isAdmin = hasRole(ROLES.ADMIN_SIKOMANDO);
  const isPemohon = hasRole(ROLES.PEMOHON);
  const isVerifikator = hasRole(ROLES.VERIFIKATOR);
  const isEvaluator = hasRole(ROLES.EVALUATOR);
  const isSurveyor = hasRole(ROLES.SURVEYOR);
  const isApprover = hasRole(ROLES.APPROVER);
  const isAuditor = hasRole(ROLES.AUDITOR);

  const roleLabel = ROLE_LABELS[primaryRole] || 'Pengguna';
  const roleBadgeColor = ROLE_BADGE_COLORS[primaryRole] || 'bg-slate-700 text-slate-200 border-slate-600';

  const menuSections = [
    {
      title: 'Utama',
      items: [
        { to: '/dashboard', label: 'Dashboard', icon: Squares2X2Icon, show: true },
        { to: '/notifications', label: 'Notifikasi & Tugas', icon: BellIcon, show: true },
      ],
    },
    {
      title: 'Pengajuan & Usulan',
      show: isPemohon || isSuperAdmin || isAdmin || isAuditor || isVerifikator || isEvaluator || isSurveyor,
      items: [
        { to: '/proposals/create', label: 'Buat Usulan Baru', icon: DocumentPlusIcon, show: isPemohon },
        { to: '/proposals', label: isPemohon ? 'Usulan Saya' : 'Daftar Semua Usulan', icon: DocumentTextIcon, show: true },
      ],
    },
    {
      title: 'Verifikasi & Penilaian',
      show: isVerifikator || isEvaluator || isSurveyor || isSuperAdmin || isAdmin,
      items: [
        { to: '/verifications', label: 'Verifikasi Administrasi', icon: ClipboardDocumentCheckIcon, show: isVerifikator || isSuperAdmin || isAdmin },
        { to: '/evaluations', label: 'Evaluasi Substantif', icon: AcademicCapIcon, show: isEvaluator || isSuperAdmin || isAdmin },
        { to: '/field-surveys', label: 'Survei Lapangan & GPS', icon: MapPinIcon, show: isSurveyor || isSuperAdmin || isAdmin },
      ],
    },
    {
      title: 'TAPD & Penetapan',
      show: isApprover || isSuperAdmin || isAdmin,
      items: [
        { to: '/recommendations', label: 'Perankingan TAPD', icon: TrophyIcon, show: isApprover || isSuperAdmin || isAdmin },
        { to: '/approvals', label: 'Executive Dossier', icon: CheckBadgeIcon, show: isApprover || isSuperAdmin || isAdmin },
        { to: '/decisions', label: 'SK Penetapan Hibah', icon: DocumentArrowDownIcon, show: isApprover || isSuperAdmin || isAdmin },
        { to: '/signatures', label: 'Tanda Tangan Digital (TTE)', icon: ShieldCheckIcon, show: isApprover || isSuperAdmin || isAdmin },
      ],
    },
    {
      title: 'Pencairan & Realisasi',
      show: isPemohon || isSuperAdmin || isAdmin || isAuditor,
      items: [
        { to: '/disbursements', label: 'Pencairan Dana (SP2D)', icon: BanknotesIcon, show: isSuperAdmin || isAdmin || isAuditor },
        { to: '/realizations', label: 'Paket & Kuitansi Belanja', icon: ShoppingBagIcon, show: isPemohon || isSuperAdmin || isAdmin },
        { to: '/lpj', label: 'Pelaporan LPJ', icon: ClipboardDocumentListIcon, show: true },
      ],
    },
    {
      title: 'Pengawasan & Audit',
      show: isAuditor || isSuperAdmin || isAdmin,
      items: [
        { to: '/audit-logs', label: 'Audit Trail Log', icon: ShieldCheckIcon, show: true },
        { to: '/qr-management', label: 'Identitas & Log QR', icon: QrCodeIcon, show: true },
      ],
    },
    {
      title: 'Tata Kelola Sistem',
      show: isSuperAdmin || isAdmin,
      items: [
        { to: '/assignments', label: 'Penugasan Staf Lapangan', icon: UserGroupIcon, show: true },
        { to: '/users', label: 'Manajemen Pengguna', icon: UsersIcon, show: true },
        { to: '/policies', label: 'Kebijakan & Rubrik', icon: Cog6ToothIcon, show: true },
        { to: '/system-monitoring', label: 'Pemantauan Sistem', icon: CpuChipIcon, show: isSuperAdmin },
      ],
    },
  ];

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <>
      {/* Mobile Backdrop */}
      {isOpen && (
        <div
          className="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-xs lg:hidden"
          onClick={onClose}
        />
      )}

      {/* Sidebar Container */}
      <aside
        className={`fixed top-0 bottom-0 left-0 z-50 w-72 bg-[#0B1120] text-slate-200 flex flex-col border-r border-slate-800 transition-transform duration-300 ease-in-out lg:translate-x-0 ${
          isOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        {/* Header with 3 Logos */}
        <div className="h-20 px-4 flex items-center justify-between border-b border-slate-800/80 bg-slate-950/40">
          <TripleBrandHeader size="sm" theme="dark" />
          <button
            type="button"
            onClick={onClose}
            className="lg:hidden text-slate-400 hover:text-white p-1.5 rounded-lg"
          >
            <XMarkIcon className="w-5 h-5" />
          </button>
        </div>

        {/* User Identity Pill Card */}
        <div className="p-4 mx-3 my-3 rounded-xl bg-slate-900/90 border border-slate-800 shadow-2xs">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white font-bold text-sm shadow-xs shrink-0">
              {user?.name?.charAt(0) || 'U'}
            </div>
            <div className="flex-1 min-w-0">
              <div className="text-xs font-bold text-white truncate">{user?.name || 'Pengguna'}</div>
              <div className="text-[11px] text-slate-400 truncate">{user?.email}</div>
            </div>
          </div>
          <div className="mt-2 pt-2 border-t border-slate-800/80 flex items-center justify-between">
            <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full border ${roleBadgeColor}`}>
              {roleLabel}
            </span>
            <span className="inline-flex items-center gap-1 text-[10px] text-emerald-400 font-medium">
              <span className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" /> Online
            </span>
          </div>
        </div>

        {/* Scrollable Navigation List */}
        <nav className="flex-1 px-3 py-2 space-y-6 overflow-y-auto custom-scrollbar">
          {menuSections
            .filter((section) => section.show !== false)
            .map((section, idx) => {
              const visibleItems = section.items.filter((item) => item.show !== false);
              if (visibleItems.length === 0) return null;

              return (
                <div key={idx} className="space-y-1">
                  <div className="px-3 text-[10px] font-bold tracking-wider uppercase text-slate-500">
                    {section.title}
                  </div>
                  {visibleItems.map((item) => {
                    const Icon = item.icon;
                    return (
                      <NavLink
                        key={item.to}
                        to={item.to}
                        onClick={() => onClose && onClose()}
                        className={({ isActive }) =>
                          `flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition-all ${
                            isActive
                              ? 'bg-blue-600 text-white font-bold shadow-xs'
                              : 'text-slate-400 hover:text-white hover:bg-slate-900/80'
                          }`
                        }
                      >
                        <Icon className="w-4 h-4 shrink-0" />
                        <span className="truncate">{item.label}</span>
                      </NavLink>
                    );
                  })}
                </div>
              );
            })}
        </nav>

        {/* Footer with Logout */}
        <div className="p-3 border-t border-slate-800/80 bg-slate-950/60">
          <button
            type="button"
            onClick={handleLogout}
            className="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 border border-rose-500/20 transition-all"
          >
            <ArrowLeftOnRectangleIcon className="w-4 h-4" />
            <span>Keluar Sistem</span>
          </button>
        </div>
      </aside>
    </>
  );
}

export default Sidebar;