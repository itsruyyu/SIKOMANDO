import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { getMyAssignments, getWorkloadStats } from '../api/assignments';
import { getWorkloadSummary, getTasksByStatus } from '../api/internalDashboard';
import DataTable from '../components/common/DataTable';
import StatusBadge from '../components/common/StatusBadge';
import LoadingSpinner from '../components/common/LoadingSpinner';
import {
  QueueListIcon,
  ClipboardDocumentCheckIcon,
  ChartBarIcon,
  MapPinIcon,
  CheckCircleIcon,
  ClockIcon,
} from '@heroicons/react/24/outline';

export default function WorkloadDashboardPage() {
  const [assignments, setAssignments] = useState([]);
  const [workload, setWorkload] = useState(null);
  const [tasks, setTasks] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function loadData() {
      try {
        const [assignRes, workRes, taskRes] = await Promise.allSettled([
          getMyAssignments(),
          getWorkloadStats(),
          getTasksByStatus(),
        ]);

        if (assignRes.status === 'fulfilled') {
          const list = assignRes.value?.data || assignRes.value || [];
          setAssignments(Array.isArray(list) ? list : list.data || []);
        }

        if (workRes.status === 'fulfilled') setWorkload(workRes.value?.data || workRes.value);
        if (taskRes.status === 'fulfilled') setTasks(taskRes.value?.data || taskRes.value);
      } catch (err) {
        console.error('Failed loading workload', err);
      } finally {
        setLoading(false);
      }
    }

    loadData();
  }, []);

  const columns = [
    {
      title: 'Judul Usulan Proposal',
      key: 'proposal',
      render: (val, row) => (
        <div>
          <span className="font-mono text-xs text-blue-600 font-semibold">
            #{row.proposal?.proposal_number || row.proposal_id?.slice(0, 8)}
          </span>
          <p className="font-bold text-slate-900">{row.proposal?.title || 'Usulan Kegiatan'}</p>
          <p className="text-xs text-slate-500">{row.proposal?.organization?.name || 'Organisasi Pemohon'}</p>
        </div>
      ),
    },
    {
      title: 'Peran Penugasan',
      key: 'role',
      render: (val, row) => {
        const role = val || row.assignment_type || 'VERIFIKATOR';
        const colorMap = {
          VERIFIKATOR: 'bg-blue-50 text-blue-700 border-blue-200',
          EVALUATOR: 'bg-indigo-50 text-indigo-700 border-indigo-200',
          SURVEYOR: 'bg-purple-50 text-purple-700 border-purple-200',
        };
        return (
          <span
            className={`rounded-full px-2.5 py-0.5 text-xs font-semibold border ${
              colorMap[role] || 'bg-slate-50 text-slate-700 border-slate-200'
            }`}
          >
            {role}
          </span>
        );
      },
    },
    {
      title: 'Status Penugasan',
      key: 'status',
      render: (val) => (
        <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
          {val || 'Ditugaskan'}
        </span>
      ),
    },
    {
      title: 'Aksi',
      key: 'action',
      render: (_, row) => {
        const type = String(row.assignment_type || '').toLowerCase();
        let target = `/proposals/${row.proposal_id}`;
        if (type.includes('verif')) target = `/verification`;
        if (type.includes('eval')) target = `/evaluation`;
        if (type.includes('surv')) target = `/field-surveys`;

        return (
          <Link
            to={target}
            className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
          >
            Buka Tugas
          </Link>
        );
      },
    },
  ];

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Beban Kerja & Antrean Tugas Saya</h1>
        <p className="mt-1 text-xs text-slate-500">
          Monitoring beban kerja pemeriksaan administrasi, evaluasi teknis, dan survei lapangan Anda.
        </p>
      </div>

      {loading ? (
        <LoadingSpinner text="Memuat beban kerja penilai..." />
      ) : (
        <>
          {/* Workload Stats Strip */}
          <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
              <div className="flex items-center justify-between">
                <p className="text-xs font-semibold text-slate-500 uppercase">Tugas Ditugaskan</p>
                <div className="rounded-lg bg-blue-50 p-2 text-blue-600">
                  <QueueListIcon className="h-5 w-5" />
                </div>
              </div>
              <p className="mt-3 text-2xl font-bold text-slate-900">
                {workload?.assigned_count ?? assignments.length}
              </p>
              <p className="mt-1 text-[11px] text-slate-400">Total proposal aktif</p>
            </div>

            <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
              <div className="flex items-center justify-between">
                <p className="text-xs font-semibold text-slate-500 uppercase">Verifikasi Tertunda</p>
                <div className="rounded-lg bg-amber-50 p-2 text-amber-600">
                  <ClockIcon className="h-5 w-5" />
                </div>
              </div>
              <p className="mt-3 text-2xl font-bold text-amber-600">
                {tasks?.pending_verifications ?? 0}
              </p>
              <p className="mt-1 text-[11px] text-slate-400">Menunggu telaah kelengkapan</p>
            </div>

            <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
              <div className="flex items-center justify-between">
                <p className="text-xs font-semibold text-slate-500 uppercase">Evaluasi / Skoring</p>
                <div className="rounded-lg bg-indigo-50 p-2 text-indigo-600">
                  <ChartBarIcon className="h-5 w-5" />
                </div>
              </div>
              <p className="mt-3 text-2xl font-bold text-indigo-600">
                {tasks?.pending_evaluations ?? 0}
              </p>
              <p className="mt-1 text-[11px] text-slate-400">Perlu input nilai kelayakan</p>
            </div>

            <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
              <div className="flex items-center justify-between">
                <p className="text-xs font-semibold text-slate-500 uppercase">Survei Lapangan</p>
                <div className="rounded-lg bg-purple-50 p-2 text-purple-600">
                  <MapPinIcon className="h-5 w-5" />
                </div>
              </div>
              <p className="mt-3 text-2xl font-bold text-purple-600">
                {tasks?.pending_surveys ?? 0}
              </p>
              <p className="mt-1 text-[11px] text-slate-400">Agenda cek fisik lapangan</p>
            </div>
          </div>

          {/* Assignments Table */}
          <div className="space-y-4">
            <h2 className="text-base font-bold text-slate-900">Daftar Penugasan Aktif Saya</h2>
            <DataTable
              columns={columns}
              data={assignments}
              loading={loading}
              searchPlaceholder="Cari penugasan atau nomor proposal..."
              emptyTitle="Tidak Ada Tugas Tertunda"
              emptyDescription="Saat ini Anda tidak memiliki beban kerja penilaian usulan yang aktif."
            />
          </div>
        </>
      )}
    </div>
  );
}

