import { expect, test, type Page } from "@playwright/test";

/**
 * Register and land on the dashboard.
 *
 * Waiting for the dashboard is the point, not politeness: navigating away
 * while the registration request is still in flight cancels it, and the next
 * page then quietly redirects to sign-in.
 */
async function registerAndSignIn(page: Page, name: string): Promise<void> {
  const email = `${name.toLowerCase().replace(/\s+/g, "-")}-${Date.now()}@example.test`;

  await page.goto("/register");
  await page.getByLabel("Name").fill(name);
  await page.getByLabel("Email").fill(email);
  await page.getByLabel("Password", { exact: true }).fill("correct-horse-battery-staple");
  await page.getByLabel("Confirm password").fill("correct-horse-battery-staple");
  await page.getByRole("button", { name: "Create account" }).click();

  await expect(page.getByRole("heading", { name: "Today" })).toBeVisible();
}

/**
 * The whole loop, in a real browser: register, read the shared library, build a
 * program, and see the prescription come back in order.
 *
 * This is the test that proves the parts fit together — CORS, the CSRF cookie,
 * the session cookie surviving a cross-origin request, and the API contract
 * matching what the screens expect. Unit tests cannot show any of that.
 */
test("a new user can register, browse the library, and build a program", async ({ page }) => {
  await registerAndSignIn(page, "Walkthrough User");

  await expect(page.getByText("Welcome back, Walkthrough.")).toBeVisible();

  await page.getByRole("link", { name: "Exercises" }).click();
  await expect(page.getByText("26 in your library")).toBeVisible();

  // The distinction the coaching engine depends on, visible on the card.
  await page.getByLabel("Search exercises").fill("Assisted");
  await expect(page.getByText("Progresses by reducing assistance")).toBeVisible();

  await page.getByRole("link", { name: "Programs" }).click();
  await page.getByRole("button", { name: "Create one" }).click();
  await page.getByLabel("Name").fill("Upper A");
  await page.getByLabel("Description").fill("Upper body, strength emphasis");
  await page.getByRole("button", { name: "Create", exact: true }).click();

  await page.getByRole("link", { name: "Upper A" }).click();
  await expect(page.getByRole("heading", { name: "Upper A" })).toBeVisible();

  await page.getByRole("button", { name: "Add the first" }).click();
  await page.getByLabel("Exercise").selectOption({ label: "Barbell Bench Press" });
  await page.getByLabel("Sets").fill("5");
  await page.getByLabel("Min reps").fill("3");
  await page.getByLabel("Max reps").fill("5");
  await page.getByRole("button", { name: "Add", exact: true }).click();

  await expect(page.getByText("Barbell Bench Press")).toBeVisible();
  await expect(page.getByText("5 × 3–5 @ RIR 2")).toBeVisible();
});

/** The server is authoritative, and its refusal has to reach the screen. */
test("a rejected prescription shows the API's own message", async ({ page }) => {
  await registerAndSignIn(page, "Validation User");

  await page.getByRole("link", { name: "Programs" }).click();
  await page.getByRole("button", { name: "Create one" }).click();
  await page.getByLabel("Name").fill("Range Check");
  await page.getByRole("button", { name: "Create", exact: true }).click();

  await page.getByRole("link", { name: "Range Check" }).click();
  await page.getByRole("button", { name: "Add the first" }).click();
  await page.getByLabel("Exercise").selectOption({ label: "Barbell Back Squat" });
  await page.getByLabel("Min reps").fill("12");
  await page.getByLabel("Max reps").fill("8");
  await page.getByRole("button", { name: "Add", exact: true }).click();

  await expect(page.getByText("The minimum must not exceed the maximum.")).toBeVisible();
});
