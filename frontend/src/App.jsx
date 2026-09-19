import React from 'react';
import {
  BrowserRouter,
  Navigate,
  Outlet,
  Route,
  Routes,
} from 'react-router-dom';

import { AuthProvider } from './context/AuthContext';
import { ToastProvider } from './context/ToastContext';
import ProtectedRoute from './routes/ProtectedRoute';
import AppShell from './components/layout/AppShell';
import PublicLayout from './components/layout/PublicLayout';

// Public Feature Pages
import LandingPage from './features/public-portal/LandingPage';
import ProgramsPage from './features/public-portal/ProgramsPage';
import ProgramDetailPage from './features/public-portal/ProgramDetailPage';
import TrackerPage from './features/public-portal/TrackerPage';
import StatisticsPage from './features/public-portal/StatisticsPage';
import TransparencyPage from './features/public-portal/TransparencyPage';
import VerifyQrPage from './features/public-portal/VerifyQrPage';

// Auth Feature Page
import LoginPage from './features/auth/LoginPage';

// Dashboard Router (Role-Adaptive)
import DashboardRouter from './features/dashboard/DashboardRouter';

// Core Workflow Feature Pages
import ProposalListPage from './features/proposals/ProposalListPage';
import ProposalWizardPage from './features/proposals/ProposalWizardPage';
import ProposalDetailPage from './features/proposals/ProposalDetailPage';

import VerificationListPage from './features/verifications/VerificationListPage';
import VerificationWorkspacePage from './features/verifications/VerificationWorkspacePage';

import EvaluationListPage from './features/evaluations/EvaluationListPage';
import EvaluationWorkspacePage from './features/evaluations/EvaluationWorkspacePage';

import FieldSurveyListPage from './features/surveys/FieldSurveyListPage';
import FieldSurveyWorkspacePage from './features/surveys/FieldSurveyWorkspacePage';

import RankingRecommendationPage from './features/recommendations/RankingRecommendationPage';

import ApprovalListPage from './features/approvals/ApprovalListPage';
import ExecutiveDossierPage from './features/approvals/ExecutiveDossierPage';

import DecisionListPage from './features/decisions/DecisionListPage';
import DecisionDetailPage from './features/decisions/DecisionDetailPage';

import DisbursementListPage from './features/disbursements/DisbursementListPage';
import DisbursementDetailPage from './features/disbursements/DisbursementDetailPage';

import RealizationListPage from './features/realizations/RealizationListPage';
import RealizationDetailPage from './features/realizations/RealizationDetailPage';

import LpjListPage from './features/lpj/LpjListPage';
import LpjDetailPage from './features/lpj/LpjDetailPage';

// Governance, Audit & Admin Feature Pages
import AuditLogListPage from './features/audit/AuditLogListPage';
import UserManagementPage from './features/users/UserManagementPage';
import PolicyConfigurationPage from './features/policies/PolicyConfigurationPage';
import QrManagementPage from './features/qr/QrManagementPage';
import NotificationInboxPage from './features/notifications/NotificationInboxPage';

function ProtectedLayout() {
  return (
    <ProtectedRoute>
      <AppShell>
        <Outlet />
      </AppShell>
    </ProtectedRoute>
  );
}

export default function App() {
  return (
    <BrowserRouter>
      <ToastProvider>
        <AuthProvider>
          <Routes>
            {/* Public Portal Routes */}
            <Route element={<PublicLayout />}>
              <Route path="/" element={<LandingPage />} />
              <Route path="/programs" element={<ProgramsPage />} />
              <Route path="/programs/:id" element={<ProgramDetailPage />} />
              <Route path="/tracker" element={<TrackerPage />} />
              <Route path="/statistics" element={<StatisticsPage />} />
              <Route path="/transparency" element={<TransparencyPage />} />
              <Route path="/verify" element={<VerifyQrPage />} />
              <Route path="/verify/:token" element={<VerifyQrPage />} />
            </Route>

            {/* Authentication */}
            <Route path="/login" element={<LoginPage />} />

            {/* Authenticated Internal & Applicant Area */}
            <Route element={<ProtectedLayout />}>
              {/* Role-Adaptive Dashboard */}
              <Route path="/dashboard" element={<DashboardRouter />} />

              {/* Notifications */}
              <Route path="/notifications" element={<NotificationInboxPage />} />

              {/* Proposal Lifecycle */}
              <Route path="/proposals" element={<ProposalListPage />} />
              <Route path="/proposals/create" element={<ProposalWizardPage />} />
              <Route path="/proposals/:id" element={<ProposalDetailPage />} />

              {/* Verifications */}
              <Route path="/verifications" element={<VerificationListPage />} />
              <Route path="/verifications/:proposalId" element={<VerificationWorkspacePage />} />

              {/* Evaluations */}
              <Route path="/evaluations" element={<EvaluationListPage />} />
              <Route path="/evaluations/:proposalId" element={<EvaluationWorkspacePage />} />

              {/* Field Surveys */}
              <Route path="/field-surveys" element={<FieldSurveyListPage />} />
              <Route path="/field-surveys/:proposalId" element={<FieldSurveyWorkspacePage />} />

              {/* TAPD Ranking Recommendations */}
              <Route path="/recommendations" element={<RankingRecommendationPage />} />

              {/* Approvals & Executive Dossier */}
              <Route path="/approvals" element={<ApprovalListPage />} />
              <Route path="/approvals/:approvalId" element={<ExecutiveDossierPage />} />

              {/* Decisions (SK Gubernur) */}
              <Route path="/decisions" element={<DecisionListPage />} />
              <Route path="/decisions/:id" element={<DecisionDetailPage />} />

              {/* Disbursements (SP2D) */}
              <Route path="/disbursements" element={<DisbursementListPage />} />
              <Route path="/disbursements/:id" element={<DisbursementDetailPage />} />

              {/* Realizations & Receipts */}
              <Route path="/realizations" element={<RealizationListPage />} />
              <Route path="/realizations/:proposalId" element={<RealizationDetailPage />} />

              {/* LPJ Reporting & Account Closing */}
              <Route path="/lpj" element={<LpjListPage />} />
              <Route path="/lpj/:id" element={<LpjDetailPage />} />

              {/* Audit Trail Logs */}
              <Route path="/audit-logs" element={<AuditLogListPage />} />

              {/* QR Management & Cryptographic Resolver */}
              <Route path="/qr-management" element={<QrManagementPage />} />

              {/* User Management & Roles */}
              <Route path="/users" element={<UserManagementPage />} />

              {/* Policy & Rubric Configurations */}
              <Route path="/policies" element={<PolicyConfigurationPage />} />

              {/* Route Compatibility Redirects */}
              <Route path="/verification" element={<Navigate to="/verifications" replace />} />
              <Route path="/evaluation" element={<Navigate to="/evaluations" replace />} />
              <Route path="/rankings" element={<Navigate to="/recommendations" replace />} />
              <Route path="/workload" element={<Navigate to="/dashboard" replace />} />
              <Route path="/organizations" element={<Navigate to="/dashboard" replace />} />
              <Route path="/settings" element={<Navigate to="/policies" replace />} />
              <Route path="/assignments" element={<Navigate to="/dashboard" replace />} />
            </Route>

            {/* Fallback */}
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </AuthProvider>
      </ToastProvider>
    </BrowserRouter>
  );
}