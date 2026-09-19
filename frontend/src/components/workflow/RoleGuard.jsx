import React from 'react';
import { useAuth } from '../../context/useAuth';
import { hasAnyRole } from '../../utils/roleUtils';

export default function RoleGuard({ allowedRoles = [], fallback = null, children }) {
  const { user } = useAuth();

  if (!hasAnyRole(user, allowedRoles)) {
    return fallback;
  }

  return <>{children}</>;
}

