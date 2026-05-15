import { test, expect } from '@playwright/test';

test('FR home renders the hero title', async ({ page, context }) => {
  await context.addInitScript(() => localStorage.setItem('mriviere.locale', 'fr'));
  await page.goto('/');
  await expect(page.locator('h1')).toContainText(/site à votre image/i);
});

test('Pricing page renders the three packages', async ({ page, context }) => {
  await context.addInitScript(() => localStorage.setItem('mriviere.locale', 'fr'));
  await page.goto('/forfaits');
  await expect(page.getByRole('heading', { name: 'STARTER' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'STANDARD' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'PREMIUM' })).toBeVisible();
});

test('EN home variant forces english content', async ({ page }) => {
  await page.goto('/en');
  await expect(page.locator('h1')).toContainText(/one-of-a-kind site/i);
});

test('Pricing CTA passes the package slug to the quote form', async ({ page, context }) => {
  await context.addInitScript(() => localStorage.setItem('mriviere.locale', 'fr'));
  await page.goto('/forfaits');
  await page.getByRole('link', { name: /Choisir STANDARD/i }).first().click();
  await page.waitForURL(/\/devis\?pkg=standard/);
  await expect(page.locator('h1')).toBeVisible();
});
