import React from 'react';
import { Link } from 'react-router-dom';

export default function PublicFooter() {
  return (
    <footer className="border-t border-slate-200 bg-slate-900 text-slate-400">
      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 gap-8 md:grid-cols-4">
          <div className="md:col-span-2">
            <div className="flex items-center gap-3">
              <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-500 text-white font-bold">
                S
              </div>
              <span className="text-xl font-bold text-white tracking-tight">SIKOMANDO</span>
            </div>
            <p className="mt-3 max-w-md text-sm text-slate-400 leading-relaxed">
              Sistem Informasi Komprehensif Manajemen Digitalisasi Hibah Organisasi. Mewujudkan
              tata kelola hibah yang transparan, akuntabel, terukur, dan terintegrasi secara digital
              dengan QR traceability.
            </p>
          </div>

          <div>
            <h4 className="text-sm font-semibold text-white">Tautan Cepat</h4>
            <ul className="mt-3 space-y-2 text-sm">
              <li>
                <Link to="/programs" className="hover:text-white transition">
                  Program Hibah
                </Link>
              </li>
              <li>
                <Link to="/transparency" className="hover:text-white transition">
                  Transparansi Anggaran
                </Link>
              </li>
              <li>
                <Link to="/statistics" className="hover:text-white transition">
                  Statistik & Metrik
                </Link>
              </li>
              <li>
                <Link to="/verify" className="hover:text-white transition">
                  Cek Keaslian QR
                </Link>
              </li>
            </ul>
          </div>

          <div>
            <h4 className="text-sm font-semibold text-white">Akses Akun</h4>
            <ul className="mt-3 space-y-2 text-sm">
              <li>
                <Link to="/login" className="hover:text-white transition">
                  Login Pegawai & Pemohon
                </Link>
              </li>
              <li>
                <span className="text-xs text-slate-500">
                  Didukung enkripsi dokumen & digital signature
                </span>
              </li>
            </ul>
          </div>
        </div>

        <div className="mt-8 border-t border-slate-800 pt-6 text-center text-xs text-slate-500">
          © {new Date().getFullYear()} SIKOMANDO. Hak Cipta Dilindungi Undang-Undang.
        </div>
      </div>
    </footer>
  );
}

