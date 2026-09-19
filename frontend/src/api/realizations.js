import apiClient from './client';

// Realization Packages
export async function getProposalRealizations(proposalId) {
  const response = await apiClient.get(`/proposals/${proposalId}/realizations`);
  return response.data;
}

export async function createRealizationPackage(proposalId, payload) {
  const response = await apiClient.post(`/proposals/${proposalId}/realizations`, payload);
  return response.data;
}

export async function getRealizationPackage(packageId) {
  const response = await apiClient.get(`/realization-packages/${packageId}`);
  return response.data;
}

export async function updateRealizationPackage(packageId, payload) {
  const response = await apiClient.put(`/realization-packages/${packageId}`, payload);
  return response.data;
}

export async function submitRealizationPackage(packageId) {
  const response = await apiClient.post(`/realization-packages/${packageId}/submit`);
  return response.data;
}

export async function verifyRealizationPackage(packageId, payload = {}) {
  const response = await apiClient.post(`/realization-packages/${packageId}/verify`, payload);
  return response.data;
}

export async function addRealizationItem(packageId, payload) {
  const response = await apiClient.post(`/realization-packages/${packageId}/items`, payload);
  return response.data;
}

// Realization Items
export async function getRealizationItem(itemId) {
  const response = await apiClient.get(`/realization-items/${itemId}`);
  return response.data;
}

export async function updateRealizationItem(itemId, payload) {
  const response = await apiClient.put(`/realization-items/${itemId}`, payload);
  return response.data;
}

export async function inspectRealizationItem(itemId, payload) {
  const response = await apiClient.post(`/realization-items/${itemId}/inspect`, payload);
  return response.data;
}

// Receipts
export async function getProposalReceipts(proposalId) {
  const response = await apiClient.get(`/proposals/${proposalId}/receipts`);
  return response.data;
}

export async function createReceipt(proposalId, payload) {
  const response = await apiClient.post(`/proposals/${proposalId}/receipts`, payload);
  return response.data;
}

export async function getReceipt(receiptId) {
  const response = await apiClient.get(`/receipts/${receiptId}`);
  return response.data;
}

export async function updateReceipt(receiptId, payload) {
  const response = await apiClient.put(`/receipts/${receiptId}`, payload);
  return response.data;
}

export async function verifyReceipt(receiptId, payload = {}) {
  const response = await apiClient.post(`/receipts/${receiptId}/verify`, payload);
  return response.data;
}

export async function cancelReceipt(receiptId, payload = {}) {
  const response = await apiClient.post(`/receipts/${receiptId}/cancel`, payload);
  return response.data;
}

// Handovers (BAST)
export async function getPackageHandovers(packageId) {
  const response = await apiClient.get(`/realization-packages/${packageId}/handovers`);
  return response.data;
}

export async function createHandover(packageId, payload) {
  const response = await apiClient.post(`/realization-packages/${packageId}/handovers`, payload);
  return response.data;
}

export async function getHandover(handoverId) {
  const response = await apiClient.get(`/handovers/${handoverId}`);
  return response.data;
}

export async function submitHandover(handoverId) {
  const response = await apiClient.post(`/handovers/${handoverId}/submit`);
  return response.data;
}

export async function completeHandover(handoverId, payload = {}) {
  const response = await apiClient.post(`/handovers/${handoverId}/complete`, payload);
  return response.data;
}

export async function cancelHandover(handoverId, payload = {}) {
  const response = await apiClient.post(`/handovers/${handoverId}/cancel`, payload);
  return response.data;
}

