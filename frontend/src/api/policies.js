import apiClient from './client';

export async function getPolicyConfigurations() {
  const response = await apiClient.get('/policy-configurations');
  return response.data;
}

export async function getPolicyVersions(code) {
  const response = await apiClient.get(`/policy-configurations/${code}/versions`);
  return response.data;
}

export async function storePolicyVersion(code, payload) {
  const response = await apiClient.post(`/policy-configurations/${code}/versions`, payload);
  return response.data;
}

export async function approvePolicyVersion(policyVersionId) {
  const response = await apiClient.post(
    `/policy-configurations/versions/${policyVersionId}/approve`
  );
  return response.data;
}

export async function activatePolicyVersion(policyVersionId) {
  const response = await apiClient.post(
    `/policy-configurations/versions/${policyVersionId}/activate`
  );
  return response.data;
}

