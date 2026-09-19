import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import Alert from '../components/feedback/Alert';
import Button from '../ui/Button';

export function RoleRoute({ allowedRoles = [], children }) {
  const { hasRole, primaryRole, isLoading } = useAuth();

  if (isLoading) {
    return null;
  }

  const isAllowed = allowedRoles.length === 0 || hasRole(allowedRoles);

  if (!isAllowed) {
    return (
      <div className="max-w-xl mx-auto py-12">
        <Alert
          type="danger"
          title="Akses Dibatasi (Unauthorized Role)"
        >
          <p className="mb-4">
            Akun Anda dengan peran <strong className="uppercase">{primaryRole || 'GUEST'}</strong> tidak memiliki hak akses untuk membuka halaman ini.
          </p>
          <Button variant="secondary" size="sm" onClick={() => window.location.href = '/dashboard'}>
            Kembali ke Dashboard Utama
          </Button>
        </Alert>
      </div>
    );
  }

  return children;
}

export default RoleRoute;
