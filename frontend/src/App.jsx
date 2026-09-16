import {
  BrowserRouter,
  Navigate,
  Outlet,
  Route,
  Routes,
} from 'react-router-dom';

import { AuthProvider } from './context/AuthContext.jsx';
import ProtectedRoute from './routes/ProtectedRoute';
import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import OrganizationsPage from './pages/OrganizationsPage';
import OrganizationDetailPage from './pages/OrganizationDetailPage';
import ProposalsPage from './pages/ProposalsPage';
import ProposalCreatePage from './pages/ProposalCreatePage';
import ProposalDetailPage from './pages/ProposalDetailPage';
import AppShell from './components/layout/AppShell';

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
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<LoginPage />} />

          <Route element={<ProtectedLayout />}>
            <Route path="/dashboard" element={<DashboardPage />} />

            <Route
              path="/organizations"
              element={<OrganizationsPage />}
            />

            <Route
              path="/organizations/:id"
              element={<OrganizationDetailPage />}
            />

            <Route path="/proposals" element={<ProposalsPage />} />

            <Route
              path="/proposals/create"
              element={<ProposalCreatePage />}
            />

            <Route
              path="/proposals/:id"
              element={<ProposalDetailPage />}
            />
          </Route>

          <Route
            path="/"
            element={<Navigate to="/dashboard" replace />}
          />

          <Route
            path="*"
            element={<Navigate to="/dashboard" replace />}
          />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  );
}