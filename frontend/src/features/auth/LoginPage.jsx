import React, { useState } from 'react';
import { useNavigate, useLocation, Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { TripleBrandHeader } from '../../components/ui/Logos';
import Input from '../../components/ui/Input';
import Button from '../../components/ui/Button';
import Alert from '../../components/feedback/Alert';
import {
  LockClosedIcon,
  EnvelopeIcon,
  ArrowRightOnRectangleIcon,
  ShieldCheckIcon,
  ArrowLeftIcon,
  SparklesIcon,
} from '@heroicons/react/24/outline';

export function LoginPage() {
  const { login } = useAuth();
  const toast = useToast();
  const navigate = useNavigate();
  const location = useLocation();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [remember, setRemember] = useState(true);
  const [isLoading, setIsLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  const redirectUrl = new URLSearchParams(location.search).get('redirect') || '/dashboard';

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrorMsg('');
    setIsLoading(true);

    try {
      await login(email, password, remember);
      toast.success('Login berhasil. Selamat datang di SIKOMANDO!');
      navigate(redirectUrl, { replace: true });
    } catch (err) {
      setErrorMsg(err.message || 'Kredensial login tidak valid.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleQuickFill = (demoEmail, demoPassword = 'Password') => {
    setEmail(demoEmail);
    setPassword(demoPassword);
    setErrorMsg('');
  };

  const demoAccounts = [
    { role: 'Super Admin', email: 'admin@sikomando.test', color: 'border-purple-200 bg-purple-50 text-purple-800' },
    { role: 'Verifikator', email: 'verifikator@sikomando.test', color: 'border-cyan-200 bg-cyan-50 text-cyan-800' },
    { role: 'Evaluator', email: 'evaluator@sikomando.test', color: 'border-indigo-200 bg-indigo-50 text-indigo-800' },
    { role: 'Surveyor', email: 'surveyor@sikomando.test', color: 'border-amber-200 bg-amber-50 text-amber-800' },
    { role: 'Approver', email: 'approver@sikomando.test', color: 'border-emerald-200 bg-emerald-50 text-emerald-800' },
    { role: 'Auditor', email: 'auditor@sikomando.test', color: 'border-rose-200 bg-rose-50 text-rose-800' },
    { role: 'Pemohon 1', email: 'pemohon@sikomando.test', color: 'border-slate-200 bg-slate-50 text-slate-800' },
    { role: 'Pemohon 3 (Sanggar Seni)', email: 'pemohon3@sikomando.test', color: 'border-blue-200 bg-blue-50 text-blue-800' },
  ];

  return (
    <div className="min-h-screen bg-slate-950 flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative overflow-hidden">
      {/* Background Glows */}
      <div className="absolute top-0 left-1/4 w-96 h-96 bg-blue-600/15 rounded-full blur-3xl pointer-events-none" />
      <div className="absolute bottom-0 right-1/4 w-96 h-96 bg-indigo-600/15 rounded-full blur-3xl pointer-events-none" />

      {/* Return to Public Portal */}
      <div className="absolute top-6 left-6 z-10">
        <Link
          to="/"
          className="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition bg-slate-900/80 px-3 py-2 rounded-lg border border-slate-800"
        >
          <ArrowLeftIcon className="w-3.5 h-3.5" />
          <span>Kembali ke Portal Publik</span>
        </Link>
      </div>

      <div className="sm:mx-auto sm:w-full sm:max-w-md relative z-10 text-center px-4">
        {/* Brand Header with 3 Logos */}
        <div className="flex justify-center mb-4">
          <TripleBrandHeader size="lg" theme="dark" />
        </div>
        <h2 className="text-xl font-black text-white tracking-tight sm:text-2xl mt-3">
          Masuk ke Sistem SIKOMANDO
        </h2>
        <p className="mt-1 text-xs text-slate-400">
          Pemerintah Provinsi Sulawesi Utara • Biro Kesejahteraan Rakyat
        </p>
      </div>

      <div className="mt-6 sm:mx-auto sm:w-full sm:max-w-md relative z-10 px-4">
        <div className="bg-white py-8 px-6 sm:px-8 shadow-2xl rounded-2xl border border-slate-200">
          {errorMsg && (
            <Alert type="danger" className="mb-5">
              {errorMsg}
            </Alert>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <Input
              label="Alamat Email Terdaftar"
              name="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="nama@sulutprov.go.id / ormas@email.com"
              icon={EnvelopeIcon}
              required
            />

            <Input
              label="Kata Sandi (Password)"
              name="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              icon={LockClosedIcon}
              required
            />

            <div className="flex items-center justify-between text-xs pt-1">
              <label className="flex items-center gap-2 text-slate-600 cursor-pointer">
                <input
                  type="checkbox"
                  checked={remember}
                  onChange={(e) => setRemember(e.target.checked)}
                  className="rounded-sm border-slate-300 text-blue-600 focus:ring-blue-500"
                />
                <span>Ingat sesi saya</span>
              </label>
              <span className="text-slate-400 cursor-not-allowed">Lupa password?</span>
            </div>

            <Button
              type="submit"
              variant="primary"
              size="lg"
              className="w-full mt-2"
              isLoading={isLoading}
              icon={ArrowRightOnRectangleIcon}
            >
              Masuk Sekarang
            </Button>
          </form>

          {/* Quick Demo Switcher */}
          <div className="mt-6 pt-6 border-t border-slate-100">
            <div className="flex items-center gap-1.5 text-xs font-bold text-slate-700 mb-2">
              <SparklesIcon className="w-4 h-4 text-amber-500" />
              <span>Akun Uji Coba Cepat (One-Click Demo)</span>
            </div>
            <p className="text-[11px] text-slate-500 mb-3 leading-relaxed">
              Pilih salah satu peran di bawah ini untuk mengisi kredensial secara otomatis (Password: <code className="font-mono bg-slate-100 px-1 py-0.5 rounded text-slate-800">Password</code>):
            </p>
            <div className="grid grid-cols-2 gap-1.5">
              {demoAccounts.map((acc) => (
                <button
                  key={acc.email}
                  type="button"
                  onClick={() => handleQuickFill(acc.email)}
                  className={`text-left px-2.5 py-1.5 rounded-lg border text-[11px] font-semibold transition hover:brightness-95 cursor-pointer ${acc.color}`}
                >
                  <div className="font-bold truncate">{acc.role}</div>
                  <div className="text-[10px] opacity-75 truncate">{acc.email.split('@')[0]}</div>
                </button>
              ))}
            </div>
          </div>
        </div>

        {/* Security badge */}
        <div className="mt-6 text-center text-slate-500 text-[11px] flex items-center justify-center gap-1.5">
          <ShieldCheckIcon className="w-4 h-4 text-emerald-400" />
          <span>Sistem diamankan dengan token autentikasi Sanctum & enkripsi data.</span>
        </div>
      </div>
    </div>
  );
}

export default LoginPage;

