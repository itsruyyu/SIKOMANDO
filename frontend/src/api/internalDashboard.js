import apiClient from './client';

export async function getWorkloadSummary() {
  const response = await apiClient.get('/internal/dashboard/workload');
  return response.data;
}

export async function getTasksByStatus() {
  const response = await apiClient.get('/internal/dashboard/tasks');
  return response.data;
}

export async function getAssignmentHistory() {
  const response = await apiClient.get('/internal/dashboard/assignment-history');
  return response.data;
}

