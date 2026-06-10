import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useQuoteStore } from '../quote';

vi.mock('@/api/client', () => ({
  apiClient: { post: vi.fn() },
  call: vi.fn(),
}));

import { call } from '@/api/client';
import { i18n } from '@/i18n';

describe('useQuoteStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('selectPackage sets both selectedPackage and draft.package', () => {
    const store = useQuoteStore();
    store.selectPackage('standard');
    expect(store.selectedPackage).toBe('standard');
    expect(store.draft.package).toBe('standard');
  });

  it('rejects submit when required fields are missing', async () => {
    const store = useQuoteStore();
    const res = await store.submit();
    expect(res.ok).toBe(false);
    expect(res.error).toBe(i18n.global.t('quote.errorMissingFields'));
  });

  it('returns checkoutUrl on successful submit', async () => {
    vi.mocked(call).mockResolvedValueOnce({
      ok: true,
      data: { id: 'abc', checkoutUrl: 'https://checkout.example/abc', totalOneShot: 39900, totalMonthly: 2900 },
    });

    const store = useQuoteStore();
    store.update({
      package: 'starter',
      clientName: 'Foo',
      clientEmail: 'foo@example.com',
      clientPhone: '0612345678',
      projectDescription: 'Test',
    });
    const res = await store.submit();
    expect(res.ok).toBe(true);
    expect(res.checkoutUrl).toBe('https://checkout.example/abc');
  });

  it('reset clears draft and result', () => {
    const store = useQuoteStore();
    store.update({ clientName: 'X', package: 'starter' });
    store.reset();
    expect(store.draft).toEqual({});
    expect(store.selectedPackage).toBe(null);
    expect(store.result).toBe(null);
  });
});
