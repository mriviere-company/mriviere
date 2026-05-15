import axios, { type AxiosError, type AxiosInstance } from 'axios';

function readCsrf(): string {
  return document.getElementById('app')?.dataset.csrf ?? '';
}

export const apiClient: AxiosInstance = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  withCredentials: true,
});

apiClient.interceptors.request.use((config) => {
  if (config.method && config.method.toLowerCase() !== 'get') {
    config.headers.set('X-CSRF-Token', readCsrf());
  }
  return config;
});

export type ApiResult<T> = { ok: true; data: T } | { ok: false; error: string; status?: number };

export async function call<T>(fn: () => Promise<{ data: T }>): Promise<ApiResult<T>> {
  try {
    const res = await fn();
    return { ok: true, data: res.data };
  } catch (err) {
    const ax = err as AxiosError<{ error?: string; message?: string }>;
    return {
      ok: false,
      status: ax.response?.status,
      error: ax.response?.data?.error ?? ax.response?.data?.message ?? ax.message ?? 'Une erreur est survenue.',
    };
  }
}
