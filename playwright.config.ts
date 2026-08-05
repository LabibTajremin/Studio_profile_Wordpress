import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/E2E',
  fullyParallel: true,
  retries: process.env.CI ? 2 : 0,
  reporter: 'html',
  use: {
    baseURL: process.env.MK_BASE_URL ?? 'http://localhost:8889',
    trace: 'on-first-retry',
  },
  projects: [
    { name: 'chromium-desktop', use: { ...devices['Desktop Chrome'] } },
    { name: 'mobile-375', use: { ...devices['iPhone SE'] } },
  ],
});
