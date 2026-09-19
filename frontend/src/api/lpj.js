import apiClient from './client';

export async function getLpjs(params = {}) {
  const response = await apiClient.get('/lpj', { params });
  return response.data;
}

export async function getLpj(lpjId) {
  const response = await apiClient.get(`/lpj/${lpjId}`);
  return response.data;
}

export async function updateLpj(lpjId, payload) {
  const response = await apiClient.patch(`/lpj/${lpjId}`, payload);
  return response.data;
}

export async function submitLpj(lpjId) {
  const response = await apiClient.post(`/lpj/${lpjId}/submit`);
  return response.data;
}

export async function reviewLpj(lpjId, payload = {}) {
  const response = await apiClient.post(`/lpj/${lpjId}/review`, payload);
  return response.data;
}

export async function requestLpjRevision(lpjId, payload) {
  const response = await apiClient.post(`/lpj/${lpjId}/request-revision`, payload);
  return response.data;
}

export async function approveLpj(lpjId, payload = {}) {
  const response = await apiClient.post(`/lpj/${lpjId}/approve`, payload);
  return response.data;
}

export async function rejectLpj(lpjId, payload = {}) {
  const response = await apiClient.post(`/lpj/${lpjId}/reject`, payload);
  return response.data;
}

export async function finalizeLpj(lpjId, payload = {}) {
  const response = await apiClient.post(`/lpj/${lpjId}/finalize`, payload);
  return response.data;
}

export async function uploadLpjDocument(lpjId, formData) {
  const response = await apiClient.post(`/lpj/${lpjId}/documents`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  return response.data;
}

export async function downloadLpjDocument(lpjId, docId) {
  const response = await apiClient.get(`/lpj/${lpjId}/documents/${docId}/download`, {
    responseType: 'blob',
  });
  return response.data;
}

export async function getProposalLpjs(proposalId) {
  const response = await apiClient.get(`/proposals/${proposalId}/lpj`);
  return response.data;
}

export async function storeProposalLpj(proposalId, payload) {
  const response = await apiClient.post(`/proposals/${proposalId}/lpj`, payload);
  return response.data;
}

export async function getProposalClosingSummary(proposalId) {
  const response = await apiClient.get(`/proposals/${proposalId}/closing-summary`);
  return response.data;
}

export async function closeProposal(proposalId, payload = {}) {
  const response = await apiClient.post(`/proposals/${proposalId}/close`, payload);
  return response.data;
}

export function getLpjPdfUrl(lpjId) {
  const baseURL = apiClient.defaults.baseURL || 'http://127.0.0.1:8000/api/v1';
  return `${baseURL}/pdf/lpj/${lpjId}`;
}

