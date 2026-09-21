import React from 'react';
import { useAuth } from '../../context/AuthContext';
import { ROLES } from '../../utils/constants';
import PemohonDashboard from './PemohonDashboard';
import InternalStaffDashboard from './InternalStaffDashboard';
import ApproverDashboard from './ApproverDashboard';
import AuditorDashboard from './AuditorDashboard';
import AdminDashboard from './AdminDashboard';

export function DashboardRouter() {
  const { hasRole } = useAuth();

  if (hasRole(ROLES.SUPER_ADMIN) || hasRole(ROLES.ADMIN_SIKOMANDO)) {
    return <AdminDashboard />;
  }

  if (hasRole(ROLES.APPROVER)) {
    return <ApproverDashboard />;
  }

  if (hasRole(ROLES.AUDITOR)) {
    return <AuditorDashboard />;
  }

  if (hasRole([ROLES.VERIFIKATOR, ROLES.EVALUATOR, ROLES.SURVEYOR])) {
    return <InternalStaffDashboard />;
  }

  if (hasRole(ROLES.PEMOHON)) {
    return <PemohonDashboard />;
  }

  // Fallback if account has no assigned RBAC role yet
  return (
    <div className="max-w-xl mx-auto my-16 p-8 bg-white rounded-2xl shadow-sm border border-amber-200 text-center space-y-4">
      <div className="w-12 h-12 mx-auto bg-amber-100 text-amber-600 rounded-full flex items-center justify-center font-bold text-xl">
        !
      </div>
      <h2 className="text-lg font-bold text-slate-900">Peran Akun Belum Ditugaskan</h2>
      <p className="text-sm text-slate-600">
        Akun Anda belum memiliki penugasan peran kewenangan (RBAC Role) dalam sistem SIKOMANDO. Silakan hubungi Administrator untuk mengatur hak akses akun Anda.
      </p>
    </div>
  );
}

export default DashboardRouter;
