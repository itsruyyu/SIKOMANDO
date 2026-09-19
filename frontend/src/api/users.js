import apiClient from './client';

export async function getUsers(params = {}) {
  const response = await apiClient.get('/users', { params });
  return response.data;
}

export async function createUser(payload) {
  const response = await apiClient.post('/users', payload);
  const normalized = {
    ...payload,
    roles: payload.roles || (payload.role ? [payload.role] : undefined),
  };
  const response = await apiClient.post('/users', normalized);
  return response.data;
}

export async function getUsersByRole(roleCode) {
  const response = await apiClient.get(`/users/by-role/${roleCode}`);
  return response.data;
}

export async function getUser(userId) {
  const response = await apiClient.get(`/users/${userId}`);
  return response.data;
}

export async function updateUser(userId, payload) {
  const response = await apiClient.patch(`/users/${userId}`, payload);
  return response.data;
}

export async function toggleUserActive(userId) {
  const response = await apiClient.post(`/users/${userId}/toggle-active`);
  return response.data;
}

export async function assignUserRole(userId, roleCode) {
  const response = await apiClient.post(`/users/${userId}/assign-role`, { role: roleCode });
  return response.data;
}

export async function removeUserRole(userId, roleCode) {
  const response = await apiClient.post(`/users/${userId}/remove-role`, { role: roleCode });
  return response.data;
}

export async function resetUserPassword(userId, payload = {}) {
  const response = await apiClient.post(`/users/${userId}/reset-password`, payload);
  return response.data;
}

