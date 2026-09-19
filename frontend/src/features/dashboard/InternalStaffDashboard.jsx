import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { ROLE_LABELS, ROLE_BADGE_COLORS } from '../../utils/constants';
import api from '../../services/api';
import { formatDate } from '../../utils/formatters';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Badge from '../../components/ui/Badge';
import Table, { TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '../../components/ui/Table';
import Spinner from '../../components/feedback/Spinner';
import EmptyState from '../../components/feedback/EmptyState';
import {
  ClipboardDocumentCheckIcon,
  AcademicCapIcon,
  MapPinIcon,
  CheckCircleIcon,
  ClockIcon,
  ArrowRightIcon,
  UserCircleIcon,
} from '@heroicons/react/24/outline';

export function InternalStaffDashboard() {
  const { user, primaryRole } = useAuth();
  const [workload, setWorkload] = useState([]);
  const [myAssignments, setMyAssignments] = useState([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    async function loadStaffData() {
      setIsLoading(true);
      try {
        const [workRes, assignRes] = await Promise.allSettled([
          api.get('/internal/dashboard/workload'),
          api.get('/assignments/my'),
        ]);

        if (!isMounted) return;

        if (workRes.status === 'fulfilled') {
          const wList = Array.isArray(workRes.value?.data) ? workRes.value.data : Array.isArray(workRes.value?.data?.data) ? workRes.value.data.data : [];
          setWorkload(wList);
        }
        if (assignRes.status === 'fulfilled') {
          const aList = Array.isArray(assignRes.value?.data) ? assignRes.value.data : Array.isArray(assignRes.value?.data?.data) ? assignRes.value.data.data : [];
          setMyAssignments(aList);
        }
      } catch (err) {
        console.error('Failed to load internal staff dashboard data:', err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadStaffData();
    return () => { isMounted = false; };
  }, []);

  // Filter workload specific to current user if available
  const safeWorkload = Array.isArray(workload) ? workload : [];
  const safeAssignments = Array.isArray(myAssignments) ? myAssignments : [];
  const userWorkload = safeWorkload.find((w) => w?.user_id === user?.id) || {
    total_tasks: safeAssignments.length,
    active_tasks: safeAssignments.filter((a) => a?.status === 'active').length,
    completed_tasks: safeAssignments.filter((a) => a?.status === 'completed').length,
  };

  const roleLabel = ROLE_LABELS[primaryRole] || 'Petugas Teknis';

  return (
    <div className="space-y-8">
      {/* Officer Header Card */}
      <div className="bg-gradient-to-r from-slate-900 to-slate-800 text-white p-6 sm:p-8 rounded-3xl shadow-lg border border-slate-700 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div className="space-y-1.5">
          <div className="flex items-center gap-2">
            <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-300 border border-blue-400/30 uppercase tracking-wider">
              {roleLabel}
            </span>
            <span className="text-xs text-slate-400">• Biro Kesra Setda Prov. Sulut</span>
          </div>
          <h1 className="text-xl sm:text-2xl font-black">
            Ruang Kerja Petugas: {user?.name}
          </h1>
          <p className="text-xs text-slate-300 max-w-xl leading-relaxed">
            Periksa dan selesaikan tugas penugasan usulan hibah sesuai dengan kewenangan peran teknis Anda.
          </p>
        </div>

        <div className="flex items-center gap-3">
          {primaryRole === 'VERIFIKATOR' && (
            <Link to="/verifications">
              <Button variant="primary" size="md" icon={ClipboardDocumentCheckIcon}>
                Buka Meja Verifikasi
              </Button>
            </Link>
          )}
          {primaryRole === 'EVALUATOR' && (
            <Link to="/evaluations">
              <Button variant="primary" size="md" icon={AcademicCapIcon}>
                Buka Lembar Evaluasi
              </Button>
            </Link>
          )}
          {primaryRole === 'SURVEYOR' && (
            <Link to="/field-surveys">
              <Button variant="primary" size="md" icon={MapPinIcon}>
                Buka Survei Lapangan & GPS
              </Button>
            </Link>
          )}
        </div>
      </div>

      {/* Officer Metric Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <Card className="border-blue-200 bg-blue-50/30">
          <CardBody className="p-5 flex items-center justify-between">
            <div>
              <span className="text-xs text-slate-500 font-semibold block">Total Penugasan</span>
              <span className="text-2xl font-black font-mono text-blue-900 mt-1 block">
                {userWorkload.total_tasks || 0}
              </span>
              <span className="text-[11px] text-blue-700">Diterima dari sistem</span>
            </div>
            <div className="p-3 rounded-xl bg-blue-600 text-white">
              <ClipboardDocumentCheckIcon className="w-6 h-6" />
            </div>
          </CardBody>
        </Card>

        <Card className="border-amber-200 bg-amber-50/30">
          <CardBody className="p-5 flex items-center justify-between">
            <div>
              <span className="text-xs text-slate-500 font-semibold block">Tugas Aktif (Pending)</span>
              <span className="text-2xl font-black font-mono text-amber-900 mt-1 block">
                {userWorkload.active_tasks || 0}
              </span>
              <span className="text-[11px] text-amber-700">Menunggu tindakan Anda</span>
            </div>
            <div className="p-3 rounded-xl bg-amber-600 text-white">
              <ClockIcon className="w-6 h-6" />
            </div>
          </CardBody>
        </Card>

        <Card className="border-emerald-200 bg-emerald-50/30">
          <CardBody className="p-5 flex items-center justify-between">
            <div>
              <span className="text-xs text-slate-500 font-semibold block">Tugas Selesai</span>
              <span className="text-2xl font-black font-mono text-emerald-900 mt-1 block">
                {userWorkload.completed_tasks || 0}
              </span>
              <span className="text-[11px] text-emerald-700">Telah dituntaskan</span>
            </div>
            <div className="p-3 rounded-xl bg-emerald-600 text-white">
              <CheckCircleIcon className="w-6 h-6" />
            </div>
          </CardBody>
        </Card>
      </div>

      {/* Task Queue Table */}
      <Card className="border-slate-200 shadow-xs">
        <CardHeader
          title="Antrean Tugas & Penugasan Anda"
          subtitle="Daftar proposal yang didelegasikan kepada Anda untuk diverifikasi atau dinilai"
        />
        {isLoading ? (
          <Spinner label="Memuat antrean tugas..." />
        ) : myAssignments.length === 0 ? (
          <div className="p-8">
            <EmptyState
              title="Tidak Ada Tugas Tertunda"
              description="Saat ini semua penugasan telah diselesaikan atau belum ada berkas baru yang dialokasikan ke akun Anda."
            />
          </div>
        ) : (
          <Table>
            <TableHead>
              <tr>
                <TableHeaderCell>Nomor Usulan</TableHeaderCell>
                <TableHeaderCell>Judul Proposal</TableHeaderCell>
                <TableHeaderCell>Jenis Tugas</TableHeaderCell>
                <TableHeaderCell>Tanggal Ditugaskan</TableHeaderCell>
                <TableHeaderCell>Status</TableHeaderCell>
                <TableHeaderCell className="text-right">Tindakan</TableHeaderCell>
              </tr>
            </TableHead>
            <TableBody>
              {myAssignments.map((task) => (
                <TableRow key={task.id}>
                  <TableCell mono className="font-bold text-blue-900">
                    {task.proposal?.proposal_number || 'USULAN'}
                  </TableCell>
                  <TableCell>
                    <div className="font-bold text-slate-800 max-w-xs truncate">{task.proposal?.title || '-'}</div>
                    <div className="text-[11px] text-slate-400">{task.proposal?.organization?.name}</div>
                  </TableCell>
                  <TableCell>
                    <span className="text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                      {task.assignment_type}
                    </span>
                  </TableCell>
                  <TableCell>{formatDate(task.assigned_at || task.created_at)}</TableCell>
                  <TableCell>
                    <span className={`text-xs font-bold px-2 py-0.5 rounded-full ${task.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}`}>
                      {task.status === 'completed' ? 'Selesai' : 'Aktif'}
                    </span>
                  </TableCell>
                  <TableCell className="text-right">
                    <Link to={`/proposals/${task.proposal_id}`}>
                      <Button variant="primary" size="xs">
                        Buka Berkas
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

export default InternalStaffDashboard;

