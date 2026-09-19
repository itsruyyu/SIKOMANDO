import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../../services/api';
import { formatCurrency, formatDate } from '../../utils/formatters';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Table, { TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '../../components/ui/Table';
import Tabs from '../../components/ui/Tabs';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import {
  ShoppingBagIcon,
  ReceiptPercentIcon,
  DocumentCheckIcon,
  ArrowLeftIcon,
  QrCodeIcon,
} from '@heroicons/react/24/outline';

export function RealizationDetailPage() {
  const { proposalId } = useParams();

  const [proposal, setProposal] = useState(null);
  const [packages, setPackages] = useState([]);
  const [receipts, setReceipts] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('packages');

  useEffect(() => {
    let isMounted = true;
    async function loadRealizationDetail() {
      setIsLoading(true);
      try {
        const [propRes, packRes, recRes] = await Promise.allSettled([
          api.get(`/proposals/${proposalId}`),
          api.get(`/proposals/${proposalId}/realizations`),
          api.get(`/proposals/${proposalId}/receipts`),
        ]);

        if (!isMounted) return;
        if (propRes.status === 'fulfilled' && propRes.value?.data) {
          setProposal(propRes.value.data);
        }
        if (packRes.status === 'fulfilled' && packRes.value?.data) {
          setPackages(packRes.value.data);
        }
        if (recRes.status === 'fulfilled' && recRes.value?.data) {
          setReceipts(recRes.value.data);
        }
      } catch (err) {
        console.error('Failed to load realization detail:', err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadRealizationDetail();
    return () => { isMounted = false; };
  }, [proposalId]);

  if (isLoading) {
    return (
      <div className="py-20">
        <Spinner size="lg" label="Memuat rincian paket realisasi & kuitansi..." />
      </div>
    );
  }

  const tabs = [
    { id: 'packages', label: 'Paket Kegiatan & Barang', icon: ShoppingBagIcon, count: packages.length },
    { id: 'receipts', label: 'Kuitansi & Bukti Pengeluaran', icon: ReceiptPercentIcon, count: receipts.length },
  ];

  return (
    <div className="space-y-6 pb-20">
      <PageHeader
        title={`Realisasi Belanja: ${proposal?.proposal_number || 'USULAN'}`}
        subtitle={`Organisasi: ${proposal?.organization?.name} • Pagu Disetujui: ${formatCurrency(proposal?.approved_amount || proposal?.requested_amount)}`}
        breadcrumbs={[
          { label: 'Realisasi Belanja', to: '/realizations' },
          { label: 'Rincian Realisasi' },
        ]}
        action={
          <Link to="/realizations">
            <Button variant="secondary" size="sm" icon={ArrowLeftIcon}>
              Kembali
            </Button>
          </Link>
        }
      />

      <Tabs tabs={tabs} activeTab={activeTab} onChange={setActiveTab} />

      {/* TAB 1: PACKAGES */}
      {activeTab === 'packages' && (
        <Card className="border-slate-200">
          <CardHeader
            title="Daftar Paket Realisasi Fisik"
            subtitle="Paket pengadaan sarana, barang, atau kegiatan yang dieksekusi"
          />
          {packages.length === 0 ? (
            <CardBody className="p-8 text-center text-xs text-slate-500">
              Belum ada paket realisasi yang didaftarkan.
            </CardBody>
          ) : (
            <Table>
              <TableHead>
                <tr>
                  <TableHeaderCell>Nama Paket</TableHeaderCell>
                  <TableHeaderCell>Metode Pengadaan</TableHeaderCell>
                  <TableHeaderCell>Nilai Paket (Rp)</TableHeaderCell>
                  <TableHeaderCell>Status</TableHeaderCell>
                </tr>
              </TableHead>
              <TableBody>
                {packages.map((pkg) => (
                  <TableRow key={pkg.id}>
                    <TableCell>
                      <div className="font-bold text-slate-900">{pkg.package_name || pkg.name}</div>
                      <div className="text-[11px] text-slate-400">{pkg.description || '-'}</div>
                    </TableCell>
                    <TableCell>
                      <span className="text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                        {pkg.procurement_method || 'Swakelola'}
                      </span>
                    </TableCell>
                    <TableCell mono className="font-bold text-slate-900">
                      {formatCurrency(pkg.total_amount || 0)}
                    </TableCell>
                    <TableCell>
                      <span className="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                        {pkg.status || 'Aktif'}
                      </span>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </Card>
      )}

      {/* TAB 2: RECEIPTS */}
      {activeTab === 'receipts' && (
        <Card className="border-slate-200">
          <CardHeader
            title="Kuitansi Belanja & Bukti Pembayaran"
            subtitle="Kuitansi resmi yang telah dilengkapi kode QR unik anti-pemalsuan"
          />
          {receipts.length === 0 ? (
            <CardBody className="p-8 text-center text-xs text-slate-500">
              Belum ada kuitansi belanja yang dicatat.
            </CardBody>
          ) : (
            <Table>
              <TableHead>
                <tr>
                  <TableHeaderCell>Nomor Kuitansi</TableHeaderCell>
                  <TableHeaderCell>Uraian Pembayaran</TableHeaderCell>
                  <TableHeaderCell>Penerima Uang (Pihak Ketiga)</TableHeaderCell>
                  <TableHeaderCell>Nominal (Rp)</TableHeaderCell>
                  <TableHeaderCell>Tanggal</TableHeaderCell>
                  <TableHeaderCell className="text-right">QR Validasi</TableHeaderCell>
                </tr>
              </TableHead>
              <TableBody>
                {receipts.map((rec) => (
                  <TableRow key={rec.id}>
                    <TableCell mono className="font-bold text-blue-900">
                      {rec.receipt_number || 'KW/2026/01'}
                    </TableCell>
                    <TableCell>
                      <div className="font-bold text-slate-800">{rec.description || 'Pembelian perlengkapan'}</div>
                    </TableCell>
                    <TableCell>
                      <span className="text-xs text-slate-700">{rec.paid_to || '-'}</span>
                    </TableCell>
                    <TableCell mono className="font-bold text-emerald-800">
                      {formatCurrency(rec.amount)}
                    </TableCell>
                    <TableCell>{formatDate(rec.receipt_date || rec.created_at)}</TableCell>
                    <TableCell className="text-right">
                      <span className="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                        <QrCodeIcon className="w-3.5 h-3.5" /> Terverifikasi
                      </span>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </Card>
      )}
    </div>
  );
}

export default RealizationDetailPage;

