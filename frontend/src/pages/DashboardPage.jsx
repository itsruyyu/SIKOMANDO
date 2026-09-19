import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../context/useAuth';
import { getProposals } from '../api/proposals';
import { getWorkloadSummary } from '../api/internalDashboard';
import { getPublicStatisticsSummary } from '../api/public';
import StatusBadge from '../components/common/StatusBadge';
import LoadingSpinner from '../components/common/LoadingSpinner';
import {
  DocumentTextIcon,
  PlusIcon,
  ClipboardDocumentCheckIcon,
  ChartBarIcon,
  MapPinIcon,
  CheckBadgeIcon,
  ArrowRightIcon,
  ClockIcon,
  ShieldCheckIcon,
} from '@heroicons/react/24/outline';

export default function DashboardPage() {
  const { user } = useAuth();
  const [proposals, setProposals] = useState([]);
  const [totalProposals, setTotalProposals] = useState(0);
  const [stats, setStats] = useState(null);
  const [workload, setWorkload] = useState(null);
  const [loading, setLoading] = useState(true);

  const userRoles = Array.isArray(user?.roles)
    ? user.roles.map((r) => (typeof r === 'string' ? r : r.code || r.name))
    : [];

  const isPemohon = userRoles.includes('PEMOHON') || userRoles.length === 0;
  const isInternal = userRoles.some((r) =>
    ['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'ADMIN', 'VERIFIKATOR', 'EVALUATOR', 'SURVEYOR', 'APPROVER', 'AUDITOR'].includes(r)
  );

  useEffect(() => {
    async function loadDashboardData() {
      try {
        const [propRes, statRes, workRes] = await Promise.allSettled([
          getProposals({ per_page: 5 }),
          getPublicStatisticsSummary(),
          isInternal ? getWorkloadSummary() : Promise.resolve(null),
        ]);

        if (propRes.status === 'fulfilled') {
          const payload = propRes.value?.data || propRes.value || [];
          const items = Array.isArray(payload) ? payload : payload.data || [];
          setProposals(items);
          const totalFromMeta = propRes.value?.meta?.total ?? (Array.isArray(payload) ? items.length : payload?.total ?? items.length);
          setTotalProposals(totalFromMeta);
        }

        if (statRes.status === 'fulfilled' && statRes.value) {
          const statData = statRes.value?.data || statRes.value;
          setStats(statData);
        }

        if (workRes.status === 'fulfilled' && workRes.value) {
          setWorkload(workRes.value?.data || workRes.value);
        }
      } catch (err) {
        console.error('Failed loading dashboard data', err);
      } finally {
        setLoading(false);
      }
    }

    loadDashboardData();
  }, [isInternal]);

  // Derive metrics accurately from DB stats when internal, or from user's proposals metadata
  const displayTotal = isInternal
    ? (stats?.total_proposals_submitted ?? totalProposals)
    : totalProposals;

  const displayInProcess = isInternal
    ? (stats?.proposals_in_process ?? 0)
    : proposals.filter((p) => ['submitted', 'verification', 'revision', 'verified', 'evaluation', 'survey', 'recommended', 'approval'].includes(p.status)).length;

  const displayApproved = isInternal
    ? (stats?.proposals_approved ?? 0)
    : proposals.filter((p) => ['approved', 'disbursed', 'implementation', 'completed'].includes(p.status)).length;

  return (
    <div className="space-y-8">
      {/* Welcome Banner */}
      <div className="rounded-2xl bg-gradient-to-r from-blue-700 via-indigo-700 to-slate-900 p-6 sm:p-8 text-white shadow-xs">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <div className="flex items-center gap-2">
              <span className="rounded-full bg-white/20 px-3 py-0.5 text-xs font-semibold text-white backdrop-blur-xs">
                {userRoles[0] || 'PEMOHON'}
              </span>
              <span className="text-xs text-blue-200">
                Tahun Anggaran {new Date().getFullYear()}
              </span>
            </div>
            <h1 className="mt-3 text-2xl font-black tracking-tight sm:text-3xl">
              Selamat datang, {user?.name || 'Pengguna'}
            </h1>
            <p className="mt-1 text-sm text-blue-100">
              SIKOMANDO siap mendampingi pengelolaan hibah organisasi secara tertib, akuntabel, dan transparan.
            </p>
          </div>

          <div className="flex items-center gap-3">
            <Link
              to="/proposals/create"
              className="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-bold text-blue-900 shadow-xs hover:bg-blue-50 transition"
            >
              <PlusIcon className="h-4 w-4 stroke-2" />
              <span>Ajukan Usulan Baru</span>
            </Link>
          </div>
        </div>
      </div>

      {/* Metric Cards */}
      {loading ? (
        <LoadingSpinner text="Memuat metrik dashboard..." />
      ) : (
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
          <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            <div className="flex items-center justify-between">
              <p className="text-xs font-semibold uppercase text-slate-500">
                {isInternal ? 'Total Usulan Terdaftar' : 'Usulan Saya'}
              </p>
              <div className="rounded-lg bg-blue-50 p-2 text-blue-600">
                <DocumentTextIcon className="h-5 w-5" />
              </div>
            </div>
            <p className="mt-4 text-2xl font-bold text-slate-900">{displayTotal}</p>
            <p className="mt-1 text-[11px] text-slate-500">
              {isInternal ? 'Total usulan di database' : 'Proposal terdaftar dalam sistem'}
            </p>
          </div>

          <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            <div className="flex items-center justify-between">
              <p className="text-xs font-semibold uppercase text-slate-500">
                {isInternal ? 'Usulan Dalam Proses' : 'Dalam Verifikasi'}
              </p>
              <div className="rounded-lg bg-amber-50 p-2 text-amber-600">
                <ClockIcon className="h-5 w-5" />
              </div>
            </div>
            <p className="mt-4 text-2xl font-bold text-amber-600">{displayInProcess}</p>
            <p className="mt-1 text-[11px] text-slate-500">
              {isInternal ? 'Tahap verifikasi, evaluasi, & rekomendasi' : 'Menunggu pemeriksaan berkas'}
            </p>
          </div>

          <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            <div className="flex items-center justify-between">
              <p className="text-xs font-semibold uppercase text-slate-500">Disetujui / SK</p>
              <div className="rounded-lg bg-emerald-50 p-2 text-emerald-600">
                <CheckBadgeIcon className="h-5 w-5" />
              </div>
            </div>
            <p className="mt-4 text-2xl font-bold text-emerald-600">{displayApproved}</p>
            <p className="mt-1 text-[11px] text-slate-500">Telah ditetapkan penerima</p>
          </div>

          <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            <div className="flex items-center justify-between">
              <p className="text-xs font-semibold uppercase text-slate-500">Integritas QR</p>
              <div className="rounded-lg bg-indigo-50 p-2 text-indigo-600">
                <ShieldCheckIcon className="h-5 w-5" />
              </div>
            </div>
            <p className="mt-4 text-2xl font-bold text-indigo-600">Aktif</p>
            <p className="mt-1 text-[11px] text-slate-500">Traceability & digital signature</p>
          </div>
        </div>
      )}

      {/* Reviewer / Internal Workload Quick Access (if applicable) */}
      {isInternal && (
        <div className="rounded-xl border border-slate-200 bg-slate-50/70 p-6">
          <h2 className="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Akses Cepat Tim Penilai & Penelaah
          </h2>
          <div className="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <Link
              to="/verification"
              className="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-4 text-center shadow-2xs hover:border-blue-500 hover:shadow-xs transition"
            >
              <ClipboardDocumentCheckIcon className="h-6 w-6 text-blue-600" />
              <span className="mt-2 text-xs font-semibold text-slate-800">Verifikasi Berkas</span>
            </Link>

            <Link
              to="/evaluation"
              className="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-4 text-center shadow-2xs hover:border-blue-500 hover:shadow-xs transition"
            >
              <ChartBarIcon className="h-6 w-6 text-indigo-600" />
              <span className="mt-2 text-xs font-semibold text-slate-800">Evaluasi Teknis</span>
            </Link>

            <Link
              to="/field-surveys"
              className="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-4 text-center shadow-2xs hover:border-blue-500 hover:shadow-xs transition"
            >
              <MapPinIcon className="h-6 w-6 text-purple-600" />
              <span className="mt-2 text-xs font-semibold text-slate-800">Survei Lapangan</span>
            </Link>

            <Link
              to="/approvals"
              className="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-4 text-center shadow-2xs hover:border-blue-500 hover:shadow-xs transition"
            >
              <CheckBadgeIcon className="h-6 w-6 text-emerald-600" />
              <span className="mt-2 text-xs font-semibold text-slate-800">Persetujuan Pimpinan</span>
            </Link>
          </div>
        </div>
      )}

      {/* Recent Proposals Section */}
      <div className="rounded-xl border border-slate-200 bg-white shadow-xs overflow-hidden">
        <div className="flex items-center justify-between border-b border-slate-200 px-6 py-4">
          <div>
            <h2 className="text-base font-bold text-slate-900">Usulan Hibah Terkini</h2>
            <p className="text-xs text-slate-500">Daftar usulan yang sedang dalam penanganan</p>
          </div>
          <Link
            to="/proposals"
            className="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700"
          >
            <span>Lihat Semua Usulan</span>
            <ArrowRightIcon className="h-3.5 w-3.5" />
          </Link>
        </div>

        <div className="divide-y divide-slate-100">
          {loading ? (
            <div className="p-8">
              <LoadingSpinner text="Memuat usulan terkini..." />
            </div>
          ) : proposals.length === 0 ? (
            <div className="p-8 text-center text-xs text-slate-500">
              Belum ada usulan hibah yang diajukan. Klik tombol "Ajukan Usulan Baru" untuk memulai.
            </div>
          ) : (
            proposals.map((item) => (
              <div
                key={item.id}
                className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 hover:bg-slate-50/80 transition"
              >
                <div>
                  <div className="flex items-center gap-2">
                    <span className="text-xs font-semibold text-blue-600 font-mono">
                      #{item.proposal_number || item.id?.slice(0, 8)}
                    </span>
                    <StatusBadge status={item.status} />
                  </div>
                  <h3 className="mt-1.5 text-sm font-bold text-slate-900">{item.title}</h3>
                  <p className="text-xs text-slate-500">
                    {item.organization?.name || 'Organisasi Pemohon'} • Pagu Dimohon:{' '}
                    <span className="font-semibold text-slate-700">
                      {item.requested_amount
                        ? `Rp ${Number(item.requested_amount).toLocaleString('id-ID')}`
                        : 'Belum terisi'}
                    </span>
                  </p>
                </div>

                <div className="flex items-center gap-2">
                  <Link
                    to={`/proposals/${item.id}`}
                    className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                  >
                    Buka Detail
                  </Link>
                </div>
              </div>
            ))
          )}
        </div>
      </div>
    </div>
  );
}