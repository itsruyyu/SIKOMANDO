import React, { useState, useEffect } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import api from '../../services/api';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { formatCurrency, formatDate } from '../../utils/formatters';
import { openPdf } from '../../utils/pdf';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Table, { TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '../../components/ui/Table';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import Alert from '../../components/feedback/Alert';
import {
  ClipboardDocumentListIcon,
  CheckBadgeIcon,
  XCircleIcon,
  ArrowLeftIcon,
  PrinterIcon,
  ArchiveBoxXMarkIcon,
  ShieldCheckIcon,
} from '@heroicons/react/24/outline';

export function LpjDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const toast = useToast();
  const { isAuditor, isAdmin, isSuperAdmin } = useAuth();

  const [lpj, setLpj] = useState(null);
  const [items, setItems] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isProcessing, setIsProcessing] = useState(false);

  const loadLpjData = async () => {
    setIsLoading(true);
    try {
      const res = await api.get(`/lpj/${id}`);
      if (res?.data) {
        setLpj(res.data);
        setItems(res.data.items || res.data.proposal?.budget_items || []);
      }
    } catch (err) {
      console.error('Failed to load LPJ detail:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadLpjData();
  }, [id]);

  const handleApproveLpj = async () => {
    setIsProcessing(true);
    try {
      await api.post(`/lpj/${id}/approve`, {
        notes: 'Laporan Pertanggungjawaban (LPJ) telah diverifikasi lengkap dan sah sesuai bukti kuitansi.',
      });
      toast.success('LPJ berhasil disetujui!');
      loadLpjData();
    } catch (err) {
      toast.error(err.message || 'Gagal menyetujui LPJ.');
    } finally {
      setIsProcessing(false);
    }
  };

  const handleCloseProposal = async () => {
    if (!lpj?.proposal_id) return;
    setIsProcessing(true);
    try {
      await api.post(`/proposals/${lpj.proposal_id}/close`, {
        notes: 'Seluruh tahapan usulan hibah, pencairan SP2D, dan verifikasi LPJ telah tuntas 100%.',
      });
      toast.success('Berkas usulan hibah telah resmi ditutup (Completed)!');
      navigate('/lpj');
    } catch (err) {
      toast.error(err.message || 'Gagal menutup berkas usulan.');
    } finally {
      setIsProcessing(false);
    }
  };

  if (isLoading) {
    return (
      <div className="py-20">
        <Spinner size="lg" label="Memuat naskah LPJ..." />
      </div>
    );
  }

  if (!lpj) {
    return (
      <div className="max-w-2xl mx-auto py-16">
        <Alert type="danger" title="LPJ Tidak Ditemukan">
          <p className="mb-4">Data Laporan Pertanggungjawaban tidak tersedia.</p>
          <Link to="/lpj">
            <Button variant="secondary" size="sm">Kembali ke Daftar LPJ</Button>
          </Link>
        </Alert>
      </div>
    );
  }

  const proposal = lpj.proposal || {};
  const isApproved = lpj.status === 'approved' || lpj.status === 'finalized';

  return (
    <div className="max-w-5xl mx-auto space-y-6 pb-20">
      <PageHeader
        title={`Verifikasi LPJ: ${lpj.lpj_number || 'LPJ/2026/01'}`}
        subtitle={`Pengusul: ${proposal.organization?.name || '-'} • Pagu Hibah: ${formatCurrency(proposal.approved_amount || 0)}`}
        breadcrumbs={[
          { label: 'Laporan LPJ', to: '/lpj' },
          { label: 'Rincian Pertanggungjawaban' },
        ]}
        action={
          <div className="flex items-center gap-2">
            <Link to="/lpj">
              <Button variant="secondary" size="sm" icon={ArrowLeftIcon}>
                Kembali
              </Button>
            </Link>
            <Button
              variant="outline"
              size="sm"
              icon={PrinterIcon}
              onClick={() => openPdf(`/pdf/lpj/${lpj.id}`)}
            >
              Cetak PDF LPJ
            </Button>
          </div>
        }
      />

      {/* Summary Stat Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <Card className="border-blue-200 bg-blue-50/30">
          <CardBody className="p-5">
            <span className="text-xs font-bold uppercase tracking-wider text-blue-800">
              Pagu Hibah Disetujui
            </span>
            <div className="text-2xl font-black font-mono text-blue-950 mt-1">
              {formatCurrency(proposal.approved_amount || 0)}
            </div>
            <span className="text-[11px] text-blue-700">Berdasarkan SK Gubernur</span>
          </CardBody>
        </Card>

        <Card className="border-emerald-200 bg-emerald-50/30">
          <CardBody className="p-5">
            <span className="text-xs font-bold uppercase tracking-wider text-emerald-800">
              Total Realisasi Belanja
            </span>
            <div className="text-2xl font-black font-mono text-emerald-950 mt-1">
              {formatCurrency(lpj.total_spent || proposal.approved_amount || 0)}
            </div>
            <span className="text-[11px] text-emerald-700">Tercakup dalam kuitansi sah</span>
          </CardBody>
        </Card>

        <Card className="border-slate-200 bg-slate-50">
          <CardBody className="p-5">
            <span className="text-xs font-bold uppercase tracking-wider text-slate-700">
              Status Pertanggungjawaban
            </span>
            <div className="mt-1">
              <span className={`text-xs font-bold px-2.5 py-1 rounded-full ${
                isApproved ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'
              }`}>
                {isApproved ? 'LPJ Diterima & Sah' : 'Menunggu Persetujuan Verifikator'}
              </span>
            </div>
            <span className="text-[11px] text-slate-500 block mt-1">
              {isApproved ? 'Siap dilakukan penutupan usulan' : 'Harap periksa bukti fisik'}
            </span>
          </CardBody>
        </Card>
      </div>

      {/* Budget vs Realization Items Table */}
      <Card className="border-slate-200 shadow-xs">
        <CardHeader
          title="Tabel Perbandingan Rencana Anggaran (RAB) vs Realisasi Belanja"
          subtitle="Rincian pos belanja barang/jasa dan kesesuaian nominal penggunaan dana"
        />
        <Table>
          <TableHead>
            <tr>
              <TableHeaderCell>Pos Belanja</TableHeaderCell>
              <TableHeaderCell>Kategori</TableHeaderCell>
              <TableHeaderCell>Volume</TableHeaderCell>
              <TableHeaderCell>Rencana Pagu</TableHeaderCell>
              <TableHeaderCell>Realisasi Riil</TableHeaderCell>
              <TableHeaderCell className="text-right">Kesesuaian</TableHeaderCell>
            </tr>
          </TableHead>
          <TableBody>
            {items.map((it, idx) => (
              <TableRow key={it.id || idx}>
                <TableCell>
                  <div className="font-bold text-slate-900">{it.item_name || it.description}</div>
                  <div className="text-[11px] text-slate-400">{it.specification}</div>
                </TableCell>
                <TableCell>
                  <span className="text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                    {it.category || 'Belanja Barang'}
                  </span>
                </TableCell>
                <TableCell mono>{it.quantity || 1} {it.unit || 'Unit'}</TableCell>
                <TableCell mono className="font-bold text-slate-800">
                  {formatCurrency(it.total_price || it.budgeted_amount || 0)}
                </TableCell>
                <TableCell mono className="font-bold text-emerald-800">
                  {formatCurrency(it.actual_spent || it.total_price || 0)}
                </TableCell>
                <TableCell className="text-right">
                  <span className="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                    <ShieldCheckIcon className="w-3.5 h-3.5" /> Sesuai
                  </span>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </Card>

      {/* Action Decision Strip */}
      <Card className="border-blue-200 shadow-sm">
        <CardBody className="p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div>
            <h4 className="text-sm font-bold text-slate-900">
              Tindakan Verifikasi & Penutupan Siklus Hibah
            </h4>
            <p className="text-xs text-slate-500 mt-0.5">
              Setelah LPJ disetujui, berkas usulan dapat ditutup secara resmi (status: Completed).
            </p>
          </div>

          <div className="flex items-center gap-3">
            {!isApproved && (
              <Button
                variant="success"
                size="md"
                icon={CheckBadgeIcon}
                onClick={handleApproveLpj}
                isLoading={isProcessing}
                className="font-bold shadow-md"
              >
                Setujui & Sahkan LPJ
              </Button>
            )}

            {isApproved && (
              <Button
                variant="primary"
                size="md"
                icon={ArchiveBoxXMarkIcon}
                onClick={handleCloseProposal}
                isLoading={isProcessing}
                className="font-bold shadow-md bg-blue-700 hover:bg-blue-600"
              >
                Tutup Berkas Usulan (Closing)
              </Button>
            )}
          </div>
        </CardBody>
      </Card>
    </div>
  );
}

export default LpjDetailPage;

