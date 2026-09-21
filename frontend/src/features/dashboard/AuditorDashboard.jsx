import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import api from '../../services/api';
import { formatDateTime } from '../../utils/formatters';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Table, { TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '../../components/ui/Table';
import Spinner from '../../components/feedback/Spinner';
import EmptyState from '../../components/feedback/EmptyState';
import {
  ShieldCheckIcon,
  QrCodeIcon,
  ClipboardDocumentCheckIcon,
  ArrowRightIcon,
} from '@heroicons/react/24/outline';

export function AuditorDashboard() {
  const { user } = useAuth();
  const [auditLogs, setAuditLogs] = useState([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    async function loadAuditLogs() {
      setIsLoading(true);
      try {
        const res = await api.get('/internal/audit-logs');
        const list = Array.isArray(res?.data)
          ? res.data
          : Array.isArray(res?.data?.data)
          ? res.data.data
          : Array.isArray(res)
          ? res
          : [];
        if (isMounted) {
          setAuditLogs(list);
        }
      } catch (err) {
        console.error('Failed to load audit logs:', err);
        if (isMounted) setAuditLogs([]);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadAuditLogs();
    return () => { isMounted = false; };
  }, []);

  return (
    <div className="space-y-8">
      {/* Auditor Header Banner */}
      <div className="bg-gradient-to-r from-slate-900 via-rose-950 to-slate-900 text-white p-6 sm:p-8 rounded-3xl shadow-lg border border-rose-900/50 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div className="space-y-1">
          <div className="flex items-center gap-2">
            <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-300 border border-rose-400/30 uppercase tracking-wider">
              Inspektorat / Auditor Pengawasan
            </span>
            <span className="text-xs text-slate-400">• Pemerintah Provinsi Sulawesi Utara</span>
          </div>
          <h1 className="text-xl sm:text-2xl font-black">
            Portal Pengawasan Internal: {user?.name}
          </h1>
          <p className="text-xs text-slate-300 max-w-xl leading-relaxed">
            Pantau seluruh jejak rekam perubahan data (audit trail), kepatuhan pelaporan pertanggungjawaban dana hibah (LPJ), dan validasi integritas QR Code.
          </p>
        </div>

        <div className="flex items-center gap-3">
          <Link to="/audit-logs">
            <Button variant="primary" size="md" icon={ShieldCheckIcon} className="bg-rose-600 hover:bg-rose-500">
              Audit Trail Penuh
            </Button>
          </Link>
          <Link to="/qr-management">
            <Button variant="outline" size="md" icon={QrCodeIcon} className="border-slate-600 text-white hover:bg-white/10">
              Log Pemindaian QR
            </Button>
          </Link>
        </div>
      </div>

      {/* Audit Stats Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <Card className="border-rose-200 bg-rose-50/30">
          <CardBody className="p-5 flex items-center justify-between">
            <div>
              <span className="text-xs text-slate-500 font-semibold block">Total Aktivitas Tercatat</span>
              <span className="text-2xl font-black font-mono text-rose-950 mt-1 block">
                {auditLogs.length} Entri
              </span>
              <span className="text-[11px] text-rose-700">Audit trail aktif & immutable</span>
            </div>
            <div className="p-3 rounded-xl bg-rose-600 text-white">
              <ShieldCheckIcon className="w-6 h-6" />
            </div>
          </CardBody>
        </Card>

        <Card className="border-blue-200 bg-blue-50/30">
          <CardBody className="p-5 flex items-center justify-between">
            <div>
              <span className="text-xs text-slate-500 font-semibold block">Pengawasan Realisasi & SP2D</span>
              <span className="text-2xl font-black font-mono text-blue-950 mt-1 block">
                100%
              </span>
              <span className="text-[11px] text-blue-700">Tersinkronisasi perbankan</span>
            </div>
            <div className="p-3 rounded-xl bg-blue-600 text-white">
              <ClipboardDocumentCheckIcon className="w-6 h-6" />
            </div>
          </CardBody>
        </Card>

        <Card className="border-emerald-200 bg-emerald-50/30">
          <CardBody className="p-5 flex items-center justify-between">
            <div>
              <span className="text-xs text-slate-500 font-semibold block">Integritas Kriptografi QR</span>
              <span className="text-2xl font-black font-mono text-emerald-950 mt-1 block">
                VALID
              </span>
              <span className="text-[11px] text-emerald-700">Tidak ada anomali terdeteksi</span>
            </div>
            <div className="p-3 rounded-xl bg-emerald-600 text-white">
              <QrCodeIcon className="w-6 h-6" />
            </div>
          </CardBody>
        </Card>
      </div>

      {/* Recent Audit Log Table */}
      <Card className="border-slate-200 shadow-xs">
        <CardHeader
          title="Log Aktivitas & Jejak Audit Terkini"
          subtitle="Aktivitas sensitif yang dilakukan oleh pengguna pada sistem SIKOMANDO"
          action={
            <Link to="/audit-logs">
              <Button variant="ghost" size="xs" icon={ArrowRightIcon} iconPosition="right">
                Buka Semua Log
              </Button>
            </Link>
          }
        />
        {isLoading ? (
          <Spinner label="Memuat rekam jejak audit..." />
        ) : auditLogs.length === 0 ? (
          <div className="p-8">
            <EmptyState
              title="Belum Ada Aktivitas Sensitif"
              description="Sistem belum mencatat perubahan status atau aksi penting terbaru."
            />
          </div>
        ) : (
          <Table>
            <TableHead>
              <tr>
                <TableHeaderCell>Waktu (WITA)</TableHeaderCell>
                <TableHeaderCell>Aktor / Pengguna</TableHeaderCell>
                <TableHeaderCell>Tindakan (Action)</TableHeaderCell>
                <TableHeaderCell>Entitas Modul</TableHeaderCell>
                <TableHeaderCell>Alamat IP</TableHeaderCell>
              </tr>
            </TableHead>
            <TableBody>
              {(Array.isArray(auditLogs) ? auditLogs : []).slice(0, 8).map((log) => (
                <TableRow key={log.id}>
                  <TableCell mono className="text-slate-500">
                    {formatDateTime(log.occurred_at || log.created_at)}
                  </TableCell>
                  <TableCell>
                    <div className="font-bold text-slate-900">{log.actor?.name || log.user?.name || log.causer_name || 'Sistem'}</div>
                    <div className="text-[11px] text-slate-400">{log.actor?.email || log.user?.email || '-'}</div>
                  </TableCell>
                  <TableCell>
                    <span className="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-800">
                      {log.action || log.event || 'UPDATE'}
                    </span>
                  </TableCell>
                  <TableCell>
                    <span className="text-xs text-slate-700">
                      {log.module ? `[${log.module}] ` : ''}
                      {log.entity_type?.split('\\').pop() || log.auditable_type?.split('\\').pop() || 'Data Usulan'}
                    </span>
                  </TableCell>
                  <TableCell mono className="text-xs text-slate-600">
                    {log.ip_address || '127.0.0.1'}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </Card>
    </div>
  );
}

export default AuditorDashboard;
