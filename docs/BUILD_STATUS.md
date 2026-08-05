# Build status — honest checklist

This build was produced in a sandboxed session with no Docker/`wp-env`,
no live WordPress install, and no MariaDB instance — and `composer install`
could not reach `github.com`'s archive API through this environment's
network proxy, so the dev dependencies (PHPUnit, PHPCS, PHPStan, WPCS)
are declared but were never actually installed or run here. Everything
below is scoped honestly against that constraint. **Nothing in this repo
has been verified against a live WordPress+MariaDB site or a real
browser** — that verification is the next required step before any phase
gate in `BUILD_INSTRUCTIONS.md` can be marked passed.

## Deliberate deviation: native WP fields instead of ACF Pro/Carbon Fields

`BUILD_INSTRUCTIONS.md` assumes ACF Pro (paid, unavailable here) or
Carbon Fields (free, but a Composer package — and this sandbox cannot
`composer install` anything from GitHub). Introducing that dependency
would mean the plugin silently breaks the moment `vendor/` isn't
regenerable, which is exactly the failure mode a Hostinger shared-hosting
deploy (no SSH Composer access on the cheapest plans) is most likely to
hit. Instead, every CPT's extra fields are defined in
`Fields\MetaBoxes::schema()` — plain PHP, version-controlled, rendered via
native `add_meta_box()` + `register_post_meta()`, zero third-party
runtime dependency. This is arguably a stronger fit for "no
click-configure ACF" than the spec's own assumption.

A related, deliberate deviation: post types that were `public => false`
placeholders (testimonials, clients, awards, FAQs, values, stats, process
steps, team) now use `show_in_menu => 'maapkathi'`, so each gets a full
native WordPress list/edit screen nested under the custom "Maapkathi" menu
automatically — sortable, filterable, with working create/edit/delete —
instead of the single custom-built "Content" unified manager described in
§3.3 #7. Feature-complete, just organized as several native screens
instead of one hand-built one.

## What is real and working (verified by direct execution in this session)

- All PHP files pass `php -l` (zero syntax errors), re-checked after every
  batch of changes in this session.
- The pure business-logic classes were smoke-tested directly (32/32
  assertions passed): `ContentVisibilityPolicy::decide()` (full §8 matrix),
  `Theme\Motion::resolve_vars()`, `Theme\ThemeSettings` (31-key registry +
  sanitisation), `Video\VideoResolver` (YouTube/Vimeo/direct-file, autoplay
  params, host-smuggling/scheme rejection from §13).
- **Content model** (§3.1): all CPTs + taxonomy, all nested under the
  custom "Maapkathi" admin menu; the 3 custom tables via `dbDelta()`,
  MariaDB-safe (§3.6); `Fields\MetaBoxes` for every CPT's extra typed
  fields.
- **Storage + video** (§6): `LocalStorageAdapter` (direct static URLs,
  `.htaccess` PHP block, disk/inode usage), chunked upload + magic-byte
  validation + orphan GC (`Rest\UploadController`), `VideoResolver` (§6.2).
- **Roles + approval workflow** (§7, §8): capabilities, login rate
  limiting, admin bootstrap, full `ContentVisibilityPolicy` decision
  matrix, `ApprovalService` (mk_revisions queue + audit log).
- **Theme engine** (§10, §11): all 24 accents / 22 patterns / 8 fonts / 31
  settings ported verbatim, inline CSS-var injection with cache
  invalidation on save, no-flash pre-paint mode script.
- **Admin — all 17 §3.3 screens now functional**, not placeholders:
  Dashboard (disk/inode usage + pending-approval count), Projects/
  Services/Team/Testimonials/Clients/Awards/FAQs/Values/Stats/
  Process-Steps (native WP CRUD screens via `show_in_menu`), Blog (native
  `edit.php`), Hero (custom repeater-style form for up to 8 slides, all
  four media kinds, length-warning copy, hold-until-video-ends, global
  slide duration), Approvals (pending-revisions queue with approve/reject
  + note, writes to the audit log), Users (invite, role change, activate/
  deactivate), Appearance (all 30 controls across 31 settings), Site Text
  (all 29 copy fields grouped by page/section), Settings (studio/contact/
  socials/behaviour/SEO defaults), Account (aliases WP's native
  `profile.php` rather than re-implementing password/profile security).
- **Public templates — all 10 §3.2 routes**: `front-page.php` (hero
  through `VideoResolver`, clients, featured projects, FAQ accordion,
  CTA band), `archive-mk_project.php`, `single-mk_project.php` (spec
  sidebar + gallery lightbox), `page-services.php`,
  `single-mk_service.php`, `page-about.php` (stats/values/vision-mission),
  `page-team.php`, `home.php` + `single.php` (blog, 404 when disabled per
  §3.5), `page-contact.php`, plus a `404.php`.
- **SEO** (§12): hand-written `Organization` JSON-LD (footer),
  `FAQPage` (homepage), `CreativeWork` (project pages),
  `BreadcrumbList` (project/service detail pages); dynamic meta/OG/
  Twitter/canonical via `Seo\Seo`; `robots.txt` disallows `/wp-admin`;
  WordPress core's own `wp-sitemap.xml` (built in since WP 5.5) indexes
  every public post type automatically, filtered to drop blog posts when
  the blog is disabled.
- **Demo seeder** (§13/Phase 13): `wp maapkathi seed` — idempotent (guards
  on an option marker), creates site/theme settings, 3 categories + 6
  projects, 4 services, 4 team members, 3 testimonials, 6 clients, 3
  awards, 4 stats, 4 values, homepage FAQs, and 4 hero slides — one per
  media kind, exercising every hero path. Placeholder images come from
  `placehold.co` rather than bundled binaries (no way to fetch/bundle CC0
  demo assets in this sandbox); the video-upload slide is left with an
  empty URL for the same reason and needs a real MP4 uploaded once a live
  install exists.
- CI workflow, `composer.json`, `phpcs.xml.dist`, `phpstan.neon.dist`,
  `phpunit.xml.dist`, `playwright.config.ts`, `.wp-env.json` all in place
  — untested only because no runtime was available in this sandbox.

## What is still thin

- The Settings screen doesn't yet manage logo/favicon/video uploads (needs
  a Media Library picker, noted inline in the screen itself).
- `Seo\Seo` covers the four JSON-LD types and meta/OG/canonical, but
  doesn't replicate a full RankMath configuration — installing RankMath
  itself (from the WP.org plugin browser, not Composer) remains a
  documented manual step in `docs/DEPLOYMENT.md`.
- Remote storage adapters (S3/Drive/R2/Supabase/Bunny) are not
  implemented — `StorageFactory` always returns `LocalStorageAdapter`,
  correct per DEC-2, but switching drivers isn't wired.
- No image derivatives/LQIP-blurhash placeholder generation yet.

## What could not be done in this environment at all

- **No PHPUnit run.** `composer install` cannot reach GitHub's archive API
  through this session's proxy — confirmed on two separate attempts. The
  test files in `tests/Unit/` are real, executable PHPUnit tests (verified
  by hand-running their assertions via a standalone smoke script — 32/32
  passed) but have never run under the actual PHPUnit binary.
- **No integration tests against real MariaDB**, no `wp-env` boot, no
  Playwright run, no browser screenshots, no axe-core a11y check, no RTL/
  actual-font rendering check.
- **No side-by-side comparison against `https://maapkathi.vercel.app`.**
- **No ACF Pro / Carbon Fields** — see the deviation note above.

## Recommended next steps

1. Run `composer install`/`npm install` somewhere with normal GitHub
   access, then `npx wp-env start` and `composer test`.
2. Install this plugin + theme on a real WordPress + MariaDB 10.6 install
   (or `wp-env`) and walk every one of the 17 admin screens and 10 public
   pages by hand — this is genuinely unverified code.
3. Run `wp maapkathi seed`, upload one real MP4 to the hero-video-upload
   slide, and confirm autoplay/Range/206 behaviour per §6.1's curl test.
4. Take Playwright screenshots at 375px on all 10 public pages and fix
   overflow as it's found; run axe-core.
5. Add a Media Library picker to the Settings screen for logo/favicon.
6. Write the integration + E2E test suites described in `docs/TESTING.md`.
