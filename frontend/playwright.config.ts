import { defineConfig, devices } from "@playwright/test";

/**
 * `PLAYWRIGHT_CHROMIUM_PATH` lets a machine point at a Chromium it already has
 * — CI images and sandboxes usually ship one, and downloading a second copy per
 * run is wasted minutes. Left unset, Playwright uses its own download.
 */
const executablePath = process.env.PLAYWRIGHT_CHROMIUM_PATH;

export default defineConfig({
  testDir: "./e2e",
  fullyParallel: false,
  workers: 1,
  reporter: "list",
  use: {
    baseURL: process.env.E2E_BASE_URL ?? "http://localhost:3000",
    trace: "off",
    ...(executablePath ? { launchOptions: { executablePath } } : {}),
  },
  projects: [
    {
      // Mobile-first is the requirement, so the end-to-end run is a phone.
      name: "mobile",
      use: { ...devices["Pixel 7"] },
    },
  ],
});
