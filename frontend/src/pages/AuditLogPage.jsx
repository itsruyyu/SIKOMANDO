import React, { useEffect, useState } from 'react';
import { getAuditLogs, getAuditLog } from '../api/auditLogs';
import DataTable from '../components/common/DataTable';
import LoadingSpinner from '../components/common/LoadingSpinner';
import Modal from '../components/common/Modal';
import { ShieldCheckIcon, EyeIcon } from '@heroicons/react/24/outline';

export default function AuditLogPage() {
  const [logs, setLogs] = useState([]);
  const [selectedLog, setSelectedLog] = useState(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [loading, setLoading] = useState(true);

  async function loadLogs() {
    setLoading(true);
    try {
      const res = await getAuditLogs();
      const list = res?.data || res || [];
      setLogs(Array.isArray(list) ? list : list.data || []);
    } catch (err) {
      console.error('Failed to load audit logs', err);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadLogs();
  }, []);

  async function handleViewDetail(log) {
    setSelectedLog(log);
    setModalOpen(true);
  }

  const columns = [
    {
      title: 'Waktu Aktivitas',
      key: 'created_at',
      render: (val) => <span className="font-mono text-xs text-slate-500">{val || '—'}</span>,
    },
    {
      title: 'Aktor / Pengguna',
      key: 'user',
      render: (val, row) => (
        <div>
          <p className="font-bold text-slate-900">{row.user?.name || row.causer_name || 'Sistem Otomatis'}</p>
          <p className="text-[11px] text-slate-500">{row.ip_address || '127.0.0.1'}</p>
        </div>
      ),
    },
    {
      title: 'Aksi / Modul',
      key: 'action',
      render: (val, row) => (
        <div>
          <span className="font-mono text-xs font-semibold text-blue-700">
            {val || row.description || 'proposal.action'}
          </span>
          <p className="text-[11px] text-slate-500">{row.entity_type || 'Proposal'}</p>
        </div>
      ),
    },
    {
      title: 'Status Integritas',
      key: 'integrity',
      render: () => (
        <span className="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200">
          Valid & Terkunci
        </span>
      ),
    },
    {
      title: 'Aksi',
      key: 'action_btn',
      render: (_, row) => (
        <button
          type="button"
          onClick={() => handleViewDetail(row)}
          className="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50"
        >
          <EyeIcon className="h-3.5 w-3.5" />
          <span>Detail Diff</span>
        </button>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Audit Trail Forensik Sistem</h1>
        <p className="text-xs text-slate-500 mt-1">
          Rekam jejak seluruh mutasi data, otorisasi pimpinan, status persetujuan, dan perubahan nilai usulan.
        </p>
      </div>

      <DataTable
        columns={columns}
        data={logs}
        loading={loading}
        searchPlaceholder="Cari riwayat aksi, nama pengguna, atau IP..."
        emptyTitle="Belum Ada Log Audit"
        emptyDescription="Seluruh aktivitas sistem direkam secara otomatis dan tidak dapat dihapus."
      />

      {/* DETAIL MODAL */}
      <Modal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        title="Detail Audit Log Perubahan Data"
        maxWidth="max-w-2xl"
      >
        {selectedLog && (
          <div className="space-y-4 text-xs">
            <div className="grid grid-cols-2 gap-4 rounded-xl border border-slate-100 bg-slate-50 p-3">
              <div>
                <p className="text-slate-500">ID Entitas Target</p>
                <p className="font-mono font-bold text-slate-800 break-all">{selectedLog.entity_id || selectedLog.id}</p>
              </div>
              <div>
                <p className="text-slate-500">IP & User Agent</p>
                <p className="font-mono text-slate-800">{selectedLog.ip_address || '127.0.0.1'}</p>
              </div>
            </div>

            <div>
              <p className="font-bold text-slate-700 uppercase">Nilai Sebelum (Old Values):</p>
              <pre className="mt-1 max-h-40 overflow-y-auto rounded-lg bg-slate-900 p-3 font-mono text-[11px] text-emerald-400">
                {JSON.stringify(selectedLog.old_values || {}, null, 2)}
              </pre>
            </div>

            <div>
              <p className="font-bold text-slate-700 uppercase">Nilai Sesudah (New Values):</p>
              <pre className="mt-1 max-h-40 overflow-y-auto rounded-lg bg-slate-900 p-3 font-mono text-[11px] text-sky-400">
                {JSON.stringify(selectedLog.new_values || {}, null, 2)}
              </pre>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
}

