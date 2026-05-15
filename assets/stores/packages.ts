import { defineStore } from 'pinia';
import { apiClient, call } from '@/api/client';
import type { PackageDto } from '@/types';

interface State {
  items: PackageDto[];
  loaded: boolean;
  loading: boolean;
}

export const usePackagesStore = defineStore('packages', {
  state: (): State => ({ items: [], loaded: false, loading: false }),
  getters: {
    bySlug: (state) => (slug: string) => state.items.find((p) => p.slug === slug),
  },
  actions: {
    async ensureLoaded() {
      if (this.loaded || this.loading) return;
      this.loading = true;
      const res = await call<{ packages: PackageDto[] }>(() => apiClient.get('/packages'));
      this.loading = false;
      if (res.ok) {
        this.items = res.data.packages;
        this.loaded = true;
      }
    },
  },
});
