import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import api from '../../services/api';
import { formatCurrency, formatDate, getStatusBadge } from '../../utils/formatters';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Badge from '../../components/ui/Badge';
import Table, { TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '../../components/ui/Table';
import Spinner from '../../components/feedback/Spinner';
import EmptyState from '../../components/feedback/EmptyState';
import PageHeader from '../../components/layout/PageHeader';
import {
  DocumentPlusIcon,
  DocumentTextIcon,
  ClockIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ArrowRightIcon,
  ShoppingBagIcon,
  ClipboardDocumentListIcon,
} from '@heroicons/react/24/outline';

export function PemohonDashboard() {
  const { user } = useAuth();
  const [proposals, setProposals] = useState([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    async function loadProposals() {
      setIsLoading(true);
      try {
        const res = await api.get('/proposals');
        const list = Array.isArray(res?.data) ? res.data : Array.isArray(res?.data?.data) ? res.data.data : [];
        if (isMounted) {
          setProposals(list);
        }
      } catch (err) {
        console.error('Failed to load applicant proposals:', err);
        if (isMounted) setProposals([]);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadProposals();
    return () => { isMounted = false; };
  }, []);

  const safeProposals = Array.isArray(proposals) ? proposals : [];
  const totalSubmitted = safeProposals.length;
  const approvedProposals = safeProposals.filter((p) => ['approved', 'disbursed', 'implementation', 'lpj_submitted', 'completed'].includes(p?.status));
  const revisionProposals = safeProposals.filter((p) => p?.status === 'revision');
  const inProcessProposals = safeProposals.filter((p) => ['submitted', 'verification', 'verified', 'evaluation', 'survey', 'recommended', 'approval'].includes(p?.status));

  const totalApprovedAmount = approvedProposals.reduce((sum, p) => sum + (parseFloat(p.approved_amount) || 0), 0);

  return (
    <div className="space-y-8">
      {/* Welcome Banner */}
      <div className="bg-gradient-to-r from-blue-900 to-indigo-900 text-white p-6 sm:p-8 rounded-3xl shadow-lg border border-blue-800 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div className="space-y-1">
          <span className="text-[11px] font-bold uppercase tracking-wider text-blue-300">
            Portal Pemohon Hibah Daerah • Provinsi Sulawesi Utara
          </span>
          <h1 className="text-xl sm:text-2xl font-black">
            Selamat Datang, {user?.name || 'Ketua Ormas'}
          </h1>
          <p className="text-xs text-blue-200 leading-relaxed max-w-xl">
            Pantau perkembangan permohonan hibah organisasi Anda secara transparan dan lengkapi berkas tindak lanjut dengan tepat waktu.
          </p>
        </div>

        <Link to="/proposals/create">
          <Button variant="primary" size="md" icon={DocumentPlusIcon} className="bg-blue-500 hover:bg-blue-400 text-slate-950 font-bold shadow-md">
            Ajukan Usulan Baru
          </Button>
        </Link>
      </div>

      {/* Metric Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <Card className="border-slate-200">
          <CardBody className="p-5 flex items-center justify-between">
            <div>
              <span className="text-xs text-slate-500 font-semibold block">Total Usulan Diajukan</span>
              <span className="text-2xl font-black font-mono text-slate-900 mt-1 block">{totalSubmitted}</span>
              <span className="text-[11px] text-slate-400">Tercatat di sistem</span>
            </div>
            <div className="p-3 rounded-xl bg-blue-50 text-blue-600">
              <DocumentTextIcon className="w-6 h-6" />
            </div>
          </CardBody>
        </Card>

        <Card className="border-slate-200">
          <CardBody className="p-5 flex items-center justify-between">
            <div>
              <span className="text-xs text-slate-500 font-semibold block">Sedang Diproses</span>
              <span className="text-2xl font-black font-mono text-amber-700 mt-1 block">{inProcessProposals.length}</span>
              <span className="text-[11px] text-amber-600">Verifikasi & evaluasi</span>
            </div>
            <div className="p-3 rounded-xl bg-amber-50 text-amber-600">
              <ClockIcon className="w-6 h-6" />
            </div>
          </CardBody>
        </Card>

        <Card className="border-slate-200">
          <CardBody className="p-5 flex items-center justify-between">
            <div>
              <span className="text-xs text-slate-500 font-semibold block">Perlu Revisi Berkas</span>
              <span className="text-2xl font-black font-mono text-rose-700 mt-1 block">{revisionProposals.length}</span>
              <span className="text-[11px] text-rose-600">Harap segera diperbaiki</span>
            </div>
            <div className="p-3 rounded-xl bg-rose-50 text-rose-600">
              <ExclamationTriangleIcon className="w-6 h-6" />
            </div>
          </CardBody>
        </Card>

        <Card className="border-slate-200">
          <CardBody className="p-5 flex items-center justify-between">
            <div>
              <span className="text-xs text-slate-500 font-semibold block">Total Pagu Disetujui</span>
              <span className="text-xl font-black font-mono text-emerald-700 mt-1 block">{formatCurrency(totalApprovedAmount)}</span>
              <span className="text-[11px] text-emerald-600">{approvedProposals.length} usulan disetujui</span>
            </div>
            <div className="p-3 rounded-xl bg-emerald-50 text-emerald-600">
              <CheckCircleIcon className="w-6 h-6" />
            </div>
          </CardBody>
        </Card>
      </div>

      {/* Recent Proposals Table */}
      <Card className="border-slate-200 shadow-xs">
        <CardHeader
          title="Daftar Usulan Hibah Organisasi Anda"
          subtitle="Daftar lengkap proposal hibah dan status tahapan terkini"
          action={
            <Link to="/proposals">
              <Button variant="ghost" size="xs" icon={ArrowRightIcon} iconPosition="right">
                Lihat Semua
              </Button>
            </Link>
          }
        />
        {isLoading ? (
          <Spinner label="Memuat usulan..." />
        ) : proposals.length === 0 ? (
          <div className="p-8">
            <EmptyState
              title="Belum Ada Usulan Diajukan"
              description="Organisasi Anda belum mengajukan permohonan hibah daerah. Klik tombol di bawah untuk membuat usulan baru."
              actionLabel="Buat Usulan Baru"
              actionIcon={DocumentPlusIcon}
              onAction={() => window.location.href = '/proposals/create'}
            />
          </div>
        ) : (
          <Table>
            <TableHead>
              <tr>
                <TableHeaderCell>Nomor Usulan</TableHeaderCell>
                <TableHeaderCell>Judul Usulan Kegiatan</TableHeaderCell>
                <TableHeaderCell>Program Hibah</TableHeaderCell>
                <TableHeaderCell>Anggaran Dimohon</TableHeaderCell>
                <TableHeaderCell>Status</TableHeaderCell>
                <TableHeaderCell className="text-right">Aksi</TableHeaderCell>
              </tr>
            </TableHead>
            <TableBody>
              {proposals.slice(0, 6).map((proposal) => (
                <TableRow key={proposal.id}>
                  <TableCell mono className="font-bold text-blue-800">
                    {proposal.proposal_number || 'Draft'}
                  </TableCell>
                  <TableCell>
                    <div className="font-bold text-slate-900 max-w-xs truncate">{proposal.title}</div>
                    <div className="text-[11px] text-slate-400">Diajukan: {formatDate(proposal.submitted_at || proposal.created_at)}</div>
                  </TableCell>
                  <TableCell>
                    <span className="text-xs text-slate-700">{proposal.grant_program?.name || '-'}</span>
                  </TableCell>
                  <TableCell mono className="font-bold text-slate-900">
                    {formatCurrency(proposal.requested_amount)}
                  </TableCell>
                  <TableCell>
                    <Badge status={proposal.status} />
                  </TableCell>
                  <TableCell className="text-right">
                    <Link to={`/proposals/${proposal.id}`}>
                      <Button variant="outline" size="xs">
                        Buka Detail
                      </Button>
                    </Link>
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

export default PemohonDashboard;

