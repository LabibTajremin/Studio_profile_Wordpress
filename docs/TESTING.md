# Testing

> See `docs/BUILD_STATUS.md` for what has and has not actually been
> executed. This document describes how to run the suite once a normal
> (unrestricted) environment is available.

```bash
composer install
composer lint        # PHPCS + WPCS
composer analyse      # PHPStan level 6+
composer test:unit    # PHPUnit, no WordPress needed
composer test:integration  # requires WP_TESTS_DIR + real MariaDB 10.6
npm install
npx playwright install --with-deps chromium
npm run test:e2e
```

## Layers

- **Unit** (`tests/Unit`) — pure logic: `ContentVisibilityPolicy`,
  `Theme\Motion::resolve_vars()`, `Theme\ThemeSettings` (the 31-setting
  registry + sanitisation), `Video\VideoResolver`. No WordPress functions
  required beyond a handful stubbed in `tests/bootstrap.php` when
  `WP_TESTS_DIR` is unset.
- **Integration** (`tests/Integration`, not yet written) — needs the real
  WP PHPUnit test suite + MariaDB 10.6 per §3.6: CPT registration, meta
  round-trips, storage adapter contract, `dbDelta()` idempotency, custom
  table charset/index-length assertions.
- **E2E** (`tests/E2E`, not yet written) — Playwright, per §14: login,
  content CRUD, the 30-control appearance/motion round-trip (§11.4), video
  autoplay across all three sources (§6.2), contact form, 375px viewport
  screenshots, axe-core.

## Why integration/E2E are not included yet

This build was produced in a sandbox with no Docker/`wp-env`, no MariaDB,
and no browser automation target — there was nothing to run them against.
The unit tests that could run standalone were verified directly (see
`docs/BUILD_STATUS.md`).
