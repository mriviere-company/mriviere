import { afterEach, vi } from 'vitest';

afterEach(() => {
  vi.clearAllMocks();
  document.body.innerHTML = '';
});
