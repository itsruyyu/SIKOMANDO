import apiClient from './client';

export async function getRankingPreview(grantProgramId) {
  const response = await apiClient.get(`/grant-programs/${grantProgramId}/rankings/preview`);
  return response.data;
}

export async function generateRankings(grantProgramId, payload = {}) {
  const response = await apiClient.post(
    `/grant-programs/${grantProgramId}/rankings/generate`,
    payload
  );
  return response.data;
}

export async function getRankings(grantProgramId) {
  const response = await apiClient.get(`/grant-programs/${grantProgramId}/rankings`);
  return response.data;
}

export async function getRankingDetail(grantProgramId, rankingId) {
  const response = await apiClient.get(`/grant-programs/${grantProgramId}/rankings/${rankingId}`);
  return response.data;
}

export async function reviewRanking(grantProgramId, rankingId, payload = {}) {
  const response = await apiClient.post(
    `/grant-programs/${grantProgramId}/rankings/${rankingId}/review`,
    payload
  );
  return response.data;
}

export async function finalizeRanking(grantProgramId, rankingId, payload = {}) {
  const response = await apiClient.post(
    `/grant-programs/${grantProgramId}/rankings/${rankingId}/finalize`,
    payload
  );
  return response.data;
}

export async function regenerateRanking(grantProgramId, rankingId) {
  const response = await apiClient.post(
    `/grant-programs/${grantProgramId}/rankings/${rankingId}/regenerate`
  );
  return response.data;
}

export async function getProposalRecommendation(proposalId) {
  const response = await apiClient.get(`/proposals/${proposalId}/recommendation`);
  return response.data;
}

