import apiClient from './client';

export async function getAssignments(params = {}) {
  const response = await apiClient.get('/assignments', { params });
  return response.data;
}

export async function getMyAssignments() {
  const response = await apiClient.get('/assignments/my');
  return response.data;
}

export async function getWorkloadStats() {
  const response = await apiClient.get('/assignments/workload');
  return response.data;
}

export async function createAssignment(payload) {
  const response = await apiClient.post('/assignments', payload);
  return response.data;
}

export async function getAssignment(assignmentId) {
  const response = await apiClient.get(`/assignments/${assignmentId}`);
  return response.data;
}

export async function revokeAssignment(assignmentId) {
  const response = await apiClient.post(`/assignments/${assignmentId}/revoke`);
  return response.data;
}

export async function getProposalAssignments(proposalId) {
  const response = await apiClient.get(`/proposals/${proposalId}/assignments`);
  return response.data;
}

