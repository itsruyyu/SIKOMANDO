import apiClient from './client';

export async function getProposalMonitoringRecords(proposalId) {
  const response = await apiClient.get(`/proposals/${proposalId}/monitoring-records`);
  return response.data;
}

export async function createProposalMonitoringRecord(proposalId, payload) {
  const response = await apiClient.post(`/proposals/${proposalId}/monitoring-records`, payload);
  return response.data;
}

export async function getMonitoringRecord(recordId) {
  const response = await apiClient.get(`/monitoring-records/${recordId}`);
  return response.data;
}

export async function checkMonitoringItem(recordId, payload) {
  const response = await apiClient.post(
    `/monitoring-records/${recordId}/check-item`,
    payload
  );
  return response.data;
}

export async function completeMonitoringRecord(recordId, payload = {}) {
  const response = await apiClient.post(
    `/monitoring-records/${recordId}/complete`,
    payload
  );
  return response.data;
}

