import apiClient from './client';

export async function getDecisions(params = {}) {
  const response = await apiClient.get('/decisions', { params });
  return response.data;
}

export async function getDecision(decisionId) {
  const response = await apiClient.get(`/decisions/${decisionId}`);
  return response.data;
}

export async function generateDecisionDocument(decisionId, payload = {}) {
  const response = await apiClient.post(`/decisions/${decisionId}/documents`, payload);
  return response.data;
}

export async function getDecisionDocument(decisionId, docId) {
  const response = await apiClient.get(`/decisions/${decisionId}/documents/${docId}`);
  return response.data;
}

export async function downloadDecisionDocument(decisionId, docId) {
  const response = await apiClient.get(
    `/decisions/${decisionId}/documents/${docId}/download`,
    { responseType: 'blob' }
  );
  return response.data;
}

export async function getProposalDecision(proposalId) {
  const response = await apiClient.get(`/proposals/${proposalId}/decision`);
  return response.data;
}

export function getDecisionPdfUrl(decisionId) {
  const baseURL = apiClient.defaults.baseURL || 'http://127.0.0.1:8000/api/v1';
  return `${baseURL}/pdf/decisions/${decisionId}`;
}

