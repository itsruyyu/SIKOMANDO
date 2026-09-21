import React, { useState, useEffect } from 'react';
import api from '../../services/api';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Badge from '../../components/ui/Badge';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import { formatDate, formatDateTime } from '../../utils/formatters';
import {
  CpuChipIcon,
  ServerStackIcon,
  CircleStackIcon,
  ShieldCheckIcon,
  ArrowPathIcon,
  ClockIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
} from '@heroicons/react/24/outline';

export default function SystemMonitoringPage() {
  const [metrics, setMetrics] = useState(null);
  const [health, setHealth] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [lastRefreshed, setLastRefreshed] = useState(new Date());

  const fetchMetrics = async () => {
    try {
      const [metricRes, healthRes] = await Promise.allSettled([
        api.get('/system/metrics'),
        api.get('/health'),
      ]);

      if (metricRes.status === 'fulfilled') {
        setMetrics(metricRes.value?.data || null);
      }

      if (healthRes.status === 'fulfilled') {
        setHealth(healthRes.value?.data || null);
      }

      setLastRefreshed(new Date());
    } catch (err) {
      console.error('Failed to load system metrics:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchMetrics();
    const interval = setInterval(fetchMetrics, 30000); // Polling every 30s
    return () => clearInterval(interval);
  }, []);

  return (
    <div className="space-y-6">
      <PageHeader
        title="Pemantauan Performa & Kesehatan Sistem"
        subtitle="Metrik telemetri langsung basis data PostgreSQL, memori runtime, dan status layanan SIKOMANDO"
        breadcrumbs={[{ label: 'Tata Kelola' }, { label: 'Pemantauan Sistem' }]}
        action={
          <div className="flex items-center gap-3">
            <span className="text-xs text-slate-400 flex items-center gap-1">
              <ClockIcon className="w-3.5 h-3.5" />
              Diperbarui: {lastRefreshed.toLocaleTimeString()}
            </span>
            <Button
              variant="secondary"
              size="sm"
              onClick={fetchMetrics}
              className="flex items-center gap-1.5"
            >
              <ArrowPathIcon className="w-4 h-4" />
              Segarkan
            </Button>
          </div>
        }
      />

      {isLoading ? (
        <div className="py-16 flex justify-center">
          <Spinner size="lg" label="Mengumpulkan telemetri sistem..." />
        </div>
      ) : (
        <div className="space-y-6">
          {/* Status Overview Cards */}
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <Card className="p-4 border-slate-200">
              <div className="flex items-center justify-between">
                <span className="text-xs font-semibold text-slate-500 uppercase">
                  Status Sistem
                </span>
                <span className="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse" />
              </div>
              <div className="text-2xl font-black text-slate-900 mt-2 flex items-center gap-2">
                {health?.status === 'healthy' ? 'OPTIMAL' : 'DEGRADED'}
              </div>
              <div className="text-[11px] text-emerald-600 font-medium mt-1">
                Semua sub-layanan merespons
              </div>
            </Card>

            <Card className="p-4 border-slate-200">
              <div className="text-xs font-semibold text-slate-500 uppercase">
                Latensi Basis Data
              </div>
              <div className="text-2xl font-black text-blue-600 mt-2">
                {metrics?.database?.latency_ms ?? '-'} ms
              </div>
              <div className="text-[11px] text-slate-500 mt-1">
                Query round-trip PostgreSQL
              </div>
            </Card>

            <Card className="p-4 border-slate-200">
              <div className="text-xs font-semibold text-slate-500 uppercase">
                Penggunaan Memori PHP
              </div>
              <div className="text-2xl font-black text-purple-600 mt-2">
                {metrics?.memory?.current_mb ?? '-'} MB
              </div>
              <div className="text-[11px] text-slate-500 mt-1">
                Puncak: {metrics?.memory?.peak_mb ?? '-'} MB
              </div>
            </Card>

            <Card className="p-4 border-slate-200">
              <div className="text-xs font-semibold text-slate-500 uppercase">
                Integritas Jejak Audit
              </div>
              <div className="text-2xl font-black text-emerald-600 mt-2 flex items-center gap-1.5">
                <ShieldCheckIcon className="w-7 h-7 text-emerald-600" />
                IMMUTABLE
              </div>
              <div className="text-[11px] text-slate-500 mt-1">
                {metrics?.statistics?.total_audit_logs ?? 0} log tak dapat diubah
              </div>
            </Card>
          </div>

          {/* Service Details Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Database & Environment */}
            <Card className="border-slate-200 p-6 space-y-4">
              <h3 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                <CircleStackIcon className="w-5 h-5 text-blue-600" />
                Lingkungan Basis Data & Runtime
              </h3>
              <div className="border-t pt-3 space-y-3 text-xs">
                <div className="flex justify-between py-1 border-b border-slate-100">
                  <span className="text-slate-500">Database Engine:</span>
                  <span className="font-semibold text-slate-800">
                    {metrics?.database?.engine || 'PostgreSQL'}
                  </span>
                </div>
                <div className="flex justify-between py-1 border-b border-slate-100">
                  <span className="text-slate-500">Versi PHP:</span>
                  <span className="font-mono font-semibold text-slate-800">
                    PHP {metrics?.php_version || '-'}
                  </span>
                </div>
                <div className="flex justify-between py-1 border-b border-slate-100">
                  <span className="text-slate-500">Versi Framework:</span>
                  <span className="font-mono font-semibold text-slate-800">
                    Laravel {metrics?.laravel_version || '-'}
                  </span>
                </div>
                <div className="flex justify-between py-1 border-b border-slate-100">
                  <span className="text-slate-500">Driver Cache & Sesi:</span>
                  <span className="font-semibold text-emerald-600">Database / Cache</span>
                </div>
                <div className="flex justify-between py-1">
                  <span className="text-slate-500">Versi PostgreSQL Host:</span>
                  <span className="font-mono text-[10px] text-slate-700 max-w-xs text-right truncate">
                    {metrics?.database?.version || 'PostgreSQL'}
                  </span>
                </div>
              </div>
            </Card>

            {/* Application Statistics */}
            <Card className="border-slate-200 p-6 space-y-4">
              <h3 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                <ServerStackIcon className="w-5 h-5 text-indigo-600" />
                Volume Data & Objek Sistem
              </h3>
              <div className="border-t pt-3 space-y-3 text-xs">
                <div className="flex justify-between py-1 border-b border-slate-100">
                  <span className="text-slate-500">Total Pengguna Terdaftar:</span>
                  <span className="font-semibold text-slate-800">
                    {metrics?.statistics?.total_users ?? 0} Akun
                  </span>
                </div>
                <div className="flex justify-between py-1 border-b border-slate-100">
                  <span className="text-slate-500">Total Usulan Hibah:</span>
                  <span className="font-semibold text-slate-800">
                    {metrics?.statistics?.total_proposals ?? 0} Berkas
                  </span>
                </div>
                <div className="flex justify-between py-1 border-b border-slate-100">
                  <span className="text-slate-500">Total Tanda Tangan Sah (TTE):</span>
                  <span className="font-semibold text-slate-800">
                    {metrics?.statistics?.total_signatures ?? 0} TTE Terdaftar
                  </span>
                </div>
                <div className="flex justify-between py-1 border-b border-slate-100">
                  <span className="text-slate-500">Catatan Jejak Audit:</span>
                  <span className="font-semibold text-emerald-600">
                    {metrics?.statistics?.total_audit_logs ?? 0} Baris (Append-Only)
                  </span>
                </div>
                <div className="flex justify-between py-1">
                  <span className="text-slate-500">Waktu Stempel Server (WITA):</span>
                  <span className="font-mono text-slate-700">
                    {metrics?.timestamp ? formatDateTime(metrics.timestamp) : '-'}
                  </span>
                </div>
              </div>
            </Card>
          </div>
        </div>
      )}
    </div>
  );
}

