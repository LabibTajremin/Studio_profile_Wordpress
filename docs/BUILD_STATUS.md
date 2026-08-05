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

## What is real and working (verified by direct execution in this session)

- All PHP files pass `php -l` (zero syntax errors).
- The pure business-logic classes were smoke-tested directly (32/32
  assertions passed) — see `docs/` note below for how to re-run this:
  - `ContentVisibilityPolicy::decide()` — full §8 decision matrix.
  - `Theme\Motion::resolve_vars()` — preset/speed/stagger/reduced-motion math.
  - `Theme\ThemeSettings` — registry is exactly 31 keys, sanitisation/
    clamping/fallback-to-default behaviour.
  - `Video\VideoResolver` — YouTube/Vimeo/direct-file resolution, autoplay
    param construction, and the host-smuggling / scheme-rejection cases
    from §13.
- `plugins/maapkathi-core/` — plugin bootstrap, `Config` (§5 validation),
  `Database` (three custom tables via `dbDelta()`, MariaDB-safe per §3.6),
  `PostTypes`/`Taxonomies` (§3.1), `Roles` (§7, capabilities + rate
  limiting + bootstrap admin), `Storage` abstraction + `LocalStorageAdapter`
  (§6, `.htaccess` PHP-execution block, direct static URLs, disk/inode
  usage), `Video\VideoResolver` (§6.2), `Rest\UploadController` (chunked
  upload + magic-byte validation + orphan GC, §6.1/§13),
  `Approval\ContentVisibilityPolicy` + `ApprovalService` (§8),
  `Inquiries` (§3.2 contact form, honeypot, rate limiting), and the full
  `Theme\*` engine (`Accents`, `Patterns`, `Fonts`, `Motion` — ported
  verbatim from the source app's registries — plus `ThemeSettings` and
  `ThemeVarsBuilder` implementing all **31** settings from §11.1).
- `themes/maapkathi-theme/` — `functions.php` wiring the inline theme-vars
  block + pre-paint no-flash script + data-* attributes, header/footer with
  the visitor theme toggle, call button, mobile overlay menu, admin-shield
  note; `front-page.php` rendering all four hero media kinds (§6.3) through
  `VideoResolver`; `page-contact.php` wired to the inquiries handler;
  base/sections/motion CSS built entirely from CSS custom properties (no
  hardcoded animation durations, per §11.3); a working lightbox and motion
  engine in vanilla JS.
- Admin: a custom "Maapkathi" top-level menu with all 17 §3.3 screen slugs
  registered (capability-checked), a Dashboard showing real disk/inode
  usage, and a fully wired **Appearance** screen exposing all 30 visible
  controls across the 31 settings (§11.1), including the stagger_ms
  gating behaviour from §11.2.
- CI workflow, `composer.json`, `phpcs.xml.dist`, `phpstan.neon.dist`,
  `phpunit.xml.dist`, `playwright.config.ts`, `.wp-env.json` are all in
  place and correctly configured — untested only because no runtime was
  available in this sandbox.

## What is scaffolded but not fleshed out

- Admin screens for Projects, Services, Content, Blog, Team, Hero,
  Approvals, Users, Site Text, Settings, and Account render a placeholder
  and are capability-checked, but do not yet have working create/edit/
  delete forms. This is the largest remaining chunk of work — §9/§3.3.
- ACF Pro is assumed unavailable (no license key supplied). The plan is
  Carbon Fields (free) for the repeater/options-page fields the CPTs need
  (hero slides, socials, media-ratio hints, etc.) — not yet wired in.
- SEO (§12): no RankMath configuration or hand-written JSON-LD beyond the
  Organization block in the footer.
- Demo content seeder (§13/Phase 13), `wp maapkathi seed` — not built.
- `docs/ARCHITECTURE.md`, `CONFIGURATION.md`, `DEPLOYMENT.md`,
  `TESTING.md`, `THEMING.md`, `CLIENT_HANDOVER.md` are stubs, not the full
  documents required by Phase 14.
- Remote storage adapters (S3/Drive/R2/Supabase/Bunny) are not implemented
  — `StorageFactory` currently always returns `LocalStorageAdapter`,
  which is correct per DEC-2 but means switching drivers isn't wired yet.

## What could not be done in this environment at all

- **No PHPUnit run.** `composer install` cannot reach GitHub's archive API
  through this session's proxy. The test files in `tests/Unit/` are real,
  executable PHPUnit tests (verified by hand-running their assertions via
  a standalone smoke script — 32/32 passed) but have never been run under
  the actual PHPUnit binary.
- **No integration tests against real MariaDB**, no `wp-env` boot, no
  Playwright run, no browser screenshots, no axe-core a11y check.
- **No side-by-side comparison against `https://maapkathi.vercel.app`.**

## Recommended next steps

1. Run `composer install` and `npm install` in an environment with normal
   GitHub access, then `npx wp-env start` and `composer test`.
2. Wire Carbon Fields and finish the CRUD admin screens (Projects,
   Services, Hero, Content, Team, Blog, Users, Approvals, Site Text,
   Settings, Account).
3. Run the full test suite against real MariaDB 10.6 and fix whatever the
   `dbDelta()`/index-length/charset assumptions in `Database.php` get
   wrong on a real server.
4. Take Playwright screenshots at 375px on all 10 public pages and fix
   overflow issues as they're found.
5. Write the remaining five docs in `docs/`.
