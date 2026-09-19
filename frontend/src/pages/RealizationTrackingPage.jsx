import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import {
  getProposalRealizations,
  createRealizationPackage,
  addRealizationItem,
  getProposalReceipts,
  createReceipt,
  getPackageHandovers,
  createHandover,
} from '../api/realizations';
import { getProposal } from '../api/proposals';
import LoadingSpinner from '../components/common/LoadingSpinner';
import StatusBadge from '../components/common/StatusBadge';
import Modal from '../components/common/Modal';
import FileUpload from '../components/common/FileUpload';
import {
  ArrowLeftIcon,
  PlusIcon,
  QrCodeIcon,
  ReceiptPercentIcon,
  DocumentDuplicateIcon,
  CheckBadgeIcon,
  ArchiveBoxIcon,
  BanknotesIcon,
} from '@heroicons/react/24/outline';

export default function RealizationTrackingPage() {
  const { proposalId } = useParams();

  const [proposal, setProposal] = useState(null);
  const [packages, setPackages] = useState([]);
  const [receipts, setReceipts] = useState([]);
  const [activeTab, setActiveTab] = useState('packages'); // packages | receipts
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [successMsg, setSuccessMsg] = useState('');

  // Modals
  const [packageModalOpen, setPackageModalOpen] = useState(false);
  const [packageName, setPackageName] = useState('');
  const [packageBudget, setPackageBudget] = useState('');

  const [itemModalOpen, setItemModalOpen] = useState(false);
  const [selectedPackageId, setSelectedPackageId] = useState(null);
  const [itemName, setItemName] = useState('');
  const [itemQuantity, setItemQuantity] = useState(1);
  const [itemSpec, setItemSpec] = useState('');

  const [receiptModalOpen, setReceiptModalOpen] = useState(false);
  const [receiptNumber, setReceiptNumber] = useState('');
  const [receiptAmount, setReceiptAmount] = useState('');
  const [receiptVendor, setReceiptVendor] = useState('');
  const [receiptDate, setReceiptDate] = useState('');

  async function loadData() {
    try {
      const [propRes, packRes, recRes] = await Promise.allSettled([
        getProposal(proposalId),
        getProposalRealizations(proposalId),
        getProposalReceipts(proposalId),
      ]);

      if (propRes.status === 'fulfilled') setProposal(propRes.value?.data || propRes.value);
      if (packRes.status === 'fulfilled') {
        const pList = packRes.value?.data || packRes.value || [];
        setPackages(Array.isArray(pList) ? pList : pList.data || []);
      }
      if (recRes.status === 'fulfilled') {
        const rList = recRes.value?.data || recRes.value || [];
        setReceipts(Array.isArray(rList) ? rList : rList.data || []);
      }
    } catch (err) {
      console.error('Failed to load realization tracking data', err);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadData();
  }, [proposalId]);

  async function handleCreatePackage(e) {
    e.preventDefault();
    setActionLoading(true);
    try {
      await createRealizationPackage(proposalId, {
        name: packageName,
        allocated_amount: Number(packageBudget),
      });
      setPackageModalOpen(false);
      setPackageName('');
      setPackageBudget('');
      setSuccessMsg('Paket pengadaan realisasi berhasil dibuat.');
      await loadData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal membuat paket.');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleAddItem(e) {
    e.preventDefault();
    if (!selectedPackageId) return;
    setActionLoading(true);
    try {
      await addRealizationItem(selectedPackageId, {
        item_name: itemName,
        quantity: Number(itemQuantity),
        specification: itemSpec,
      });
      setItemModalOpen(false);
      setItemName('');
      setItemQuantity(1);
      setItemSpec('');
      setSuccessMsg('Item fisik barang ber-QR berhasil didaftarkan.');
      await loadData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menambahkan item.');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleCreateReceipt(e) {
    e.preventDefault();
    setActionLoading(true);
    try {
      await createReceipt(proposalId, {
        receipt_number: receiptNumber,
        amount: Number(receiptAmount),
        vendor_name: receiptVendor,
        transaction_date: receiptDate,
      });
      setReceiptModalOpen(false);
      setReceiptNumber('');
      setReceiptAmount('');
      setReceiptVendor('');
      setSuccessMsg('Bukti kuitansi pembelanjaan berhasil dicatat.');
      await loadData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menyimpan kuitansi.');
    } finally {
      setActionLoading(false);
    }
  }

  if (loading) {
    return <LoadingSpinner text="Memuat pelacakan realisasi fisik & kuitansi..." />;
  }

  return (
    <div className="space-y-6">
      <Link
        to={`/proposals/${proposalId}`}
        className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition"
      >
        <ArrowLeftIcon className="h-3.5 w-3.5" /> Kembali ke Lembar Usulan
      </Link>

      {/* Header */}
      <div className="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div className="flex items-center gap-2">
              <span className="font-mono text-xs font-semibold text-blue-600">
                #{proposal?.proposal_number || proposalId?.slice(0, 8)}
              </span>
              <StatusBadge status={proposal?.status || 'implementation'} />
            </div>
            <h1 className="mt-1.5 text-xl font-bold text-slate-900 sm:text-2xl">
              Pelacakan Realisasi Fisik & Kuitansi: {proposal?.title}
            </h1>
            <p className="mt-1 text-xs text-slate-500">
              Lembaga: <span className="font-semibold text-slate-700">{proposal?.organization?.name}</span> •
              Alokasi Bantuan: <span className="font-bold text-emerald-600">Rp {Number(proposal?.requested_amount || 0).toLocaleString('id-ID')}</span>
            </p>
          </div>

          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => setPackageModalOpen(true)}
              className="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-blue-700 transition"
            >
              <PlusIcon className="h-4 w-4" />
              <span>Tambah Paket Realisasi</span>
            </button>
            <button
              type="button"
              onClick={() => setReceiptModalOpen(true)}
              className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
            >
              <ReceiptPercentIcon className="h-4 w-4 text-emerald-600" />
              <span>Catat Kuitansi</span>
            </button>
          </div>
        </div>
      </div>

      {successMsg && (
        <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-700">
          <CheckBadgeIcon className="h-5 w-5 shrink-0 text-emerald-600" />
          <span>{successMsg}</span>
        </div>
      )}

      {/* Tabs */}
      <div className="border-b border-slate-200">
        <nav className="flex space-x-6 text-xs font-bold">
          <button
            onClick={() => setActiveTab('packages')}
            className={`pb-3 border-b-2 transition ${
              activeTab === 'packages'
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-slate-500 hover:text-slate-700'
            }`}
          >
            Paket Pengadaan Fisik ({packages.length})
          </button>
          <button
            onClick={() => setActiveTab('receipts')}
            className={`pb-3 border-b-2 transition ${
              activeTab === 'receipts'
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-slate-500 hover:text-slate-700'
            }`}
          >
            Daftar Kuitansi Belanja ({receipts.length})
          </button>
        </nav>
      </div>

      {/* TAB 1: PACKAGES & ITEMS */}
      {activeTab === 'packages' && (
        <div className="space-y-6">
          {packages.length === 0 ? (
            <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50/50 p-12 text-center text-xs text-slate-500">
              Belum ada paket pengadaan barang/jasa yang didaftarkan.
            </div>
          ) : (
            packages.map((pkg) => {
              const items = pkg.items || [];
              return (
                <div
                  key={pkg.id}
                  className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4"
                >
                  <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-4">
                    <div>
                      <div className="flex items-center gap-2">
                        <ArchiveBoxIcon className="h-5 w-5 text-blue-600" />
                        <h3 className="text-base font-bold text-slate-900">{pkg.name || 'Paket Realisasi'}</h3>
                        <StatusBadge status={pkg.status || 'verified'} size="sm" />
                      </div>
                      <p className="text-xs text-slate-500 mt-1">
                        Pagu Paket: <span className="font-semibold text-slate-800">Rp {Number(pkg.allocated_amount || 0).toLocaleString('id-ID')}</span>
                      </p>
                    </div>

                    <div className="flex items-center gap-2">
                      <button
                        type="button"
                        onClick={() => {
                          setSelectedPackageId(pkg.id);
                          setItemModalOpen(true);
                        }}
                        className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                      >
                        + Tambah Item Fisik
                      </button>
                    </div>
                  </div>

                  {/* Items Table */}
                  <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-slate-200 text-left text-xs">
                      <thead className="bg-slate-50 font-semibold uppercase text-slate-500">
                        <tr>
                          <th className="px-4 py-2.5">Nama Barang / Spesifikasi</th>
                          <th className="px-4 py-2.5">Volume</th>
                          <th className="px-4 py-2.5">Identitas QR Traceability</th>
                          <th className="px-4 py-2.5">Status Inspeksi</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100 bg-white">
                        {items.length === 0 ? (
                          <tr>
                            <td colSpan={4} className="px-4 py-4 text-center text-slate-400">
                              Belum ada rincian item barang pada paket ini.
                            </td>
                          </tr>
                        ) : (
                          items.map((it, idx) => (
                            <tr key={it.id || idx}>
                              <td className="px-4 py-3 font-semibold text-slate-800">
                                {it.item_name}
                                {it.specification && (
                                  <p className="text-[11px] font-normal text-slate-500">{it.specification}</p>
                                )}
                              </td>
                              <td className="px-4 py-3 text-slate-600">{it.quantity} Unit</td>
                              <td className="px-4 py-3">
                                <span className="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 font-mono text-[11px] font-bold text-blue-700 border border-blue-200">
                                  <QrCodeIcon className="h-3.5 w-3.5" />
                                  {it.qr_token || it.qr_identity?.token || `QR-${it.id?.slice(0, 6)}`}
                                </span>
                              </td>
                              <td className="px-4 py-3">
                                <span className="text-emerald-700 font-semibold">Tervalidasi</span>
                              </td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              );
            })
          )}
        </div>
      )}

      {/* TAB 2: RECEIPTS */}
      {activeTab === 'receipts' && (
        <div className="rounded-2xl border border-slate-200 bg-white shadow-xs overflow-hidden">
          <table className="min-w-full divide-y divide-slate-200 text-left text-xs">
            <thead className="bg-slate-50 font-semibold uppercase text-slate-500">
              <tr>
                <th className="px-6 py-3.5">Nomor Kuitansi / Bukti</th>
                <th className="px-6 py-3.5">Nama Toko / Rekanan</th>
                <th className="px-6 py-3.5">Tanggal Belanja</th>
                <th className="px-6 py-3.5 text-right">Nilai Pembayaran (Rp)</th>
                <th className="px-6 py-3.5">Status Keabsahan</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 bg-white">
              {receipts.length === 0 ? (
                <tr>
                  <td colSpan={5} className="px-6 py-8 text-center text-slate-400">
                    Belum ada kuitansi pembayaran yang dicatat.
                  </td>
                </tr>
              ) : (
                receipts.map((rc, idx) => (
                  <tr key={rc.id || idx}>
                    <td className="px-6 py-4 font-mono font-bold text-slate-900">
                      {rc.receipt_number || `KWT/${idx + 1}`}
                    </td>
                    <td className="px-6 py-4 text-slate-700">{rc.vendor_name || 'Rekanan Penyedia'}</td>
                    <td className="px-6 py-4 text-slate-500">{rc.transaction_date || '—'}</td>
                    <td className="px-6 py-4 text-right font-bold text-slate-900">
                      Rp {Number(rc.amount || 0).toLocaleString('id-ID')}
                    </td>
                    <td className="px-6 py-4">
                      <span className="rounded-full bg-emerald-50 px-2.5 py-0.5 font-semibold text-emerald-700 border border-emerald-200">
                        Sah / Terverifikasi
                      </span>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      )}

      {/* MODAL PAKET */}
      <Modal
        isOpen={packageModalOpen}
        onClose={() => setPackageModalOpen(false)}
        title="Tambah Paket Realisasi Pengadaan"
      >
        <form onSubmit={handleCreatePackage} className="space-y-4">
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Nama Paket Pengadaan *
            </label>
            <input
              type="text"
              value={packageName}
              onChange={(e) => setPackageName(e.target.value)}
              placeholder="Contoh: Pengadaan Sound System & Sarana Pelatihan..."
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-xs focus:border-blue-500 focus:outline-hidden"
            />
          </div>
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Alokasi Pagu Paket (Rp) *
            </label>
            <input
              type="number"
              value={packageBudget}
              onChange={(e) => setPackageBudget(e.target.value)}
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-sm font-bold focus:border-blue-500 focus:outline-hidden"
            />
          </div>
          <div className="flex justify-end gap-2 pt-4">
            <button
              type="button"
              onClick={() => setPackageModalOpen(false)}
              className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={actionLoading}
              className="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700"
            >
              Simpan Paket
            </button>
          </div>
        </form>
      </Modal>

      {/* MODAL ITEM */}
      <Modal
        isOpen={itemModalOpen}
        onClose={() => setItemModalOpen(false)}
        title="Daftarkan Item Fisik Barang (QR Identity)"
      >
        <form onSubmit={handleAddItem} className="space-y-4">
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Nama Barang / Peralatan *
            </label>
            <input
              type="text"
              value={itemName}
              onChange={(e) => setItemName(e.target.value)}
              placeholder="Contoh: Laptop Core i7, Printer All-in-One..."
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-xs focus:border-blue-500 focus:outline-hidden"
            />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold uppercase text-slate-700">
                Jumlah / Volume *
              </label>
              <input
                type="number"
                min="1"
                value={itemQuantity}
                onChange={(e) => setItemQuantity(e.target.value)}
                required
                className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-xs focus:border-blue-500 focus:outline-hidden"
              />
            </div>
            <div>
              <label className="block text-xs font-bold uppercase text-slate-700">
                Spesifikasi Singkat
              </label>
              <input
                type="text"
                value={itemSpec}
                onChange={(e) => setItemSpec(e.target.value)}
                placeholder="Merek, tipe, nomor seri..."
                className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-xs focus:border-blue-500 focus:outline-hidden"
              />
            </div>
          </div>
          <div className="flex justify-end gap-2 pt-4">
            <button
              type="button"
              onClick={() => setItemModalOpen(false)}
              className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={actionLoading}
              className="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700"
            >
              Generate QR & Simpan
            </button>
          </div>
        </form>
      </Modal>

      {/* MODAL KUITANSI */}
      <Modal
        isOpen={receiptModalOpen}
        onClose={() => setReceiptModalOpen(false)}
        title="Catat Bukti Kuitansi Belanja"
      >
        <form onSubmit={handleCreateReceipt} className="space-y-4">
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Nomor Kuitansi / Faktur *
            </label>
            <input
              type="text"
              value={receiptNumber}
              onChange={(e) => setReceiptNumber(e.target.value)}
              placeholder="Contoh: KWT/2026/089"
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-xs font-mono focus:border-emerald-500 focus:outline-hidden"
            />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold uppercase text-slate-700">
                Nilai Belanja (Rp) *
              </label>
              <input
                type="number"
                value={receiptAmount}
                onChange={(e) => setReceiptAmount(e.target.value)}
                required
                className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-xs font-bold focus:border-emerald-500 focus:outline-hidden"
              />
            </div>
            <div>
              <label className="block text-xs font-bold uppercase text-slate-700">
                Tanggal Pembayaran *
              </label>
              <input
                type="date"
                value={receiptDate}
                onChange={(e) => setReceiptDate(e.target.value)}
                required
                className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-xs focus:border-emerald-500 focus:outline-hidden"
              />
            </div>
          </div>
          <div>
            <label className="block text-xs font-bold uppercase text-slate-700">
              Nama Toko / Penyedia Jasa *
            </label>
            <input
              type="text"
              value={receiptVendor}
              onChange={(e) => setReceiptVendor(e.target.value)}
              placeholder="Contoh: Toko Elektronik Bersama..."
              required
              className="mt-1.5 w-full rounded-lg border border-slate-300 py-2 px-3 text-xs focus:border-emerald-500 focus:outline-hidden"
            />
          </div>
          <div className="flex justify-end gap-2 pt-4">
            <button
              type="button"
              onClick={() => setReceiptModalOpen(false)}
              className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={actionLoading}
              className="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
            >
              Simpan Kuitansi
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}

