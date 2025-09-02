import { ref } from 'vue';

export function useApi() {
  const loading = ref(false);
  const error = ref<string | null>(null);

  const getCsrfToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return token || '';
  };

  const apiCall = async (url: string, options: RequestInit = {}) => {
    loading.value = true;
    error.value = null;

    try {
      const defaultOptions: RequestInit = {
        credentials: 'same-origin', // Include cookies for session authentication
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...options.headers,
        },
      };

      // Add CSRF token for non-GET requests
      if (options.method && options.method !== 'GET') {
        defaultOptions.headers = {
          ...defaultOptions.headers,
          'X-CSRF-TOKEN': getCsrfToken(),
        };
      }

      const response = await fetch(url, {
        ...defaultOptions,
        ...options,
        headers: {
          ...defaultOptions.headers,
          ...options.headers,
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      return data;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'An error occurred';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const get = (url: string, options: RequestInit = {}) => {
    return apiCall(url, { ...options, method: 'GET' });
  };

  const post = (url: string, data?: any, options: RequestInit = {}) => {
    return apiCall(url, {
      ...options,
      method: 'POST',
      body: data ? JSON.stringify(data) : undefined,
    });
  };

  const put = (url: string, data?: any, options: RequestInit = {}) => {
    return apiCall(url, {
      ...options,
      method: 'PUT',
      body: data ? JSON.stringify(data) : undefined,
    });
  };

  const del = (url: string, options: RequestInit = {}) => {
    return apiCall(url, { ...options, method: 'DELETE' });
  };

  return {
    loading,
    error,
    apiCall,
    get,
    post,
    put,
    delete: del,
  };
}
