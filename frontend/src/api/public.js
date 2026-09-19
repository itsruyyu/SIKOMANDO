import apiClient from './client';

export async function getPublicGrantPrograms(params = {}) {
  const response = await apiClient.get('/public/grant-programs', { params });
  return response.data;
}

export async function getPublicGrantProgram(id) {
  const response = await apiClient.get(`/public/grant-programs/${id}`);
  return response.data;
}

export async function getPublicGrantProgramTimeline(id) {
  const response = await apiClient.get(`/public/grant-programs/${id}/timeline`);
  return response.data;
}

export async function getPublicGrantProgramDocuments(id) {
  const response = await apiClient.get(`/public/grant-programs/${id}/documents`);
  return response.data;
}

export async function getPublicAnnouncements(params = {}) {
  const response = await apiClient.get('/public/announcements', { params });
  return response.data;
}

export async function getPublicAnnouncement(id) {
  const response = await apiClient.get(`/public/announcements/${id}`);
  return response.data;
}

export async function getPublicStatistics() {
  const response = await apiClient.get('/public/statistics');
  return response.data;
}

export async function getPublicStatisticsSummary() {
  const response = await apiClient.get('/public/statistics/summary');
  return response.data;
}

export async function getPublicTransparency(params = {}) {
  const response = await apiClient.get('/public/transparency', { params });
  return response.data;
}

export async function getPublicTransparencyProgram(id) {
  const response = await apiClient.get(`/public/transparency/${id}`);
  return response.data;
}

export async function verifyPublicQr(token) {
  const response = await apiClient.get(`/public/verify/${token}`);
  return response.data;
}

