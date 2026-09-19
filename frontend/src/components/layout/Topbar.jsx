import React from 'react';
import { Link } from 'react-router-dom';
import { Bars3Icon, GlobeAltIcon, ArrowRightOnRectangleIcon } from '@heroicons/react/24/outline';
import { useAuth } from '../../context/useAuth';

export default function Topbar({ onMenuClick }) {
  const { user, logout } = useAuth();

  const userRoles = Array.isArray(user?.roles)
    ? user.roles.map((r) => (typeof r === 'string' ? r : r.code || r.name))
    : [];

  return (
    <header className="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 backdrop-blur-xs px-4 sm:px-6">
      <button
        type="button"
        aria-label="Buka menu"
        className="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden"
        onClick={onMenuClick}
      >
        <Bars3Icon className="h-6 w-6" />
      </button>

      <div className="ml-auto flex items-center gap-3 sm:gap-4">
        <Link
          to="/"
          target="_blank"
          rel="noopener noreferrer"
          title="Lihat Portal Publik"
          className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 transition"
        >
          <GlobeAltIcon className="h-4 w-4 text-blue-600" />
          <span className="hidden sm:inline">Portal Publik</span>
        </Link>

        <div className="hidden text-right sm:block">
          <div className="flex items-center justify-end gap-1.5">
            <span className="text-sm font-semibold text-slate-900">{user?.name ?? 'Pengguna'}</span>
            {userRoles.length > 0 && (
              <span className="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 border border-blue-200">
                {userRoles[0]}
              </span>
            )}
          </div>
          <p className="text-xs text-slate-500">{user?.email ?? ''}</p>
        </div>

        <button
          type="button"
          onClick={logout}
          className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-rose-600 transition"
        >
          <ArrowRightOnRectangleIcon className="h-4 w-4" />
          <span>Keluar</span>
        </button>
      </div>
    </header>
  );
}