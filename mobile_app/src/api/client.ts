import axios from 'axios';
import { tokenStore } from './token';

export const API_URL =
  process.env.EXPO_PUBLIC_API_URL ??
  'http://10.0.2.2:8084/Rst/RstInternalApp/public/api/mobile';

export const api = axios.create({
  baseURL: API_URL,
  timeout: 20000,
  headers: { Accept: 'application/json' },
});

let unauthorizedHandler: (() => void) | null = null;

export const setUnauthorizedHandler = (handler: () => void) => {
  unauthorizedHandler = handler;
};

api.interceptors.request.use(async (config) => {
  const token = await tokenStore.get();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error?.response?.status === 401) {
      await tokenStore.clear();
      unauthorizedHandler?.();
    }
    return Promise.reject(error);
  },
);

export const apiMessage = (error: unknown, fallback = 'Terjadi kesalahan.'): string => {
  if (axios.isAxiosError(error)) {
    if (!error.response) {
      return 'Tidak dapat terhubung ke server API. Periksa koneksi atau EXPO_PUBLIC_API_URL.';
    }

    const data = error.response.data as {
      message?: string;
      errors?: Record<string, string[]>;
    };
    if (data?.errors) {
      const first = Object.values(data.errors)[0];
      if (first?.length) return first[0];
    }
    if (data?.message) return data.message;
  }
  return fallback;
};
