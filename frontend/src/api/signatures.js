import apiClient from './client';

// Profiles
export async function getSignatureProfiles() {
  const response = await apiClient.get('/signatures/profiles');
  return response.data;
}

export async function createSignatureProfile(payload) {
  const response = await apiClient.post('/signatures/profiles', payload);
  return response.data;
}

export async function getSignatureProfile(profileId) {
  const response = await apiClient.get(`/signatures/profiles/${profileId}`);
  return response.data;
}

export async function updateSignatureProfile(profileId, payload) {
  const response = await apiClient.put(`/signatures/profiles/${profileId}`, payload);
  return response.data;
}

export async function uploadSignatureVisual(profileId, formData) {
  const response = await apiClient.post(`/signatures/profiles/${profileId}/visual`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  return response.data;
}

// Signatures Execution
export async function getPendingSignatures() {
  const response = await apiClient.get('/signatures/pending');
  return response.data;
}

export async function requestSignature(payload) {
  const response = await apiClient.post('/signatures/request', payload);
  return response.data;
}

export async function getSignature(signatureId) {
  const response = await apiClient.get(`/signatures/${signatureId}`);
  return response.data;
}

export async function signSignature(signatureId, payload = {}) {
  const response = await apiClient.post(`/signatures/${signatureId}/sign`, payload);
  return response.data;
}

export async function rejectSignature(signatureId, payload = {}) {
  const response = await apiClient.post(`/signatures/${signatureId}/reject`, payload);
  return response.data;
}

export async function revokeSignature(signatureId, payload = {}) {
  const response = await apiClient.post(`/signatures/${signatureId}/revoke`, payload);
  return response.data;
}

