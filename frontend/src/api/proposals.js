import apiClient from './client';

export async function getProposals(params = {}) {
  const response = await apiClient.get('/proposals', { params });
  return response.data;
}

export async function getProposal(id) {
  const response = await apiClient.get(`/proposals/${id}`);
  return response.data;
}

export async function createProposal(payload) {
  const response = await apiClient.post('/proposals', payload);
  return response.data;
}

export async function submitProposal(proposalId) {
  const response = await apiClient.post(`/proposals/${proposalId}/submit`);
  return response.data;
}

// Proposal Documents
export async function getProposalDocuments(proposalId) {
  const response = await apiClient.get(`/proposals/${proposalId}/documents`);
  return response.data;
}

export async function uploadProposalDocument(proposalId, formData) {
  const response = await apiClient.post(`/proposals/${proposalId}/documents`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  return response.data;
}

export async function replaceProposalDocument(proposalId, documentId, formData) {
  const response = await apiClient.post(
    `/proposals/${proposalId}/documents/${documentId}/replace`,
    formData,
    { headers: { 'Content-Type': 'multipart/form-data' } }
  );
  return response.data;
}

export async function deleteProposalDocument(proposalId, documentId) {
  const response = await apiClient.delete(`/proposals/${proposalId}/documents/${documentId}`);
  return response.data;
}

export async function downloadProposalDocument(proposalId, documentId) {
  const response = await apiClient.get(`/proposals/${proposalId}/documents/${documentId}/download`, {
    responseType: 'blob',
  });
  return response.data;
}

// Revisions
export async function getProposalRevisions(proposalId) {
  const response = await apiClient.get(`/proposals/${proposalId}/revisions`);
  return response.data;
}

export async function createProposalRevision(proposalId, payload) {
  const response = await apiClient.post(`/proposals/${proposalId}/revisions`, payload);
  return response.data;
}

export async function getProposalRevision(proposalId, revisionId) {
  const response = await apiClient.get(`/proposals/${proposalId}/revisions/${revisionId}`);
  return response.data;
}

export async function submitProposalRevision(proposalId, revisionId, payload) {
  const response = await apiClient.post(
    `/proposals/${proposalId}/revisions/${revisionId}/submit`,
    payload
  );
  return response.data;
}

// PDF Export
export async function getProposalPdfUrl(proposalId) {
  const baseURL = apiClient.defaults.baseURL || 'http://127.0.0.1:8000/api/v1';
  return `${baseURL}/pdf/proposals/${proposalId}`;
}