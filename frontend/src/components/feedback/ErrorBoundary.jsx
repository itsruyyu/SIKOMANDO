import React from 'react';
import { ExclamationTriangleIcon, ArrowPathIcon } from '@heroicons/react/24/outline';

export class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  componentDidCatch(error, errorInfo) {
    console.error('ErrorBoundary caught a runtime rendering error:', error, errorInfo);
  }

  handleReload = () => {
    window.location.reload();
  };

  handleReset = () => {
    this.setState({ hasError: false, error: null });
  };

  render() {
    if (this.state.hasError) {
      return (
        <div className="min-h-[400px] flex items-center justify-center p-6 bg-slate-50">
          <div className="max-w-md w-full bg-white rounded-2xl p-6 shadow-xl border border-red-100 text-center space-y-4">
            <div className="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto text-red-600">
              <ExclamationTriangleIcon className="w-8 h-8" />
            </div>

            <div className="space-y-1">
              <h3 className="text-lg font-bold text-slate-900">
                Terjadi Kendala Tampilan
              </h3>
              <p className="text-xs text-slate-500 leading-relaxed">
                Aplikasi mengalami kesalahan tak terduga saat merender tampilan ini. Silakan muat ulang halaman atau kembali ke beranda.
              </p>
            </div>

            {this.state.error?.message && (
              <div className="bg-slate-100 p-3 rounded-lg text-left text-[11px] font-mono text-slate-700 overflow-x-auto max-h-24">
                {this.state.error.message}
              </div>
            )}

            <div className="flex justify-center gap-3 pt-2">
              <button
                type="button"
                onClick={this.handleReload}
                className="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-sm transition"
              >
                <ArrowPathIcon className="w-4 h-4" />
                Muat Ulang Halaman
              </button>
              <button
                type="button"
                onClick={() => (window.location.href = '/dashboard')}
                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition"
              >
                Ke Dashboard
              </button>
            </div>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}

export default ErrorBoundary;

