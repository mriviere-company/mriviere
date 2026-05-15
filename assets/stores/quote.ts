import { defineStore } from 'pinia';
import { apiClient, call } from '@/api/client';
import { i18n } from '@/i18n';
import type { PackageSlug, QuoteCreatedResponse, QuotePayload } from '@/types';

interface State {
  selectedPackage: PackageSlug | null;
  draft: Partial<QuotePayload>;
  submitting: boolean;
  result: QuoteCreatedResponse | null;
  error: string | null;
}

export const useQuoteStore = defineStore('quote', {
  state: (): State => ({
    selectedPackage: null,
    draft: {},
    submitting: false,
    result: null,
    error: null,
  }),
  actions: {
    selectPackage(slug: PackageSlug) {
      this.selectedPackage = slug;
      this.draft.package = slug;
    },
    update(patch: Partial<QuotePayload>) {
      this.draft = { ...this.draft, ...patch };
    },
    reset() {
      this.draft = {};
      this.selectedPackage = null;
      this.result = null;
      this.error = null;
    },
    async submit(): Promise<{ ok: boolean; checkoutUrl?: string; error?: string }> {
      if (!this.draft.package || !this.draft.clientName || !this.draft.clientEmail) {
        return { ok: false, error: 'Champs obligatoires manquants.' };
      }
      this.submitting = true;
      this.error = null;
      const locale = i18n.global.locale.value === 'en' ? 'en' : 'fr';
      const payload = { ...this.draft, locale };
      const res = await call<QuoteCreatedResponse>(() => apiClient.post('/quotes', payload));
      this.submitting = false;
      if (res.ok) {
        this.result = res.data;
        return { ok: true, checkoutUrl: res.data.checkoutUrl };
      }
      this.error = res.error;
      return { ok: false, error: res.error };
    },
  },
});
