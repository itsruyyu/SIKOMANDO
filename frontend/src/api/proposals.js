import apiClient from './client';

export async function getProposals(params = {}) {
  const response = await apiClient.get('/proposals', {
    params,
  });

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