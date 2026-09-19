import apiClient from './client';

export async function getAuditLogs(params = {}) {
  const response = await apiClient.get('/internal/audit-logs', { params });
  return response.data;
}

export async function getAuditLog(auditLogId) {
  const response = await apiClient.get(`/internal/audit-logs/${auditLogId}`);
  return response.data;
}

