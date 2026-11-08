import { test, expect } from '@playwright/test';

/**
 * The mobile menu must be closable.
 *
 * It shipped with no way out at all: the close button existed in the markup
 * and the stylesheet but was never wired up in JS, and the overlay sits above
 * the header (z-index 200 vs 100) so the hamburger underneath it could not be
 * tapped either. Separately, the overlay was a child of the header, whose
 * backdrop-filter makes it the containing block for position:fixed
 * descendants - so the "full screen" overlay was clamped to the header's 65px
 * box and the nav links rendered above the top of the screen.
 *
 * CI does not stand up a WordPress server (see .github/workflows/ci.yml), so
 * these skip unless MK_BASE_URL points at a reachable site.
 */

const BASE = process.env.MK_BASE_URL;

test.describe('mobile menu', () => {
  test.skip(!BASE, 'Set MK_BASE_URL to run these against a real site.');

  test.use({ viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true });

  const open = async (page: import('@playwright/test').Page) => {
    await page.goto('/');
    await page.locator('.mk-menu-toggle').tap();
    await expect(page.locator('#mk-mobile-menu')).toBeVisible();
  };

  test('the overlay fills the viewport and its links are on screen', async ({ page }) => {
    await open(page);

    const vp = page.viewportSize()!;
    const box = await page.locator('#mk-mobile-menu').boundingBox();
    expect(box!.width).toBeGreaterThanOrEqual(vp.width - 1);
    expect(box!.height).toBeGreaterThanOrEqual(vp.height - 1);

    for (const link of await page.locator('#mk-mobile-menu a').all()) {
      const r = await link.boundingBox();
      expect(r!.y, 'nav link above the top of the screen').toBeGreaterThanOrEqual(0);
      expect(r!.y + r!.height, 'nav link below the bottom of the screen').toBeLessThanOrEqual(vp.height);
    }
  });

  test('the close button closes it', async ({ page }) => {
    await open(page);
    await page.locator('.mk-mobile-menu__close').tap();
    await expect(page.locator('#mk-mobile-menu')).toBeHidden();
    await expect(page.locator('.mk-menu-toggle')).toHaveAttribute('aria-expanded', 'false');
  });

  test('tapping the same spot twice opens then closes it', async ({ page }) => {
    await page.goto('/');
    const box = (await page.locator('.mk-menu-toggle').boundingBox())!;
    const [x, y] = [box.x + box.width / 2, box.y + box.height / 2];

    await page.touchscreen.tap(x, y);
    await expect(page.locator('#mk-mobile-menu')).toBeVisible();

    // The close button lands exactly where the hamburger was, so the same
    // coordinates must toggle it back shut.
    await page.touchscreen.tap(x, y);
    await expect(page.locator('#mk-mobile-menu')).toBeHidden();
  });

  test('Escape closes it', async ({ page }) => {
    await open(page);
    await page.keyboard.press('Escape');
    await expect(page.locator('#mk-mobile-menu')).toBeHidden();
  });

  test('following a nav link closes it', async ({ page }) => {
    await open(page);
    await page.locator('#mk-mobile-menu a').first().tap();
    await page.waitForLoadState('domcontentloaded');
    await expect(page.locator('#mk-mobile-menu')).toBeHidden();
    await expect(page.locator('body')).not.toHaveClass(/mk-menu-open/);
  });

  test('the page behind does not scroll while it is open, and is released after', async ({ page }) => {
    await open(page);
    await expect(page.locator('body')).toHaveClass(/mk-menu-open/);
    await expect(page.locator('body')).toHaveCSS('overflow', 'hidden');

    await page.locator('.mk-mobile-menu__close').tap();
    await expect(page.locator('body')).not.toHaveClass(/mk-menu-open/);
    await expect(page.locator('body')).not.toHaveCSS('overflow', 'hidden');
  });

  test('widening to desktop clears the open state', async ({ page }) => {
    await open(page);
    await page.setViewportSize({ width: 1200, height: 800 });
    await expect(page.locator('#mk-mobile-menu')).toBeHidden();
    await expect(page.locator('body')).not.toHaveClass(/mk-menu-open/);
  });
});
