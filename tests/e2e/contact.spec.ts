import { test, expect } from '@playwright/test';

test.describe('Contact page (FR default)', () => {
  test.beforeEach(async ({ context }) => {
    await context.addInitScript(() => localStorage.setItem('mriviere.locale', 'fr'));
  });

  test('submits a valid message and shows the success state', async ({ page }) => {
    await page.goto('/contact');

    await page.getByLabel('Votre nom').fill('Test E2E');
    await page.getByLabel('Courriel').fill('e2e@example.com');
    await page.getByLabel('Votre message').fill('Bonjour, ceci est un test E2E automatique du formulaire de contact.');

    const requestPromise = page.waitForResponse(
      (res) => res.url().includes('/api/contact') && res.request().method() === 'POST',
    );
    await page.getByRole('button', { name: /Envoyer le message/i }).click();

    const response = await requestPromise;
    expect(response.status()).toBe(200);

    await expect(page.getByText(/Message envoyé/i)).toBeVisible({ timeout: 5000 });
  });

  test('blocks honeypot-stuffed submissions', async ({ page }) => {
    await page.goto('/contact');
    await page.getByLabel('Votre nom').fill('Spammer');
    await page.getByLabel('Courriel').fill('spam@example.com');
    await page.getByLabel('Votre message').fill('Suspicious payload — spam attempt for testing.');
    await page.evaluate(() => {
      const honey = document.querySelector('.contact__honey input') as HTMLInputElement | null;
      if (!honey) throw new Error('honeypot input not found');
      const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value')?.set;
      setter?.call(honey, 'http://spam.invalid');
      honey.dispatchEvent(new Event('input', { bubbles: true }));
    });

    const requestPromise = page.waitForResponse((res) => res.url().includes('/api/contact'));
    await page.getByRole('button', { name: /Envoyer le message/i }).click();
    const response = await requestPromise;
    expect([400, 422, 429]).toContain(response.status());
  });
});

test.describe('Locale switcher', () => {
  test('toggles UI text from FR to EN without navigating', async ({ page, context }) => {
    await context.addInitScript(() => localStorage.setItem('mriviere.locale', 'fr'));
    await page.goto('/contact');
    await expect(page.getByRole('heading', { level: 1 })).toContainText(/projet/i);

    await page.getByRole('button', { name: 'Langue' }).click();
    await expect(page.getByRole('heading', { level: 1 })).toContainText(/project/i);
    expect(page.url()).toContain('/contact');
  });
});
