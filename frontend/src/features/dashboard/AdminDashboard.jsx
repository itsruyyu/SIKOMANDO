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
import {
  ShieldCheckIcon,
  UsersIcon,
  DocumentDuplicateIcon,
  BanknotesIcon,
  Cog6ToothIcon,
  ArrowRightIcon,
  CheckBadgeIcon,
  ClipboardDocumentCheckIcon,
} from '@heroicons/react/24/outline';

export function AdminDashboard() {
  const { user } = useAuth();
  const [summary, setSummary] = useState(null);
  const [proposals, setProposals] = useState([]);
  const [workload, setWorkload] = useState([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    async function loadAdminData() {
      setIsLoading(true);
      try {
        const [statRes, propRes, workRes] = await Promise.allSettled([
          api.get('/public/statistics/summary'),
          api.get('/proposals'),
          api.get('/internal/dashboard/workload'),
        ]);

        if (!isMounted) return;

        if (statRes.status === 'fulfilled') {
          setSummary(statRes.value?.data || null);
        }
        if (propRes.status === 'fulfilled') {
          const pList = Array.isArray(propRes.value?.data) ? propRes.value.data : Array.isArray(propRes.value?.data?.data) ? propRes.value.data.data : [];
          setProposals(pList);
        }
        if (workRes.status === 'fulfilled') {
          const wList = Array.isArray(workRes.value?.data) ? workRes.value.data : Array.isArray(workRes.value?.data?.data) ? workRes.value.data.data : [];
          setWorkload(wList);
        }
      } catch (err) {
        console.error('Failed to load admin dashboard data:', err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadAdminData();
    return () => { isMounted = false; };
  }, []);

  return (
    <div className="space-y-8">
      {/* Admin Master Header Banner */}
      <div className="bg-gradient-to-r from-slate-950 via-slate-900 to-blue-950 text-white p-6 sm:p-8 rounded-3xl shadow-xl border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div className="space-y-1.5">
          <div className="flex items-center gap-2">
            <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-600/30 text-blue-300 border border-blue-500/40 uppercase tracking-wider">
              Super Admin & Pengelola Sistem SIKOMANDO
            </span>
            <span className="text-xs text-slate-400">• Provinsi Sulawesi Utara</span>
          </div>
          <h1 className="text-xl sm:text-2xl font-black">
            Pusat Kendali Administrasi: {user?.name}
          </h1>
          <p className="text-xs text-slate-300 max-w-2xl leading-relaxed">
            Kelola data master, penugasan verifikator, pendaftaran program hibah, pencairan dana SP2D, dan tata kelola akun pengguna se-Sulawesi Utara.
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-2.5">
          <Link to="/users">
            <Button variant="primary" size="sm" icon={UsersIcon}>
              Kelola Pengguna
            </Button>
          </Link>
          <Link to="/policies">
            <Button variant="outline" size="sm" icon={Cog6ToothIcon} className="border-slate-700 text-white hover:bg-white/10">
              Konfigurasi Kebijakan
            </Button>
          </Link>
        </div>
      </div>

      {isLoading ? (
        <Spinner label="Memuat metrik sistem terpadu..." />
      ) : (
        <>
          {/* Top Metric Cards */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <Card className="border-blue-200 bg-blue-50/40">
              <CardBody className="p-5 flex items-center justify-between">
                <div>
                  <span className="text-xs text-slate-500 font-semibold block">Total Usulan Masuk</span>
                  <span className="text-2xl font-black font-mono text-blue-950 mt-1 block">
                    {summary?.total_proposals_submitted || proposals.length}
                  </span>
                  <span className="text-[11px] text-blue-700">Tercatat di basis data</span>
                </div>
                <div className="p-3 rounded-xl bg-blue-600 text-white">
                  <DocumentDuplicateIcon className="w-6 h-6" />
                </div>
              </CardBody>
            </Card>

            <Card className="border-emerald-200 bg-emerald-50/40">
              <CardBody className="p-5 flex items-center justify-between">
                <div>
                  <span className="text-xs text-slate-500 font-semibold block">Total Pagu Disetujui</span>
                  <span className="text-xl font-black font-mono text-emerald-950 mt-1 block">
                    {formatCurrency(summary?.total_approved_amount || 0)}
                  </span>
                  <span className="text-[11px] text-emerald-700">Ketetapan SK Gubernur</span>
                </div>
                <div className="p-3 rounded-xl bg-emerald-600 text-white">
                  <CheckBadgeIcon className="w-6 h-6" />
                </div>
              </CardBody>
            </Card>

            <Card className="border-amber-200 bg-amber-50/40">
              <CardBody className="p-5 flex items-center justify-between">
                <div>
                  <span className="text-xs text-slate-500 font-semibold block">Total Dana Dicairkan</span>
                  <span className="text-xl font-black font-mono text-amber-950 mt-1 block">
                    {formatCurrency(summary?.total_disbursed_amount || 0)}
                  </span>
                  <span className="text-[11px] text-amber-700">Realisasi perbankan SP2D</span>
                </div>
                <div className="p-3 rounded-xl bg-amber-600 text-white">
                  <BanknotesIcon className="w-6 h-6" />
                </div>
              </CardBody>
            </Card>

            <Card className="border-slate-200 bg-slate-50/70">
              <CardBody className="p-5 flex items-center justify-between">
                <div>
                  <span className="text-xs text-slate-500 font-semibold block">Program Hibah Aktif</span>
                  <span className="text-2xl font-black font-mono text-slate-900 mt-1 block">
                    {summary?.active_programs_count || 4}
                  </span>
                  <span className="text-[11px] text-slate-500">APBD Prov. Sulut</span>
                </div>
                <div className="p-3 rounded-xl bg-slate-800 text-white">
                  <ShieldCheckIcon className="w-6 h-6" />
                </div>
              </CardBody>
            </Card>
          </div>

          {/* Officer Workload Distribution */}
          <Card className="border-slate-200 shadow-xs">
            <CardHeader
              title="Distribusi Beban Kerja Petugas Teknis"
              subtitle="Monitoring beban verifikasi, evaluasi, dan survei lapangan para pejabat teknis"
            />
            <Table>
              <TableHead>
                <tr>
                  <TableHeaderCell>Petugas Teknis</TableHeaderCell>
                  <TableHeaderCell>Tipe Penugasan</TableHeaderCell>
                  <TableHeaderCell>Total Tugas</TableHeaderCell>
                  <TableHeaderCell>Tugas Aktif</TableHeaderCell>
                  <TableHeaderCell>Tugas Selesai</TableHeaderCell>
                  <TableHeaderCell>Progres (%)</TableHeaderCell>
                </tr>
              </TableHead>
              <TableBody>
                {(Array.isArray(workload) ? workload : []).length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center py-6 text-slate-400">
                      Belum ada penugasan petugas tercatat.
                    </TableCell>
                  </TableRow>
                ) : (
                  (Array.isArray(workload) ? workload : []).map((w, idx) => {
                    const pct = w.total_tasks > 0 ? Math.round((w.completed_tasks / w.total_tasks) * 100) : 0;
                    return (
                      <TableRow key={idx}>
                        <TableCell>
                          <div className="font-bold text-slate-900">{w.user_name}</div>
                        </TableCell>
                        <TableCell>
                          <span className="text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                            {w.assignment_type}
                          </span>
                        </TableCell>
                        <TableCell mono className="font-bold">{w.total_tasks}</TableCell>
                        <TableCell mono className="font-bold text-amber-700">{w.active_tasks}</TableCell>
                        <TableCell mono className="font-bold text-emerald-700">{w.completed_tasks}</TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <div className="w-24 bg-slate-100 rounded-full h-2 overflow-hidden">
                              <div className="bg-blue-600 h-2 rounded-full" style={{ width: `${pct}%` }} />
                            </div>
                            <span className="text-xs font-mono font-semibold text-slate-700">{pct}%</span>
                          </div>
                        </TableCell>
                      </TableRow>
                    );
                  })
                )}
              </TableBody>
            </Table>
          </Card>

          {/* Proposals Overview Table */}
          <Card className="border-slate-200 shadow-xs">
            <CardHeader
              title="Daftar Usulan Terkini Seluruh Sulawesi Utara"
              subtitle="Usulan terbaru dari berbagai ormas, yayasan, dan lembaga keagamaan"
              action={
                <Link to="/proposals">
                  <Button variant="ghost" size="xs" icon={ArrowRightIcon} iconPosition="right">
                    Semua Usulan
                  </Button>
                </Link>
              }
            />
            <Table>
              <TableHead>
                <tr>
                  <TableHeaderCell>Nomor Usulan</TableHeaderCell>
                  <TableHeaderCell>Judul Proposal</TableHeaderCell>
                  <TableHeaderCell>Ormas Pemohon</TableHeaderCell>
                  <TableHeaderCell>Anggaran</TableHeaderCell>
                  <TableHeaderCell>Status</TableHeaderCell>
                  <TableHeaderCell className="text-right">Aksi</TableHeaderCell>
                </tr>
              </TableHead>
              <TableBody>
                {(Array.isArray(proposals) ? proposals : []).slice(0, 8).map((prop) => (
                  <TableRow key={prop.id}>
                    <TableCell mono className="font-bold text-blue-900">
                      {prop.proposal_number}
                    </TableCell>
                    <TableCell>
                      <div className="font-bold text-slate-800 max-w-xs truncate">{prop.title}</div>
                      <div className="text-[11px] text-slate-400">{formatDate(prop.submitted_at || prop.created_at)}</div>
                    </TableCell>
                    <TableCell>
                      <span className="text-xs text-slate-700">{prop.organization?.name || '-'}</span>
                    </TableCell>
                    <TableCell mono className="font-bold text-slate-900">
                      {formatCurrency(prop.approved_amount || prop.requested_amount)}
                    </TableCell>
                    <TableCell>
                      <Badge status={prop.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      <Link to={`/proposals/${prop.id}`}>
                        <Button variant="outline" size="xs">
                          Buka Detail
                        </Button>
                      </Link>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </Card>
        </>
      )}
    </div>
  );
}

export default AdminDashboard;

