# Maapkathi Studio — WordPress Rebuild

A pixel-and-feature-complete WordPress rebuild of the Maapkathi Studio Next.js
application (`LabibTajremin/MapKathi_Web_App`), built as one custom plugin
(`maapkathi-core`) and one custom theme (`maapkathi-theme`) for MariaDB /
Hostinger shared hosting.

See `BUILD_INSTRUCTIONS.md` for the full specification and `docs/` for
architecture, configuration, deployment, testing, theming, and handover
documentation.

## Status

This build is in progress. See `docs/BUILD_STATUS.md` for an honest,
up-to-date checklist of what is implemented, what is scaffolded, and what
still needs a live WordPress + MariaDB environment to finish and verify.

## Local development

```bash
composer install
npm install
npx wp-env start
```

## Repository layout

- `plugins/maapkathi-core/` — all data, business logic, storage, roles, REST.
- `themes/maapkathi-theme/` — presentation only. Never writes to the database.
- `docs/` — architecture, configuration, deployment, testing, theming, handover.
- `tests/` — PHPUnit (unit + integration) and Playwright (E2E).
