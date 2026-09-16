import apiClient from './client';

export async function getOrganizations(params = {}) {
  const response = await apiClient.get('/organizations', {
    params,
  });

  return response.data;
}

export async function getOrganization(id) {
  const response = await apiClient.get(`/organizations/${id}`);

  return response.data;
}