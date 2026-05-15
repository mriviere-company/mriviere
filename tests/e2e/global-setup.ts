import { execSync } from 'node:child_process';

export default async function globalSetup() {
  try {
    execSync('php bin/console cache:pool:clear cache.rate_limiter --env=prod', { stdio: 'pipe' });
  } catch (e) {
    console.warn('global-setup: cache:pool:clear failed (non-fatal)', e);
  }
}
