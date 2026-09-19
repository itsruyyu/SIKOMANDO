import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import api from '../../services/api';
import { formatCurrency, formatDate } from '../../utils/formatters';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Badge from '../../components/ui/Badge';
import Table, { TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '../../components/ui/Table';
import Spinner from '../../components/feedback/Spinner';
import EmptyState from '../../components/feedback/EmptyState';
import {
  CheckBadgeIcon,
  DocumentArrowDownIcon,
  ClockIcon,
  TrophyIcon,
  ArrowRightIcon,
  ShieldCheckIcon,
} from '@heroicons/react/24/outline';

export function ApproverDashboard() {
  const { user } = useAuth();
  const [approvals, setApprovals] = useState([]);
  const [decisions, setDecisions] = useState([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    async function loadApproverData() {
      setIsLoading(true);
      try {
        const [appRes, decRes] = await Promise.allSettled([
          api.get('/approvals'),
          api.get('/decisions'),
        ]);

        if (!isMounted) return;

        if (appRes.status === 'fulfilled') {
          const aList = Array.isArray(appRes.value?.data) ? appRes.value.data : Array.isArray(appRes.value?.data?.data) ? appRes.value.data.data : [];
          setApprovals(aList);
        }
        if (decRes.status === 'fulfilled') {
          const dList = Array.isArray(decRes.value?.data) ? decRes.value.data : Array.isArray(decRes.value?.data?.data) ? decRes.value.data.data : [];
          setDecisions(dList);
        }
      } catch (err) {
        console.error('Failed to load approver data:', err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadApproverData();
    return () => { isMounted = false; };
  }, []);

  const safeApprovals = Array.isArray(approvals) ? approvals : [];
  const pendingApprovals = safeApprovals.filter((a) => a?.status === 'pending');

  return (
    <div className="space-y-8">
      {/* Executive Header Banner */}
      <div className="bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-950 text-white p-6 sm:p-8 rounded-3xl shadow-lg border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div className="space-y-1">
          <div className="flex items-center gap-2">
            <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 uppercase tracking-wider">
              Pejabat Penyetuju (Approver)
            </span>
            <span className="text-xs text-slate-400">• Setda Provinsi Sulawesi Utara</span>
          </div>
          <h1 className="text-xl sm:text-2xl font-black">
            Executive Leadership Dossier: {user?.name}
          </h1>
          <p className="text-xs text-slate-300 max-w-xl leading-relaxed">
            Tinjau rekam jejak kelayakan teknis, bukti verifikasi administrasi, hasil survei ber-GPS, dan terbitkan Surat Keputusan (SK) Gubernur.
          </p>
        </div>

        <div className="flex items-center gap-3">
          <Link to="/approvals">
            <Button variant="primary" size="md" icon={CheckBadgeIcon} className="bg-emerald-600 hover:bg-emerald-500">
              Antrean Persetujuan ({pendingApprovals.length})
            </Button>
          </Link>
          <Link to="/decisions">
            <Button variant="outline" size="md" icon={DocumentArrowDownIcon} className="border-slate-600 text-white hover:bg-white/10">
              Surat Keputusan (SK)
            </Button>
          </Link>
        </div>
      </div>

      {/* Metrics */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <Card className="border-amber-200 bg-amber-50/40">
          <CardBody className="p-5 flex items-center justify-between">
            <div>
              <span className="text-xs text-slate-500 font-semibold block">Menunggu Persetujuan Anda</span>
              <span className="text-2xl font-black font-mono text-amber-900 mt-1 block">
                {pendingApprovals.length} Usulan
              </span>
              <span className="text-[11px] text-amber-700">Dossier lengkap siap diputuskan</span>
            </div>
            <div className="p-3 rounded-xl bg-amber-600 text-white">
              <ClockIcon className="w-6 h-6" />
            </div>
          </CardBody>
        </Card>

        <Card className="border-emerald-200 bg-emerald-50/40">
          <CardBody className="p-5 flex items-center justify-between">
            <div>
              <span className="text-xs text-slate-500 font-semibold block">Telah Disetujui (Approved)</span>
              <span className="text-2xl font-black font-mono text-emerald-900 mt-1 block">
                {approvals.filter((a) => a.status === 'approved').length} Usulan
              </span>
              <span className="text-[11px] text-emerald-700">SK Penetapan Terbit</span>
            </div>
            <div className="p-3 rounded-xl bg-emerald-600 text-white">
              <CheckBadgeIcon className="w-6 h-6" />
            </div>
          </CardBody>
        </Card>

        <Card className="border-blue-200 bg-blue-50/40">
          <CardBody className="p-5 flex items-center justify-between">
            <div>
              <span className="text-xs text-slate-500 font-semibold block">Total Surat Keputusan (SK)</span>
              <span className="text-2xl font-black font-mono text-blue-900 mt-1 block">
                {decisions.length} SK Resmi
              </span>
              <span className="text-[11px] text-blue-700">Dilengkapi QR Code Kriptografis</span>
            </div>
            <div className="p-3 rounded-xl bg-blue-600 text-white">
              <DocumentArrowDownIcon className="w-6 h-6" />
            </div>
          </CardBody>
        </Card>
      </div>

      {/* Approval Queue Table */}
      <Card className="border-slate-200 shadow-xs">
        <CardHeader
          title="Daftar Usulan Menunggu Keputusan Pimpinan"
          subtitle="Tinjau berkas komprehensif dan berikan pengesahan formal"
        />
        {isLoading ? (
          <Spinner label="Memuat usulan pimpinan..." />
        ) : approvals.length === 0 ? (
          <div className="p-8">
            <EmptyState
              title="Tidak Ada Usulan Menunggu Persetujuan"
              description="Saat ini semua usulan yang masuk telah diputuskan atau belum ada berkas rekomendasi TAPD yang diteruskan."
            />
          </div>
        ) : (
          <Table>
            <TableHead>
              <tr>
                <TableHeaderCell>Nomor Usulan</TableHeaderCell>
                <TableHeaderCell>Judul Usulan Kegiatan</TableHeaderCell>
                <TableHeaderCell>Organisasi Pemohon</TableHeaderCell>
                <TableHeaderCell>Nominal Rekomendasi</TableHeaderCell>
                <TableHeaderCell>Status</TableHeaderCell>
                <TableHeaderCell className="text-right">Aksi Pimpinan</TableHeaderCell>
              </tr>
            </TableHead>
            <TableBody>
              {approvals.slice(0, 8).map((app) => (
                <TableRow key={app.id}>
                  <TableCell mono className="font-bold text-blue-900">
                    {app.proposal?.proposal_number || 'USULAN'}
                  </TableCell>
                  <TableCell>
                    <div className="font-bold text-slate-900 max-w-xs truncate">{app.proposal?.title || '-'}</div>
                  </TableCell>
                  <TableCell>
                    <span className="text-xs text-slate-700">{app.proposal?.organization?.name || '-'}</span>
                  </TableCell>
                  <TableCell mono className="font-bold text-emerald-800">
                    {formatCurrency(app.recommended_amount || app.proposal?.approved_amount || 0)}
                  </TableCell>
                  <TableCell>
                    <span className={`text-xs font-bold px-2.5 py-1 rounded-full ${app.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}`}>
                      {app.status === 'approved' ? 'Disetujui' : 'Menunggu Review'}
                    </span>
                  </TableCell>
                  <TableCell className="text-right">
                    <Link to={`/approvals/${app.id}`}>
                      <Button variant="primary" size="xs">
                        Buka Dossier
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

export default ApproverDashboard;

