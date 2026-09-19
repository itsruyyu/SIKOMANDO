import apiClient from './client';

export async function getDisbursements(params = {}) {
  const response = await apiClient.get('/disbursements', { params });
  return response.data;
}

export async function getDisbursement(disbursementId) {
  const response = await apiClient.get(`/disbursements/${disbursementId}`);
  return response.data;
}

export async function verifyDisbursement(disbursementId, payload = {}) {
  const response = await apiClient.post(`/disbursements/${disbursementId}/verify`, payload);
  return response.data;
}

export async function approveDisbursement(disbursementId, payload = {}) {
  const response = await apiClient.post(`/disbursements/${disbursementId}/approve`, payload);
  return response.data;
}

export async function recordDisbursementTransaction(disbursementId, payload) {
  const response = await apiClient.post(
    `/disbursements/${disbursementId}/transactions`,
    payload
  );
  return response.data;
}

export async function getProposalDisbursements(proposalId) {
  const response = await apiClient.get(`/proposals/${proposalId}/disbursements`);
  return response.data;
}

export async function getProposalDisbursementSummary(proposalId) {
  const response = await apiClient.get(`/proposals/${proposalId}/disbursement-summary`);
  return response.data;
}

export async function storeDisbursementPlan(proposalId, payload) {
  const response = await apiClient.post(
    `/proposals/${proposalId}/disbursement-plans`,
    payload
  );
  return response.data;
}

export function getDisbursementPdfUrl(disbursementId) {
  const baseURL = apiClient.defaults.baseURL || 'http://127.0.0.1:8000/api/v1';
  return `${baseURL}/pdf/disbursements/${disbursementId}`;
}

