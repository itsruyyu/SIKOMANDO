export function hasAnyRole(user, allowedRoles = []) {
  if (!user) return false;
  if (!allowedRoles || allowedRoles.length === 0) return true;

  const userRoles = Array.isArray(user.roles)
    ? user.roles.map((r) => (typeof r === 'string' ? r : r.code || r.name))
    : [];

  // Super Admin and Admin have access to all internal features
  if (userRoles.includes('SUPER_ADMIN') || userRoles.includes('ADMIN')) {
    return true;
  }

  return allowedRoles.some((role) => userRoles.includes(role));
}

