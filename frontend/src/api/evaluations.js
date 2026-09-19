import apiClient from './client';

export async function getEvaluations(proposalId) {
  const response = await apiClient.get(`/proposals/${proposalId}/evaluations`);
  return response.data;
}

export async function startEvaluation(proposalId, payload = {}) {
  const response = await apiClient.post(`/proposals/${proposalId}/evaluations`, payload);
  return response.data;
}

export async function getEvaluation(proposalId, evaluationId) {
  const response = await apiClient.get(`/proposals/${proposalId}/evaluations/${evaluationId}`);
  return response.data;
}

export async function updateEvaluationItem(proposalId, evaluationId, itemId, payload) {
  const response = await apiClient.patch(
    `/proposals/${proposalId}/evaluations/${evaluationId}/items/${itemId}`,
    payload
  );
  return response.data;
}

export async function completeEvaluation(proposalId, evaluationId, payload) {
  const response = await apiClient.post(
    `/proposals/${proposalId}/evaluations/${evaluationId}/complete`,
    payload
  );
  return response.data;
}

export async function deleteEvaluation(proposalId, evaluationId) {
  const response = await apiClient.delete(`/proposals/${proposalId}/evaluations/${evaluationId}`);
  return response.data;
}

export function getEvaluationPdfUrl(proposalId, evaluationId) {
  const baseURL = apiClient.defaults.baseURL || 'http://127.0.0.1:8000/api/v1';
  return `${baseURL}/pdf/proposals/${proposalId}/evaluations/${evaluationId}`;
}

