import React, { useEffect, useState } from 'react';
import {
  getDisbursements,
  getDisbursement,
  verifyDisbursement,
  approveDisbursement,
  recordDisbursementTransaction,
  getDisbursementPdfUrl,
} from '../api/disbursements';
import DataTable from '../components/common/DataTable';
import StatusBadge from '../components/common/StatusBadge';
import LoadingSpinner from '../components/common/LoadingSpinner';
import Modal from '../components/common/Modal';
import {
  BanknotesIcon,
  PrinterIcon,
  CheckBadgeIcon,
  DocumentCheckIcon,
  BuildingOffice2Icon,
} from '@heroicons/react/24/outline';

export default function DisbursementPage() {
  const [disbursements, setDisbursements] = useState([]);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [successMsg, setSuccessMsg] = useState('');

  // Transaction modal
  const [transModalOpen, setTransModalOpen] = useState(false);
  const [selectedDisbursement, setSelectedDisbursement] = useState(null);
  const [sp2dNumber, setSp2dNumber] = useState('');
  const [sp2dDate, setSp2dDate] = useState('');
  const [transAmount, setTransAmount] = useState('');

  async function loadDisbursements() {
    setLoading(true);
    try {
      const res = await getDisbursements();
      const list = res?.data || res || [];
      setDisbursements(Array.isArray(list) ? list : list.data || []);
    } catch (err) {
      console.error('Failed to load disbursements', err);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadDisbursements();
  }, []);

  async function handleVerify(id) {
    if (!window.confirm('Verifikasi kelengkapan berkas rekening dan NPWP pemohon?')) return;
    setActionLoading(true);
    try {
      await verifyDisbursement(id);
      setSuccessMsg('Berkas pencairan & rekening berhasil diverifikasi.');
      await loadDisbursements();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal memverifikasi.');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleApprove(id) {
    if (!window.confirm('Setujui proses penyaluran dana hibah ini?')) return;
    setActionLoading(true);
    try {
      await approveDisbursement(id);
      setSuccessMsg('Pencairan dana hibah disetujui.');
      await loadDisbursements();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menyetujui pencairan.');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleRecordSp2d(e) {
    e.preventDefault();
    if (!selectedDisbursement) return;
    setActionLoading(true);

    try {
      await recordDisbursementTransaction(selectedDisbursement.id, {
        transaction_number: sp2dNumber,
        sp2d_number: sp2dNumber,
        transaction_date: sp2dDate,
        amount: Number(transAmount),
      });

      setTransModalOpen(false);
      setSuccessMsg('Penerbitan SP2D dan pencatatan transaksi transfer berhasil disimpan!');
      await loadDisbursements();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal mencatat transaksi SP2D.');
    } finally {
      setActionLoading(false);
    }
  }

  const columns = [
    {
      title: 'Nomor Usulan & Lembaga',
      key: 'proposal',
      render: (val, row) => (
        <div>
          <span className="font-mono text-xs text-blue-600 font-semibold">
            #{row.proposal?.proposal_number || row.id?.slice(0, 8)}
          </span>
          <p className="font-bold text-slate-900">{row.proposal?.organization?.name || 'Organisasi Penerima'}</p>
          <p className="text-xs text-slate-500">{row.proposal?.title || 'Bantuan Hibah'}</p>
        </div>
      ),
    },
    {
      title: 'Rekening Bank Penerima',
      key: 'bank_account',
      render: (val, row) => (
        <div className="text-xs">
          <p className="font-semibold text-slate-800">{row.bank_name || 'Bank Pembangunan Daerah'}</p>
          <p className="font-mono text-slate-500">{row.bank_account_number || row.account_number || '—'}</p>
          <p className="text-[11px] text-slate-400">a.n. {row.bank_account_name || 'Lembaga'}</p>
        </div>
      ),
    },
    {
      title: 'Jumlah Pencairan (Rp)',
      key: 'amount',
      render: (val) => (
        <span className="font-bold text-emerald-700 text-sm">
          {val ? `Rp ${Number(val).toLocaleString('id-ID')}` : '—'}
        </span>
      ),
    },
    {
      title: 'Status Pencairan',
      key: 'status',
      render: (val) => <StatusBadge status={val || 'approved'} />,
    },
    {
      title: 'Aksi',
      key: 'action',
      render: (_, row) => (
        <div className="flex items-center gap-1.5">
          {row.status !== 'verified' && row.status !== 'disbursed' && (
            <button
              type="button"
              onClick={() => handleVerify(row.id)}
              disabled={actionLoading}
              className="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50"
            >
              Verifikasi Rekening
            </button>
          )}

          {row.status !== 'disbursed' && (
            <button
              type="button"
              onClick={() => {
                setSelectedDisbursement(row);
                setTransAmount(row.amount || '');
                setTransModalOpen(true);
              }}
              className="rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-700"
            >
              Catat SP2D
            </button>
          )}

          <a
            href={`http://127.0.0.1:8000/api/v1/pdf/disbursements/${row.id}`}
            target="_blank"
            rel="noopener noreferrer"
            className="rounded-md border border-slate-200 p-1 text-slate-600 hover:bg-slate-50"
            title="Cetak Bukti SP2D"
          >
            <PrinterIcon className="h-4 w-4" />
          </a>
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Manajemen Pencairan Dana & SP2D</h1>
        <p className="text-xs text-slate-500 mt-1">
          Verifikasi rekening bank lembaga, validasi NPWP, persetujuan termin, dan pencatatan nomor SP2D penyaluran.
        </p>
      </div>

      {successMsg && (
        <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-700">
          <CheckBadgeIcon className="h-5 w-5 shrink-0 text-emerald-600" />
          <span>{successMsg}</span>
        </div>
      )}

      <DataTable
        columns={columns}
        data={disbursements}
        loading={loading}
        searchPlaceholder="Cari nomor rekening, nama lembaga, atau usulan..."
        emptyTitle="Tidak Ada Antrean Pencairan"
        emptyDescription="Pencairan dana hibah diproses setelah SK penetapan diterbitkan."
      />

      {/* Modal SP2D */}
      <Modal
        isOpen={transModalOpen}
        onClose={() => setTransModalOpen(false)}
        title="Catat Nomor Penerbitan SP2D Bank"
      >
        <form onSubmit={handleRecordSp2d} className="space-y-4">
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Nomor SP2D Resmi *
            </label>
            <input
              type="text"
              value={sp2dNumber}
              onChange={(e) => setSp2dNumber(e.target.value)}
              placeholder="Contoh: SP2D/0421/BPKAD/2026"
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-xs font-mono focus:border-emerald-500 focus:outline-hidden"
            />
          </div>

          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Tanggal Transaksi Penyaluran *
            </label>
            <input
              type="date"
              value={sp2dDate}
              onChange={(e) => setSp2dDate(e.target.value)}
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-xs focus:border-emerald-500 focus:outline-hidden"
            />
          </div>

          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Nominal Transfer Realisasi (Rp) *
            </label>
            <input
              type="number"
              value={transAmount}
              onChange={(e) => setTransAmount(e.target.value)}
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-sm font-bold text-slate-900 focus:border-emerald-500 focus:outline-hidden"
            />
          </div>

          <div className="flex justify-end gap-2 pt-4">
            <button
              type="button"
              onClick={() => setTransModalOpen(false)}
              className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={actionLoading}
              className="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
            >
              {actionLoading ? 'Menyimpan...' : 'Simpan Transaksi SP2D'}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}

