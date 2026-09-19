import apiClient from './client';

export async function resolveQrToken(token) {
  const response = await apiClient.get(`/qr/resolve/${token}`);
  return response.data;
}

export async function revokeQr(qrIdentityId, payload = {}) {
  const response = await apiClient.post(`/qr/${qrIdentityId}/revoke`, payload);
  return response.data;
}

export async function regenerateQr(qrIdentityId) {
  const response = await apiClient.post(`/qr/${qrIdentityId}/regenerate`);
  return response.data;
}

export async function getQrLogs(qrIdentityId) {
  const response = await apiClient.get(`/qr/${qrIdentityId}/logs`);
  return response.data;
}

