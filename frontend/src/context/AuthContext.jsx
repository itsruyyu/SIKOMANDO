import React, { createContext, useContext, useState, useEffect, useCallback, useMemo } from 'react';
import api from '../services/api';
import { ROLES } from '../utils/constants';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(() => localStorage.getItem('token') || sessionStorage.getItem('token'));
  const [isLoading, setIsLoading] = useState(true);

  // Fetch authenticated user profile from /auth/me
  const fetchMe = useCallback(async () => {
    try {
      const response = await api.get('/auth/me');
      // api interceptor returns response.data (the Laravel payload)
      // /auth/me returns: { success, message, data: { ...user with roles[] } }
      // so the user object is at response.data
      const userData = response?.data || response;
      if (userData && (userData.id || userData.email)) {
        setUser(userData);
        localStorage.setItem('user', JSON.stringify(userData));
      } else {
        setUser(null);
      }
    } catch {
      setUser(null);
      localStorage.removeItem('token');
      sessionStorage.removeItem('token');
      localStorage.removeItem('user');
      setToken(null);
    } finally {
      setIsLoading(false);
    }
  }, []);

  // On initial mount or token change, verify user session
  useEffect(() => {
    if (token) {
      fetchMe();
    } else {
      setUser(null);
      setIsLoading(false);
    }
  }, [token, fetchMe]);

  // Login handler
  const login = async (email, password, remember = true) => {
    setIsLoading(true);
    try {
      const res = await api.post('/auth/login', { email, password });
      // api.js interceptor returns response.data directly, so res IS the payload
      // Laravel returns: { success, message, data: { token, user } }
      // After interceptor strips one level: res = { success, message, data: { token, user } }
      // But if interceptor normalizes nested: res = { ..., data: ..., token: ... }
      // Try both extraction paths robustly:
      const receivedToken = res?.data?.token || res?.token;

      if (!receivedToken) {
        throw new Error('Token otentikasi tidak ditemukan dalam respon server.');
      }

      if (remember) {
        localStorage.setItem('token', receivedToken);
      } else {
        sessionStorage.setItem('token', receivedToken);
      }
      setToken(receivedToken);

      // Immediately fetch user profile with the new token
      const meRes = await api.get('/auth/me', {
        headers: { Authorization: `Bearer ${receivedToken}` },
      });

      // meRes is already the payload (interceptor strips AxiosResponse wrapper)
      // Laravel /auth/me returns: { success, message, data: { ...user } }
      const userData = meRes?.data || meRes;
      setUser(userData);
      localStorage.setItem('user', JSON.stringify(userData));

      return { success: true, user: userData };
    } catch (err) {
      setIsLoading(false);
      throw err;
    } finally {
      setIsLoading(false);
    }
  };

  // Logout handler
  const logout = async () => {
    try {
      if (token) {
        await api.post('/auth/logout');
      }
    } catch {
      // Ignored if token was already expired
    } finally {
      localStorage.removeItem('token');
      sessionStorage.removeItem('token');
      localStorage.removeItem('user');
      setToken(null);
      setUser(null);
      window.location.href = '/login';
    }
  };

  // Role verification helper
  const hasRole = useCallback((roleOrRoles) => {
    if (!user || !user.roles) return false;
    // Normalize: API may return roles as objects {id, code, name} or plain strings
    const userRoles = (Array.isArray(user.roles) ? user.roles : []).map((r) =>
      typeof r === 'object' && r !== null ? String(r.code || r.name || '').toUpperCase() : String(r).toUpperCase()
    );

    // Super admin has access to everything
    if (userRoles.includes(ROLES.SUPER_ADMIN)) return true;

    if (Array.isArray(roleOrRoles)) {
      return roleOrRoles.some((r) => userRoles.includes(String(r).toUpperCase()));
    }
    return userRoles.includes(String(roleOrRoles).toUpperCase());
  }, [user]);

  // Primary active role — always a string code
  const primaryRole = useMemo(() => {
    if (!user || !user.roles || user.roles.length === 0) return null;
    const first = user.roles[0];
    return typeof first === 'object' && first !== null
      ? String(first.code || first.name || '').toUpperCase()
      : String(first).toUpperCase();
  }, [user]);

  const value = useMemo(() => ({
    user,
    token,
    isLoading,
    isAuthenticated: !!user,
    login,
    logout,
    refreshUser: fetchMe,
    hasRole,
    primaryRole,
    isSuperAdmin: hasRole(ROLES.SUPER_ADMIN),
    isAdmin: hasRole(ROLES.ADMIN_SIKOMANDO),
    isPemohon: hasRole(ROLES.PEMOHON),
    isVerifikator: hasRole(ROLES.VERIFIKATOR),
    isEvaluator: hasRole(ROLES.EVALUATOR),
    isSurveyor: hasRole(ROLES.SURVEYOR),
    isApprover: hasRole(ROLES.APPROVER),
    isAuditor: hasRole(ROLES.AUDITOR),
  }), [user, token, isLoading, fetchMe, hasRole, primaryRole]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}