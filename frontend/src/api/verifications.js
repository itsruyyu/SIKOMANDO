import apiClient from './client';

export async function getVerifications(proposalId) {
  const response = await apiClient.get(`/proposals/${proposalId}/verifications`);
  return response.data;
}

export async function startVerification(proposalId, payload = {}) {
  const response = await apiClient.post(`/proposals/${proposalId}/verifications`, payload);
  return response.data;
}

export async function getVerification(proposalId, verificationId) {
  const response = await apiClient.get(`/proposals/${proposalId}/verifications/${verificationId}`);
  return response.data;
}

export async function updateVerificationItem(proposalId, verificationId, itemId, payload) {
  const response = await apiClient.patch(
    `/proposals/${proposalId}/verifications/${verificationId}/items/${itemId}`,
    payload
  );
  return response.data;
}

export async function completeVerification(proposalId, verificationId, payload) {
  const response = await apiClient.post(
    `/proposals/${proposalId}/verifications/${verificationId}/complete`,
    payload
  );
  return response.data;
}

export function getVerificationPdfUrl(proposalId, verificationId) {
  const baseURL = apiClient.defaults.baseURL || 'http://127.0.0.1:8000/api/v1';
  return `${baseURL}/pdf/proposals/${proposalId}/verifications/${verificationId}`;
}

