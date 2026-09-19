import apiClient from './client';

export async function getNotifications(params = {}) {
  const response = await apiClient.get('/notifications', { params });
  return response.data;
}

export async function getUnreadNotificationsCount() {
  const response = await apiClient.get('/notifications/unread-count');
  return response.data;
}

export async function markAllNotificationsAsRead() {
  const response = await apiClient.post('/notifications/mark-all-as-read');
  return response.data;
}

export async function markNotificationAsRead(id) {
  const response = await apiClient.post(`/notifications/${id}/read`);
  return response.data;
}

