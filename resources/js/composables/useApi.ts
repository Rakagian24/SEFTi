import { ref } from "vue";

export function useApi() {
  const loading = ref(false);
  const error = ref<string | null>(null);

  const refreshCsrfToken = async () => {
    try {
      // endpoint Laravel yang kasih balik token baru
      const res = await fetch("/refresh-csrf", {
        method: "GET",
        credentials: "same-origin",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (res.ok) {
        const data = await res.json();
        if (data.token) {
          document
            .querySelector('meta[name="csrf-token"]')
            ?.setAttribute("content", data.token);
          (window as any).__csrf = data.token;
          return data.token;
        }
      }
    } catch (e) {
      console.error("Failed to refresh CSRF:", e);
    }
    return "";
  };

  const getCsrfToken = () => {
    return (
      document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
      (window as any).__csrf ||
      ""
    );
  };

  const apiCall = async (url: string, options: RequestInit = {}) => {
    loading.value = true;
    error.value = null;

    try {
      const defaultOptions: RequestInit = {
        credentials: "same-origin", // penting biar cookie session ikut
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
          ...options.headers,
        },
      };

      // inject CSRF token kalau bukan GET
      if (options.method && options.method !== "GET") {
        defaultOptions.headers = {
          ...defaultOptions.headers,
          "X-CSRF-TOKEN": getCsrfToken(),
        };
      }

      let response = await fetch(url, {
        ...defaultOptions,
        ...options,
        headers: {
          ...defaultOptions.headers,
          ...options.headers,
        },
      });

      // kalau token expired -> refresh token -> retry sekali
      if (response.status === 419) {
        const newToken = await refreshCsrfToken();
        if (newToken) {
          response = await fetch(url, {
            ...defaultOptions,
            ...options,
            headers: {
              ...defaultOptions.headers,
              ...options.headers,
              "X-CSRF-TOKEN": newToken,
            },
          });
        }
      }

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      return await response.json();
    } catch (err) {
      error.value = err instanceof Error ? err.message : "An error occurred";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const get = (url: string, options: RequestInit = {}) => {
    return apiCall(url, { ...options, method: "GET" });
  };

  const post = (url: string, data?: any, options: RequestInit = {}) => {
    return apiCall(url, {
      ...options,
      method: "POST",
      body: data ? JSON.stringify(data) : undefined,
    });
  };

  const put = (url: string, data?: any, options: RequestInit = {}) => {
    return apiCall(url, {
      ...options,
      method: "PUT",
      body: data ? JSON.stringify(data) : undefined,
    });
  };

  const del = (url: string, options: RequestInit = {}) => {
    return apiCall(url, { ...options, method: "DELETE" });
  };

  return {
    loading,
    error,
    apiCall,
    get,
    post,
    put,
    delete: del,
    refreshCsrfToken,
  };
}
