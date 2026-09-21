import React, { useState, useEffect } from 'react';
import api from '../../services/api';
import { formatDateTime } from '../../utils/formatters';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody } from '../../components/ui/Card';
import Table, { TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '../../components/ui/Table';
import Spinner from '../../components/feedback/Spinner';
import EmptyState from '../../components/feedback/EmptyState';
import {
  ShieldCheckIcon,
  MagnifyingGlassIcon,
  ChevronDownIcon,
  ChevronUpIcon,
} from '@heroicons/react/24/outline';

export function AuditLogListPage() {
  const [logs, setLogs] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [expandedId, setExpandedId] = useState(null);

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
          setLogs(list);
        }
      } catch (err) {
        console.error('Failed to load audit logs:', err);
        if (isMounted) setLogs([]);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadAuditLogs();
    return () => { isMounted = false; };
  }, []);

  const filtered = (Array.isArray(logs) ? logs : []).filter((l) => {
    const user = l.actor?.name || l.user?.name || l.causer_name || '';
    const act = l.action || l.event || '';
    const mod = l.module || '';
    const ip = l.ip_address || '';
    const type = l.entity_type || l.auditable_type || '';
    return (
      user.toLowerCase().includes(search.toLowerCase()) ||
      act.toLowerCase().includes(search.toLowerCase()) ||
      mod.toLowerCase().includes(search.toLowerCase()) ||
      ip.toLowerCase().includes(search.toLowerCase()) ||
      type.toLowerCase().includes(search.toLowerCase())
    );
  });

  const toggleExpand = (id) => {
    setExpandedId(expandedId === id ? null : id);
  };

  return (
    <div className="space-y-6 pb-20">
      <PageHeader
        title="Rekam Jejak Audit Sistem (Audit Trail Log)"
        subtitle="Log kepatuhan forensik digital, pencatatan otentikasi, dan perubahan data seluruh modul SIKOMANDO"
        breadcrumbs={[{ label: 'Audit Log' }]}
      />

      {/* Search Bar */}
      <Card className="border-slate-200">
        <CardBody className="p-4">
          <div className="relative max-w-md">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
              <MagnifyingGlassIcon className="w-4 h-4" />
            </div>
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Cari nama pengguna, tindakan, IP address, atau modul..."
              className="w-full pl-9 pr-3 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:bg-white focus:border-blue-500"
            />
          </div>
        </CardBody>
      </Card>

      {/* Table */}
      <Card className="border-slate-200 shadow-xs">
        {isLoading ? (
          <Spinner label="Memuat rekam jejak audit..." />
        ) : filtered.length === 0 ? (
          <div className="p-12">
            <EmptyState
              icon={ShieldCheckIcon}
              title="Tidak Ada Entri Audit Log"
              description="Belum ada aktivitas yang tercatat sesuai pencarian."
            />
          </div>
        ) : (
          <Table>
            <TableHead>
              <tr>
                <TableHeaderCell>Waktu (WITA)</TableHeaderCell>
                <TableHeaderCell>Aktor / Pengguna</TableHeaderCell>
                <TableHeaderCell>Aksi (Action)</TableHeaderCell>
                <TableHeaderCell>Modul / Entitas</TableHeaderCell>
                <TableHeaderCell>Alamat IP</TableHeaderCell>
                <TableHeaderCell className="text-right">Rincian</TableHeaderCell>
              </tr>
            </TableHead>
            <TableBody>
              {filtered.map((log) => (
                <React.Fragment key={log.id}>
                  <TableRow hover onClick={() => toggleExpand(log.id)}>
                    <TableCell mono className="text-slate-500 text-xs">
                      {formatDateTime(log.occurred_at || log.created_at)}
                    </TableCell>
                    <TableCell>
                      <div className="font-bold text-slate-900">
                        {log.actor?.name || log.user?.name || log.causer_name || 'Sistem'}
                      </div>
                      <div className="text-[11px] text-slate-400">
                        {log.actor?.email || log.user?.email || '-'}
                      </div>
                    </TableCell>
                    <TableCell>
                      <span className="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-800">
                        {log.action || log.event || 'UPDATE'}
                      </span>
                    </TableCell>
                    <TableCell>
                      <span className="text-xs text-slate-700 font-medium">
                        {log.module ? `[${log.module}] ` : ''}
                        {log.entity_type?.split('\\').pop() || log.auditable_type?.split('\\').pop() || 'Data Usulan'}
                      </span>
                    </TableCell>
                    <TableCell mono className="text-xs text-slate-600">
                      {log.ip_address || '127.0.0.1'}
                    </TableCell>
                    <TableCell className="text-right">
                      <button
                        type="button"
                        onClick={(e) => {
                          e.stopPropagation();
                          toggleExpand(log.id);
                        }}
                        className="p-1 text-slate-400 hover:text-slate-700 transition"
                      >
                        {expandedId === log.id ? <ChevronUpIcon className="w-4 h-4" /> : <ChevronDownIcon className="w-4 h-4" />}
                      </button>
                    </TableCell>
                  </TableRow>

                  {/* Expanded JSON details */}
                  {expandedId === log.id && (
                    <tr>
                      <td colSpan={6} className="bg-slate-900 text-slate-200 p-4 font-mono text-xs overflow-x-auto">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                          {log.old_values && Object.keys(log.old_values).length > 0 && (
                            <div>
                              <div className="text-[10px] text-red-400 mb-1 font-bold uppercase">
                                Nilai Lama (Before):
                              </div>
                              <pre className="whitespace-pre-wrap leading-relaxed text-red-200 bg-slate-950 p-2 rounded">
                                {JSON.stringify(log.old_values, null, 2)}
                              </pre>
                            </div>
                          )}
                          {log.new_values && Object.keys(log.new_values).length > 0 && (
                            <div>
                              <div className="text-[10px] text-emerald-400 mb-1 font-bold uppercase">
                                Nilai Baru (After):
                              </div>
                              <pre className="whitespace-pre-wrap leading-relaxed text-emerald-200 bg-slate-950 p-2 rounded">
                                {JSON.stringify(log.new_values, null, 2)}
                              </pre>
                            </div>
                          )}
                        </div>
                        <div className="text-[10px] text-slate-400 mb-1 font-bold uppercase">
                          Metadata & Konteks Permintaan:
                        </div>
                        <pre className="whitespace-pre-wrap leading-relaxed bg-slate-950 p-2 rounded">
                          {JSON.stringify(log.metadata || log.properties || { request_id: log.request_id, user_agent: log.user_agent }, null, 2)}
                        </pre>
                      </td>
                    </tr>
                  )}
                </React.Fragment>
              ))}
            </TableBody>
          </Table>
        )}
      </Card>
    </div>
  );
}

export default AuditLogListPage;
