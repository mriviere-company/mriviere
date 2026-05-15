import { test, expect } from '@playwright/test';

test.describe('Admin auth', () => {
  test('rejects bad credentials', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByLabel('Email').fill('nope@example.com');
    await page.getByLabel('Mot de passe').fill('wrong');

    const responsePromise = page.waitForResponse((r) => r.url().includes('/api/admin/login'));
    await page.getByRole('button', { name: /Se connecter/i }).click();
    const res = await responsePromise;
    expect(res.status()).toBe(401);
    await expect(page.getByText(/Identifiants/i)).toBeVisible();
  });

  test('redirects unauthenticated requests to /admin/login', async ({ page }) => {
    await page.goto('/admin/dashboard');
    await page.waitForURL(/\/admin\/login/);
    expect(page.url()).toContain('/admin/login');
  });
});
