import { test, expect } from '@playwright/test';

/**
 * The hero is full-page by contract (§11.5). It shipped with a four-way
 * `hero_style` select, production was left on `minimal`, and the hero rendered
 * at 43-50% of the viewport. These tests exist so that cannot come back.
 *
 * CI does not stand up a WordPress server (see .github/workflows/ci.yml and
 * docs/BUILD_STATUS.md), so these skip unless one is reachable. Point them at a
 * real site with MK_BASE_URL to run them for real:
 *
 *   MK_BASE_URL=https://maapkathi.com npm run test:e2e
 *
 * The server-free half of this contract is asserted in
 * tests/Unit/Theme/HeroFullViewportTest.php, which runs on every push.
 */

const BASE = process.env.MK_BASE_URL;

test.describe('hero is always the full viewport', () => {
  test.skip(!BASE, 'Set MK_BASE_URL to run these against a real site.');

  const VIEWPORTS = [
    { name: 'desktop-tall', width: 1920, height: 1200 },
    { name: 'desktop', width: 1920, height: 1080 },
    { name: 'laptop', width: 1440, height: 900 },
    { name: 'tablet', width: 768, height: 1024 },
    { name: 'mobile', width: 375, height: 667 },
    { name: 'mobile-small', width: 360, height: 640 },
    { name: 'landscape', width: 667, height: 375 },
  ];

    for (const vp of VIEWPORTS) {
    test(`hero fills the viewport at ${vp.name} (${vp.width}x${vp.height})`, async ({ page }) => {
      await page.setViewportSize({ width: vp.width, height: vp.height });
      await page.goto('/');

      const hero = page.locator('.mk-hero');
      await expect(hero).toBeVisible();

      const box = await hero.boundingBox();
      expect(box, 'hero must have a layout box').not.toBeNull();

      // Full viewport. 1px tolerance for sub-pixel rounding.
      expect(box!.height).toBeGreaterThanOrEqual(vp.height - 1);

      // min-height, not a fixed height: the copy must never be clipped.
      const copy = page.locator('.mk-hero__slide.is-active .mk-hero__copy');
      if (await copy.count()) {
        const cb = await copy.boundingBox();
        expect(cb!.top).toBeGreaterThanOrEqual(box!.top - 1);
        expect(cb!.bottom).toBeLessThanOrEqual(box!.bottom + 1);
      }

      // A full-bleed hero must not introduce sideways scroll.
      const overflows = await page.evaluate(
        () => document.documentElement.scrollWidth > document.documentElement.clientWidth,
      );
      expect(overflows).toBe(false);
    });
  }

  test('hero size is not adjustable from the DOM', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto('/');

    // The attribute must not be emitted at all.
    await expect
      .poll(() => page.evaluate(() => document.documentElement.hasAttribute('data-hero-style')))
      .toBe(false);

    // And even if something re-adds it, no selector reacts to it.
    for (const value of ['minimal', 'contained', 'split']) {
      await page.evaluate((v) => document.documentElement.setAttribute('data-hero-style', v), value);
      const box = await page.locator('.mk-hero').boundingBox();
      expect(box!.height, `data-hero-style="${value}" must not shrink the hero`).toBeGreaterThanOrEqual(899);
    }
  });

  test('any aspect ratio covers the hero without letterboxing', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto('/');

    const media = page.locator('.mk-hero__slide.is-active .mk-hero__media').first();
    if (!(await media.count())) test.skip(true, 'active slide has no <img> media');

    await expect(media).toHaveCSS('object-fit', 'cover');

    for (const [w, h] of [[600, 600], [450, 800], [1600, 450]]) {
      await media.evaluate((img: HTMLImageElement, [iw, ih]: number[]) => {
        const c = document.createElement('canvas');
        c.width = iw;
        c.height = ih;
        const ctx = c.getContext('2d')!;
        ctx.fillStyle = '#c33';
        ctx.fillRect(0, 0, iw, ih);
        img.removeAttribute('srcset');
        img.src = c.toDataURL();
      }, [w, h]);

      const [m, hero] = await Promise.all([
        media.boundingBox(),
        page.locator('.mk-hero').boundingBox(),
      ]);
      expect(m!.width, `${w}x${h} must cover hero width`).toBeGreaterThanOrEqual(hero!.width - 1);
      expect(m!.height, `${w}x${h} must cover hero height`).toBeGreaterThanOrEqual(hero!.height - 1);
    }
  });
});
