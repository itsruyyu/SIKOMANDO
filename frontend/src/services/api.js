import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 30000,
});

// Request Interceptor: Attach Sanctum Bearer Token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    // If data is FormData, let the browser set multipart/form-data with boundary
    if (config.data instanceof FormData) {
      delete config.headers['Content-Type'];
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response Interceptor: Normalize data and handle standard status codes
api.interceptors.response.use(
  (response) => {
    const payload = response.data;
    
    // Normalize Laravel pagination when wrapped in ApiResponse::success({ data: [...], meta: {...} })
    if (
      payload &&
      typeof payload === 'object' &&
      payload.data &&
      typeof payload.data === 'object' &&
      Array.isArray(payload.data.data) &&
      payload.data.meta
    ) {
      return {
        ...payload,
        data: payload.data.data,
        meta: payload.data.meta,
      };
    }
    
    return payload;
  },
  (error) => {
    const response = error.response;
    
    // 401 Unauthorized handling
    if (response?.status === 401) {
      localStorage.removeItem('token');
      sessionStorage.removeItem('token');
      localStorage.removeItem('user');
      
      const currentPath = window.location.pathname;
      const isPublicPage = currentPath === '/' || 
                           currentPath.startsWith('/programs') || 
                           currentPath.startsWith('/tracker') || 
                           currentPath.startsWith('/statistics') || 
                           currentPath.startsWith('/transparency') || 
                           currentPath.startsWith('/verify') || 
                           currentPath === '/login';
      
      if (!isPublicPage) {
        window.location.href = `/login?redirect=${encodeURIComponent(currentPath)}`;
      }
    }

    // Extract human-friendly error details
    let errorMessage = 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.';
    let validationErrors = null;

    if (response?.data) {
      if (response.data.message) {
        errorMessage = response.data.message;
      }
      if (response.data.errors) {
        validationErrors = response.data.errors;
        // If there's a specific validation message list, pick the first one as primary message
        const firstField = Object.keys(response.data.errors)[0];
        if (firstField && Array.isArray(response.data.errors[firstField]) && response.data.errors[firstField][0]) {
          errorMessage = response.data.errors[firstField][0];
        }
      }
    } else if (error.message) {
      errorMessage = error.message;
    }

    const enhancedError = new Error(errorMessage);
    enhancedError.status = response?.status || 500;
    enhancedError.errors = validationErrors;
    enhancedError.originalResponse = response?.data;

    return Promise.reject(enhancedError);
  }
);

export default api;

