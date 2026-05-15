import { defineStore } from 'pinia';
import { apiClient, call } from '@/api/client';
import type { AdminUserDto } from '@/types';

interface State {
  user: AdminUserDto | null;
  checking: boolean;
}

export const useAuthStore = defineStore('auth', {
  state: (): State => ({ user: null, checking: false }),
  actions: {
    async fetchMe() {
      this.checking = true;
      const res = await call<AdminUserDto>(() => apiClient.get('/admin/me'));
      this.checking = false;
      this.user = res.ok ? res.data : null;
    },
    async login(email: string, password: string): Promise<{ ok: boolean; error?: string }> {
      const res = await call<AdminUserDto>(() => apiClient.post('/admin/login', { email, password }));
      if (res.ok) {
        this.user = res.data;
        return { ok: true };
      }
      return { ok: false, error: res.error };
    },
    async logout() {
      await call<{ ok: true }>(() => apiClient.post('/admin/logout', {}));
      this.user = null;
    },
  },
});
