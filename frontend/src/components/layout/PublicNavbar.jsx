import React, { useState } from 'react';
import { Link, NavLink } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { Bars3Icon, XMarkIcon, ShieldCheckIcon } from '@heroicons/react/24/outline';

const navItems = [
  { name: 'Beranda', href: '/' },
  { name: 'Program Hibah', href: '/programs' },
  { name: 'Transparansi', href: '/transparency' },
  { name: 'Statistik', href: '/statistics' },
  { name: 'Cek Keaslian Dokumen', href: '/verify' },
];

export default function PublicNavbar() {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const { user } = useAuth();

  return (
    <header className="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur-xs">
      <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
        <Link to="/" className="flex items-center gap-3">
          <img src="/logo.png" alt="SIKOMANDO Logo" className="h-10 w-10 object-contain drop-shadow-xs" />
          <div>
            <span className="text-lg font-black tracking-tight text-slate-900">SIKOMANDO</span>
            <span className="hidden text-xs text-slate-500 sm:block">
              Portal Digitalisasi Hibah Daerah
            </span>
          </div>
        </Link>

        <nav className="hidden items-center gap-1 md:flex">
          {navItems.map((item) => (
            <NavLink
              key={item.href}
              to={item.href}
              className={({ isActive }) =>
                `rounded-lg px-3.5 py-2 text-sm font-medium transition ${
                  isActive
                    ? 'bg-blue-50 text-blue-700'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                }`
              }
            >
              {item.name}
            </NavLink>
          ))}
        </nav>

        <div className="hidden items-center gap-3 md:flex">
          {user ? (
            <Link
              to="/dashboard"
              className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-700 transition"
            >
              Buka Dashboard
            </Link>
          ) : (
            <Link
              to="/login"
              className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition"
            >
              Masuk Sistem
            </Link>
          )}
        </div>

        <div className="flex md:hidden">
          <button
            type="button"
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            className="rounded-lg p-2 text-slate-600 hover:bg-slate-100"
          >
            {mobileMenuOpen ? <XMarkIcon className="h-6 w-6" /> : <Bars3Icon className="h-6 w-6" />}
          </button>
        </div>
      </div>

      {mobileMenuOpen && (
        <div className="border-b border-slate-200 bg-white px-4 py-3 md:hidden">
          <div className="space-y-1">
            {navItems.map((item) => (
              <NavLink
                key={item.href}
                to={item.href}
                onClick={() => setMobileMenuOpen(false)}
                className={({ isActive }) =>
                  `block rounded-lg px-3 py-2 text-sm font-medium ${
                    isActive ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100'
                  }`
                }
              >
                {item.name}
              </NavLink>
            ))}
          </div>
          <div className="mt-4 border-t border-slate-200 pt-3">
            {user ? (
              <Link
                to="/dashboard"
                onClick={() => setMobileMenuOpen(false)}
                className="block w-full rounded-lg bg-blue-600 py-2.5 text-center text-sm font-semibold text-white"
              >
                Buka Dashboard
              </Link>
            ) : (
              <Link
                to="/login"
                onClick={() => setMobileMenuOpen(false)}
                className="block w-full rounded-lg border border-slate-300 py-2.5 text-center text-sm font-semibold text-slate-700"
              >
                Masuk Sistem
              </Link>
            )}
          </div>
        </div>
      )}
    </header>
  );
}

