import apiClient from './client';

export async function getAssignedSurveys() {
  const response = await apiClient.get('/field-surveys/assigned');
  return response.data;
}

export async function getProposalFieldSurveys(proposalId) {
  const response = await apiClient.get(`/proposals/${proposalId}/field-surveys`);
  return response.data;
}

export async function createFieldSurvey(proposalId, payload) {
  const response = await apiClient.post(`/proposals/${proposalId}/field-surveys`, payload);
  return response.data;
}

export async function getFieldSurvey(proposalId, surveyId) {
  const response = await apiClient.get(`/proposals/${proposalId}/field-surveys/${surveyId}`);
  return response.data;
}

export async function updateSurveySchedule(proposalId, surveyId, payload) {
  const response = await apiClient.patch(
    `/proposals/${proposalId}/field-surveys/${surveyId}/schedule`,
    payload
  );
  return response.data;
}

export async function startFieldSurvey(proposalId, surveyId) {
  const response = await apiClient.post(`/proposals/${proposalId}/field-surveys/${surveyId}/start`);
  return response.data;
}

export async function updateSurveyItem(proposalId, surveyId, itemId, payload) {
  const response = await apiClient.patch(
    `/proposals/${proposalId}/field-surveys/${surveyId}/items/${itemId}`,
    payload
  );
  return response.data;
}

export async function storeSurveyFinding(proposalId, surveyId, payload) {
  const response = await apiClient.post(
    `/proposals/${proposalId}/field-surveys/${surveyId}/findings`,
    payload
  );
  return response.data;
}

export async function storeSurveyDocument(proposalId, surveyId, formData) {
  const response = await apiClient.post(
    `/proposals/${proposalId}/field-surveys/${surveyId}/documents`,
    formData,
    { headers: { 'Content-Type': 'multipart/form-data' } }
  );
  return response.data;
}

export async function fillSurveyResult(proposalId, surveyId, payload) {
  const response = await apiClient.patch(
    `/proposals/${proposalId}/field-surveys/${surveyId}/result`,
    payload
  );
  return response.data;
}

export async function submitSurvey(proposalId, surveyId) {
  const response = await apiClient.post(`/proposals/${proposalId}/field-surveys/${surveyId}/submit`);
  return response.data;
}

export async function completeSurvey(proposalId, surveyId) {
  const response = await apiClient.post(
    `/proposals/${proposalId}/field-surveys/${surveyId}/complete`
  );
  return response.data;
}

export function getFieldSurveyPdfUrl(proposalId, surveyId) {
  const baseURL = apiClient.defaults.baseURL || 'http://127.0.0.1:8000/api/v1';
  return `${baseURL}/pdf/proposals/${proposalId}/field-surveys/${surveyId}`;
}

