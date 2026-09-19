import apiClient from './client';

export async function getApprovals(params = {}) {
  const response = await apiClient.get('/approvals', { params });
  return response.data;
}

export async function getApproval(approvalId) {
  const response = await apiClient.get(`/approvals/${approvalId}`);
  return response.data;
}

export async function reviewApproval(approvalId, payload = {}) {
  const response = await apiClient.post(`/approvals/${approvalId}/review`, payload);
  return response.data;
}

export async function approveApproval(approvalId, payload = {}) {
  const response = await apiClient.post(`/approvals/${approvalId}/approve`, payload);
  return response.data;
}

export async function rejectApproval(approvalId, payload = {}) {
  const response = await apiClient.post(`/approvals/${approvalId}/reject`, payload);
  return response.data;
}

export async function getProposalApprovals(proposalId) {
  const response = await apiClient.get(`/proposals/${proposalId}/approvals`);
  return response.data;
}

export async function createProposalApproval(proposalId, payload) {
  const response = await apiClient.post(`/proposals/${proposalId}/approvals`, payload);
  return response.data;
}

