import React, { useState } from 'react';
import { Link, NavLink, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { TripleBrandHeader } from '../ui/Logos';
import Button from '../ui/Button';
import {
  Bars3Icon,
  XMarkIcon,
  ArrowRightOnRectangleIcon,
  Squares2X2Icon,
  ShieldCheckIcon,
  MapPinIcon,
  PhoneIcon,
  EnvelopeIcon,
} from '@heroicons/react/24/outline';

export default function PublicLayout() {
  const { isAuthenticated, user } = useAuth();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const location = useLocation();

  const navLinks = [
    { to: '/', label: 'Beranda' },
    { to: '/programs', label: 'Program Hibah' },
    { to: '/tracker', label: 'Lacak Usulan' },
    { to: '/statistics', label: 'Statistik Anggaran' },
    { to: '/transparency', label: 'Transparansi' },
    { to: '/verify', label: 'Verifikasi QR' },
  ];

  return (
    <div className="min-h-screen flex flex-col bg-[#F8FAFC]">
      {/* Topmost Government Ribbon */}
      <div className="bg-slate-900 text-slate-300 text-[11px] py-1 px-4 sm:px-8 border-b border-slate-800">
        <div className="max-w-7xl mx-auto flex items-center justify-between">
          <div className="flex items-center gap-2">
            <span className="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
            <span className="font-medium">Portal Resmi Hibah Pemerintah Provinsi Sulawesi Utara — Terbuka & Akuntabel</span>
          </div>
          <div className="hidden md:flex items-center gap-4 text-slate-400">
            <span>Tahun Anggaran Aktif: 2026</span>
            <span>•</span>
            <span>WITA (UTC+8)</span>
          </div>
        </div>
      </div>

      {/* Main Public Navbar */}
      <header className="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200/90 shadow-2xs">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-20">
            {/* 3 Logos Official Header */}
            <Link to="/" className="hover:opacity-95 transition">
              <TripleBrandHeader size="md" />
            </Link>

            {/* Desktop Navigation Links */}
            <nav className="hidden lg:flex items-center space-x-1">
              {navLinks.map((link) => {
                const isActive = location.pathname === link.to;
                return (
                  <NavLink
                    key={link.to}
                    to={link.to}
                    className={`px-3.5 py-2 rounded-lg text-xs font-semibold tracking-wide transition-all ${
                      isActive
                        ? 'bg-blue-50 text-blue-700 font-bold'
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                    }`}
                  >
                    {link.label}
                  </NavLink>
                );
              })}
            </nav>

            {/* Auth Action Button */}
            <div className="hidden lg:flex items-center gap-3">
              {isAuthenticated ? (
                <Link to="/dashboard">
                  <Button variant="primary" size="sm" leftIcon={<Squares2X2Icon className="w-4 h-4" />}>
                    Dashboard ({user?.name?.split(' ')[0] || 'User'})
                  </Button>
                </Link>
              ) : (
                <Link to="/login">
                  <Button variant="outline" size="sm" leftIcon={<ArrowRightOnRectangleIcon className="w-4 h-4" />}>
                    Masuk Sistem
                  </Button>
                </Link>
              )}
            </div>

            {/* Mobile Menu Button */}
            <div className="flex lg:hidden">
              <button
                type="button"
                onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                className="p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition"
                aria-label="Toggle menu"
              >
                {mobileMenuOpen ? <XMarkIcon className="w-6 h-6" /> : <Bars3Icon className="w-6 h-6" />}
              </button>
            </div>
          </div>
        </div>

        {/* Mobile Navigation Drawer */}
        {mobileMenuOpen && (
          <div className="lg:hidden border-t border-slate-200 bg-white px-4 pt-2 pb-6 space-y-2 shadow-lg animate-in slide-in-from-top-2">
            {navLinks.map((link) => (
              <Link
                key={link.to}
                to={link.to}
                onClick={() => setMobileMenuOpen(false)}
                className={`block px-4 py-2.5 rounded-lg text-sm font-medium ${
                  location.pathname === link.to
                    ? 'bg-blue-50 text-blue-700 font-bold'
                    : 'text-slate-700 hover:bg-slate-50'
                }`}
              >
                {link.label}
              </Link>
            ))}
            <div className="pt-4 border-t border-slate-100">
              {isAuthenticated ? (
                <Link to="/dashboard" onClick={() => setMobileMenuOpen(false)}>
                  <Button variant="primary" size="md" className="w-full" leftIcon={<Squares2X2Icon className="w-4 h-4" />}>
                    Buka Dashboard
                  </Button>
                </Link>
              ) : (
                <Link to="/login" onClick={() => setMobileMenuOpen(false)}>
                  <Button variant="primary" size="md" className="w-full" leftIcon={<ArrowRightOnRectangleIcon className="w-4 h-4" />}>
                    Masuk Sistem SIKOMANDO
                  </Button>
                </Link>
              )}
            </div>
          </div>
        )}
      </header>

      {/* Page Content Viewport */}
      <main className="flex-1">
        <Outlet />
      </main>

      {/* Official Government Footer */}
      <footer className="bg-slate-950 text-slate-300 border-t border-slate-800 pt-16 pb-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
          {/* Main Footer Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            {/* Col 1: Triple Brand Logo & Mission */}
            <div className="space-y-4">
              <TripleBrandHeader size="sm" theme="dark" />
              <p className="text-xs text-slate-400 leading-relaxed pt-2">
                Sistem Informasi Hibah Komando (SIKOMANDO) merupakan platform digital terpadu Pemerintah Provinsi Sulawesi Utara untuk menjamin pengelolaan hibah daerah yang transparan, akuntabel, tepat sasaran, dan bebas dari gratifikasi.
              </p>
              <div className="flex items-center gap-2 text-xs text-amber-400 font-medium pt-1">
                <ShieldCheckIcon className="w-4 h-4" />
                <span>Terintegrasi Audit Trail & Kriptografi QR</span>
              </div>
            </div>

            {/* Col 2: Akses Cepat */}
            <div>
              <h4 className="text-sm font-bold text-white uppercase tracking-wider mb-4 border-b border-slate-800 pb-2">
                Akses Layanan
              </h4>
              <ul className="space-y-2.5 text-xs text-slate-400">
                <li>
                  <Link to="/programs" className="hover:text-white transition">Program Hibah Aktif</Link>
                </li>
                <li>
                  <Link to="/tracker" className="hover:text-white transition">Lacak Progres Usulan</Link>
                </li>
                <li>
                  <Link to="/statistics" className="hover:text-white transition">Statistik Realisasi Anggaran</Link>
                </li>
                <li>
                  <Link to="/transparency" className="hover:text-white transition">Portal Transparansi Penerima</Link>
                </li>
                <li>
                  <Link to="/verify" className="hover:text-white transition">Uji Keabsahan Dokumen QR</Link>
                </li>
                <li>
                  <Link to="/login" className="hover:text-white transition">Masuk Staf & Ormas</Link>
                </li>
              </ul>
            </div>

            {/* Col 3: Regulasi & Dasar Hukum */}
            <div>
              <h4 className="text-sm font-bold text-white uppercase tracking-wider mb-4 border-b border-slate-800 pb-2">
                Landasan Regulasi
              </h4>
              <ul className="space-y-2.5 text-xs text-slate-400">
                <li>Permendagri No. 77 Tahun 2020 tentang Pedoman Teknis Pengelolaan Keuangan Daerah</li>
                <li>Peraturan Gubernur Sulawesi Utara tentang Tata Cara Penganggaran dan Pelaporan Hibah Daerah</li>
                <li>Standar Operasional Prosedur Biro Kesejahteraan Rakyat Setda Prov. Sulut</li>
                <li>Instruksi Gubernur tentang Integrasi Geospasial Bantuan Sosial & Hibah</li>
              </ul>
            </div>

            {/* Col 4: Kontak & Alamat Kantor */}
            <div>
              <h4 className="text-sm font-bold text-white uppercase tracking-wider mb-4 border-b border-slate-800 pb-2">
                Sekretariat Penyelenggara
              </h4>
              <div className="space-y-3 text-xs text-slate-400">
                <div className="flex items-start gap-2.5">
                  <MapPinIcon className="w-4 h-4 text-blue-400 shrink-0 mt-0.5" />
                  <span>
                    Kantor Gubernur Sulawesi Utara<br />
                    Biro Kesejahteraan Rakyat Setda Prov. Sulut<br />
                    Jl. 17 Agustus No. 69, Manado, Sulawesi Utara
                  </span>
                </div>
                <div className="flex items-center gap-2.5">
                  <PhoneIcon className="w-4 h-4 text-blue-400 shrink-0" />
                  <span>(0431) 862001 / Fax: (0431) 862002</span>
                </div>
                <div className="flex items-center gap-2.5">
                  <EnvelopeIcon className="w-4 h-4 text-blue-400 shrink-0" />
                  <span>kesra@sulutprov.go.id</span>
                </div>
              </div>
            </div>
          </div>

          {/* Bottom Copyright Bar */}
          <div className="pt-8 border-t border-slate-800 text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
              &copy; {new Date().getFullYear()} Pemerintah Provinsi Sulawesi Utara. Hak Cipta Dilindungi Undang-Undang.
            </div>
            <div className="flex items-center gap-4 text-[11px]">
              <span>Biro Kesejahteraan Rakyat Setda Prov. Sulut</span>
              <span>•</span>
              <span>Versi 1.0 (Feature-Driven)</span>
            </div>
          </div>
        </div>
      </footer>
    </div>
  );
}
