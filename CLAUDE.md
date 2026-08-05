# Maapkathi Studio — WordPress Rebuild

## What this is
A WordPress rebuild (plugin `maapkathi-core` + theme `maapkathi-theme`) of
the Next.js app `LabibTajremin/MapKathi_Web_App`. Full spec:
`BUILD_INSTRUCTIONS.md` (repo root). Current honest status, what's
verified vs. not: `docs/BUILD_STATUS.md` — **read that before claiming
anything is done or broken.**

## Repo layout
- `plugins/maapkathi-core/src/` — all data/logic (Config, PostTypes,
  Fields, Storage, Video, Roles, Approval, Theme, Admin, Inquiries, Seo,
  Rest, Cli, Support). Plugin owns all data; theme only renders.
- `themes/maapkathi-theme/` — presentation only, no DB writes.
- `docs/` — ARCHITECTURE, CONFIGURATION, DEPLOYMENT, TESTING, THEMING,
  CLIENT_HANDOVER, BUILD_STATUS (living doc, keep updated).

## Key deliberate deviations from BUILD_INSTRUCTIONS.md (don't "fix" these back)
1. **No ACF Pro / Carbon Fields.** Both require Composer, and this
   environment cannot `composer install` (GitHub archive API is
   proxy-blocked — confirmed repeatedly). Fields are native
   `register_post_meta()` + `add_meta_box()` in `Fields\MetaBoxes`.
2. **CPTs nest under the custom "Maapkathi" admin menu via
   `show_in_menu => 'maapkathi'`** instead of one hand-built "Content"
   screen — gives full native WP CRUD per content type for free.
3. Both custom roles (`mk_admin`, `mk_editor`) must keep the **literal
   `edit_posts`** WordPress capability granted, alongside the custom
   `mk_*` caps — WordPress's own admin bootstrap gates custom-post-type
   screens on it regardless of `capability_type` remapping. Removing it
   breaks all CPT admin screens for that role. (Found via live testing,
   see below.)

## Hard-won bug fixes — do not reintroduce these patterns
1. **Never map `'edit_post'`, `'read_post'`, or `'delete_post'`** (the
   *singular* CPT capability keys) to a capability string used elsewhere
   for bare `current_user_can()` checks. WordPress globally aliases that
   string to "requires a specific post ID," breaking every bare check of
   it anywhere in the plugin. Leave those 3 keys unset; let WP auto-derive
   them.
2. Any CPT whose fields should be editable via REST/block editor needs
   `'custom-fields'` in `supports`, or WP never exposes `meta` in the REST
   schema at all.
3. Checkbox/boolean meta fields must register as REST type `'boolean'`,
   not `'string'`, or REST writes 400.
4. Rate limiting / attempt-counters must hook a hook that fires **exactly
   once** per real event (e.g. `wp_login_failed`), never the
   `authenticate` filter chain directly — WordPress can re-enter it more
   than once per request, silently double-counting even successful
   logins.
5. `mk_service` has `has_archive => false` deliberately — `/services` is
   a hand-built Page, not a CPT archive. Don't re-enable has_archive for it.

## Environment notes
- **Docker works here** (daemon isn't running by default — start with
  `sudo -n dockerd &`, wait ~3s, then `docker info` to confirm). Use it to
  spin up a real `mariadb:10.6` + `wordpress:*-apache` + `wordpress:cli-*`
  stack via docker-compose, mount `plugins/maapkathi-core` and
  `themes/maapkathi-theme` as volumes, and test for real — this is how
  the bugs above were actually found. Prefer this over reasoning about
  untested code when stakes are high.
- **`composer install` cannot reach `github.com`'s archive API** (403 /
  timeout through this proxy) — PHPUnit/PHPCS/PHPStan are declared in
  `composer.json` but have never actually run. `npm install` works fine
  (npmjs.org isn't blocked).
- Tests in `tests/Unit/` are real PHPUnit tests but were only verified via
  a standalone smoke-test PHP script (no PHPUnit binary available).

## Still unverified (be honest about this)
No real browser/Playwright/axe-core run ever happened — only HTTP-level
checks (status codes, response bodies, DB state, debug.log). No real video
file has been pushed through the upload/chunking path. No HTTPS, no actual
Hostinger hosting. See `docs/BUILD_STATUS.md` "Recommended next steps" for
what to do first in a fresh session.
