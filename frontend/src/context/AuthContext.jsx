import { useEffect, useState } from 'react';
import {
  getCurrentUser,
  login as loginRequest,
  logout as logoutRequest,
} from '../api/auth';
import { AuthContext } from './AuthContext.js';

const TOKEN_KEY = 'sikomando_token';

function extractUser(payload) {
  return payload?.data?.user || payload?.data || payload?.user || payload;
}

function extractToken(payload) {
  return payload?.token || payload?.data?.token || null;
}

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let active = true;

    async function initializeAuth() {
      const token = localStorage.getItem(TOKEN_KEY);

      if (!token) {
        if (active) {
          setLoading(false);
        }

        return;
      }

      try {
        const response = await getCurrentUser();

        if (active) {
          setUser(extractUser(response));
        }
      } catch {
        localStorage.removeItem(TOKEN_KEY);

        if (active) {
          setUser(null);
        }
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    }

    initializeAuth();

    return () => {
      active = false;
    };
  }, []);

  async function login(credentials) {
    const response = await loginRequest(credentials);
    const token = extractToken(response);

    if (!token) {
      throw new Error('Token autentikasi tidak ditemukan.');
    }

    localStorage.setItem(TOKEN_KEY, token);

    const currentUserResponse = await getCurrentUser();
    const authenticatedUser = extractUser(currentUserResponse);

    setUser(authenticatedUser);

    return authenticatedUser;
  }

  async function logout() {
    try {
      await logoutRequest();
    } finally {
      localStorage.removeItem(TOKEN_KEY);
      setUser(null);
    }
  }

  const value = {
    user,
    loading,
    isAuthenticated: Boolean(user),
    login,
    logout,
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
}