# Build status — honest checklist

## Footer subscribe button, showcase rail, footer logo size

**The subscribe button was invisible.** It painted `background: var(--accent)`
with `color: var(--accent-foreground)`, but FR-08.2 lets the footer
background be set to the accent — and an accent button on an accent footer
cannot be seen. It now inverts the footer's own colours (`--footer-fg`
background, `--footer-bg` text), which `FooterColor::resolve()` has already
contrast-checked against each other, so it is legible on every footer
background by construction. Hover lifts it 2px with a shadow rather than
changing hue, so that guarantee survives the hover state.

**Featured work is a horizontal rail.** The full-bleed gapless wall ran
edge to edge with no breathing room and could only ever show what fitted
one screen. It is now a snap-scrolling rail inside the section's gutter.
It scrolls with touch, trackpad, scrollbar and keyboard from CSS alone;
`assets/js/showcase.js` adds prev/next arrows on top and creates them in
JS rather than PHP, so a failed script leaves no dead controls. "View all
work" changed from bare underlined text to a pill with a hairline border
and an arrow that travels on hover.

**Footer logo size is a slider.** The number-only "Maximum logo height"
field is now a paired range + number control labelled "Logo size", and its
ceiling went from 160px to 320px so a wordmark can be made large enough to
read.

Raising that ceiling exposed a real bug: `.mk-footer__logo-img` set
`max-height` and `width: auto` but no `max-width`, so a wide wordmark at a
large logo height became wider than its column, stretched the footer grid
and put a horizontal scrollbar on the whole page (GR-04). Caught at 390px
with the size set to 200px, and fixed with `max-width: 100%`.

Verified on the live stack with the footer background set to the accent —
the exact reported condition. The button renders cream on maroon instead
of maroon on maroon, and hover applies a −2px translate plus a shadow. The
rail insets 16 / 31 / 168px at 390 / 768 / 1440, scrolls at every width,
and the page's horizontal overflow is 0 at all three. The next arrow
advances exactly one tile plus its gap (373px), the prev arrow enables
once scrolled, and both hide when everything already fits. The logo
slider drives the number box, saves, persists across a reload and reaches
the front end as `--footer-logo-max-h: 200px`.

## Demo data covers partners

`wp maapkathi seed` now seeds six partners with generated logos, alt text
and websites, so the logo band — and therefore the separation it puts
between the closing section and the footer — is visible on a freshly
seeded site instead of only after someone adds partners by hand. Partners
are also torn down by `--fresh`, alongside every other demo type.

The client seeder was corrected at the same time: it was attaching its
generated logo as the featured image, which the client wall no longer
reads, so seeded clients would have rendered as initials marks. It writes
to the logo field now.

Verified on the live stack: `--fresh` reseeds cleanly, all six partners
carry a logo and alt text, all six clients carry a logo and no featured
image, the front page renders six client logos with no initials marks, and
the band renders six partner logos at the default source (twelve with
"both"). Partners appear in the Maapkathi admin menu between Clients and
Awards, and its list screen shows the six seeded records.

## Client and partner logos are their own field

A client's featured image is a photograph of the work, so reading it for
the client wall put an interior shot in among the logos on the live site.
Both `mk_client` and `mk_partner` now carry a dedicated `mk_logo`
attachment field (the existing reusable `media` field type), resolved by
`Support\Logos`.

The two types deliberately differ on the fallback. Partners have always
been logo-only records, so a partner's featured image still counts as a
logo and existing sites render unchanged. A client's does not: the client
wall and the logo band use the dedicated field only, and a client with no
logo uploaded falls through to the initials mark rather than showing its
photograph.

The logo band above the footer (FR-10) also gained a source setting —
partners, clients, or both — so a "Trusted by" band does not mean entering
every logo twice. It sits on the page background between the last section
and the footer, which is what stops an accent-tinted closing section and
an accent-tinted footer reading as one oversized block.

While wiring the field up, a pre-existing bug surfaced: the admin CSS/JS
bundle was enqueued only on screens whose hook contains "maapkathi", but
the content edit screens are WordPress's own post.php / post-new.php. Every
media and icon picker inside a meta box has therefore been inert since it
was added — "Choose image" did nothing, because neither `wp_enqueue_media()`
nor the plugin's admin JS ever loaded there. `Menu::is_mk_post_screen()`
now includes those screens.

Verified on the live Docker stack: the Logo field renders on both edit
screens, the Media Library modal actually opens from it, a value saved
through the real admin form round-trips and shows its preview thumbnail,
and the front page then renders that logo with the correct alt text. A
client holding only a featured image renders the initials mark, not the
photograph. The band's three sources return 2 / 1 / 3 logos against a
fixture where one client's image is a photograph rather than a logo, and
its computed background is transparent (the page background) while the
footer's is not. Thirteen assertions covering every case in
`tests/Integration/LogoResolutionTest.php` were also executed directly
against the running WordPress, since PHPUnit itself still cannot run here.


## Static assets are versioned in the path, not in `?ver=`

Production (Hostinger/LiteSpeed) has "remove query strings from static
resources" switched on, which strips WordPress's `?ver=` cache-buster
before it reaches the browser. Combined with
`Cache-Control: max-age=604800` on the CSS, that meant no deploy was ever
picked up: fetching `base.css` returned a 12,535-byte August copy while
`base.css?bust=…` returned the current 21,259-byte one from the same
server. Every visual complaint about the footer, the featured-work
showcase and the hero height traced back to this one cause, not to the
markup or the CSS, both of which were already correct on the server.

`themes/maapkathi-theme/inc/asset-cache.php` moves the version into the
path instead: each stylesheet and script is mirrored into
`wp-content/uploads/maapkathi-assets/<name>.<mtime>.<ext>`, so a changed
file gets a URL no cache layer has ever seen and an unchanged file keeps
its URL and stays cached. Superseded copies are kept for a week rather
than deleted immediately, because downstream-cached HTML can still point
at the previous fingerprint. Anything that fails — an unwritable uploads
directory, an unreadable source — falls back to the old theme URL with a
`?ver=`, so the worst case is today's behaviour, never a missing
stylesheet.

Verified on the live Docker stack: all seven mirrored assets return 200
with the correct MIME type and full byte counts, the footer computes as a
four-column grid at 1440px and a single column at 390px, and appending a
byte to `base.css` produced a new fingerprinted URL on the next request
while the previous copy stayed in place.


> **Latest pass (client-view sweep + production hardening).** Added a
> versioned migration runner, an Enquiries inbox, the placeholder and
> health endpoints, favicon/logo handling, security hardening, locally
> generated demo imagery, and Hostinger deployment files — and fixed a
> further batch of real bugs listed under "Second round of live-verified
> fixes" below. All routes and admin screens re-verified against the live
> Docker stack with an empty error log.

This plugin + theme have now been installed and exercised against a real,
running WordPress 6.7 + MariaDB 10.6 stack (Docker: `mariadb:10.6`,
`wordpress:6.7-php8.2-apache`, `wordpress:cli-php8.2`), not just read for
syntax. This section is the honest record of what that live testing found,
fixed, and confirmed — plus what's still outside what could be checked here
(no real browser/Playwright, no HTTPS, no production traffic).

`composer install` still cannot reach `github.com`'s archive API through
this sandbox's proxy. PHPCS and PHPStan are now run anyway — they were
fetched by git clone and symlinked into `vendor/` — and both are part of
every change: phpcs clean, phpstan down to two long-standing
ignore-pattern mismatches. PHPUnit still has no binary here, so the tests
in `tests/Unit/` are exercised by standalone assertion scripts locally and
by CI upstream rather than by a local `phpunit` run.

## Change Request R1 (spec: `StudioProfileThemeRequirementsR1.docx`)

All ten feature requirements implemented and verified against a real
WordPress 6.7 / PHP 8.2 / MariaDB 10.6 stack. Full evidence, including
what was *not* verified, is in `docs/QA_SIGNOFF_R1.md`.

- FR-01 header colour, FR-02 section titles, FR-03 section builder,
  FR-04 gallery/masonry, FR-05 map, FR-06/07 icons, FR-08 footer,
  FR-09 copyright bar, FR-10 partners.
- Plus a Showcase layout for Featured work, supplied by the client as a
  reference recording and now the default.

Two defects were found by running the gate rather than reading the code:
deprecations on every admin request from `add_submenu_page( null, … )`,
and a horizontal scrollbar at ≤768px from the header. Both fixed.

Still unverified after R1: non-Chromium browsers, Lighthouse numbers, a
real 30-section builder load, an SVG icon uploaded through the media
library, and a screen-reader pass.

## Bugs found and fixed by live testing (would have shipped broken otherwise)

These would not have been caught by code review, `php -l`, or unit tests —
each only appeared once a real WordPress core actually processed a
request. All four are fixed, and the fix was re-verified live afterward.

1. **Site-wide admin lockout.** `PostTypes::args_for()` mapped the
   `edit_post`/`read_post`/`delete_post` meta-cap keys to the plugin's own
   generic capability strings (`mk_edit_content`, `mk_publish_content`,
   `read`). WordPress's `_post_type_meta_capabilities()` globally registers
   whatever string is used there as an alias requiring a specific post ID
   — so every bare `current_user_can( 'mk_edit_content' )` call anywhere in
   the plugin (Dashboard, every admin screen, meta box auth) started
   silently resolving through `map_meta_cap()`'s "you must check this
   against a specific post" fallback and returning `do_not_allow`. Result:
   **the entire admin area 403'd for every role**, including
   administrators. Root-caused via `map_meta_cap()` tracing against live
   WordPress core source, not guessed. Fixed by leaving those three keys
   unset (WordPress auto-derives collision-free per-post-type strings) and
   mapping only the plural primitive caps.
2. **`/services` routing conflict.** `mk_service` had both `has_archive =>
   true` (default) and a hand-built `page-services.php` Page at the same
   slug. WordPress's CPT archive rewrite rule silently won, so the custom
   category/sub-service page never rendered — confirmed via the response's
   `body` class (`post-type-archive-mk_service` instead of
   `page-template-default`). Fixed by setting `has_archive => false` for
   `mk_service` specifically (individual services still resolve at
   `/services/{slug}`).
3. **REST `meta` invisible + wrong type.** No CPT declared `'custom-fields'`
   support, so WordPress never added a `meta` property to the REST schema
   at all (confirmed via `OPTIONS /wp-json/wp/v2/mk_project` before/after)
   — meaning the block editor's REST-based save could never touch any of
   `Fields\MetaBoxes`'s registered fields. Separately, checkbox fields were
   registered as REST type `string`, so any real boolean update was
   rejected with `rest_invalid_type`. Fixed by adding `'custom-fields'` to
   every CPT's supports array and mapping the `checkbox` field type to
   REST type `boolean`. Re-verified with a real `POST
   /wp-json/wp/v2/mk_project/{id}` updating `mk_summary`, `mk_is_featured`
   (bool), and `mk_area_sqft` (number) — all three persisted correctly.
4. **Login rate limiter double-counted successful logins.** The failed-
   attempt counter was incremented inside the `authenticate` filter, which
   WordPress core can re-enter more than once per single login request —
   confirmed live: **one successful login incremented the "failed
   attempts" counter**, meaning a handful of normal logins during testing
   or real use could lock a legitimate user out for up to 90 minutes.
   Fixed by moving the increment to WordPress's dedicated
   `wp_login_failed` action (fired exactly once per genuine failure) and
   keeping only the pre-check (no side effects) on `authenticate`.
   Re-verified: 3 clean logins in a row now leave the counter untouched; a
   deliberately wrong password increments it by exactly 1.
5. **Hero admin screen PHP warnings.** `Admin/views/hero.php` assumed
   every saved slide carried all media-kind fields, but
   `HeroScreen::save()` only writes the keys relevant to that slide's own
   `media_kind`. Editing a saved image slide threw ~10 "Undefined array
   key" warnings per page load. Fixed by merging every row over the full
   default shape before rendering. Re-verified: zero log entries after.

## Second round of live-verified fixes

Found by walking the site as a client would, against the live stack:

6. **All 29 Site Text fields were completely dormant.** `SiteText::` was
   never called by a single template — the entire screen saved values
   nothing ever read, which is precisely the "a control that does nothing
   is worse than no control" failure §11 exists to prevent. Wired every
   field into the templates, added shipped defaults so a fresh install
   renders real copy, and verified live that changing a heading in the
   admin changes the public homepage.
7. **The homepage was missing 8 of its 13 required sections.** §3.2
   specifies hero, tagline note, client wall, portfolio categories,
   featured projects, services, stats band, values, team, testimonials,
   awards, FAQ, and CTA. Only 5 existed. Rebuilt the whole front page.
8. **Client logos and FAQs read from the wrong data source.**
   `front-page.php` pulled them from `mk_site_settings` while the admin
   screens wrote them as `mk_client` / `mk_faq` posts — so the client wall
   could never render and FAQs only worked by accident of the seeder.
   Added a `Support\Content` accessor layer that every template and the
   JSON-LD now share.
9. **FAQPage JSON-LD described a different set of FAQs than the page
   rendered** (same root cause as #8) — a genuine structured-data
   violation. Both now read through the same accessor.
10. **Empty meta descriptions site-wide.** The SEO fallback chain ended at
    `get_bloginfo('description')`, which is blank on a fresh install, so
    every page shipped `<meta name="description" content="">`. Now walks
    excerpt → summary → trimmed content → SEO default → studio tagline,
    and OG images fall back through cover → default → logo → generated
    placeholder.
11. **`nav.php` would have fatalled the site.** It declared
    `mk_blog_enabled()` unguarded, and the new plugin helper declares the
    same name — a redeclare fatal the moment both loaded. Both are now
    guarded.
12. **Contact-form validation errors were stored but never displayed.**
    A visitor submitting a bad address was redirected to a form that
    looked like nothing happened. Errors now render, and the form
    repopulates from the raw input (sanitize_email() had been wiping the
    very field that needed correcting).
13. **No Enquiries inbox existed at all.** The form wrote to
    `mk_inquiries` and nothing in wp-admin ever read it — submissions were
    invisible to the site owner. Built the inbox with read/unread state,
    filters, pagination, reply/delete, and an unread-count bubble on the
    menu.
14. **Rewrite rules were flushed before post types were registered** on
    activation, so `/work/{slug}` 404'd until someone re-saved Permalinks.

## New in this pass

- **Versioned migration runner** (`Support\Migrations`): ordered,
  idempotent steps with a concurrency lock, run on activation *and*
  version-guarded on every load, so an in-place file update upgrades the
  database with no deactivate/reactivate. Verified live by dropping all
  three tables and simply loading a page — they were recreated and the
  health endpoint went green. Uses `dbDelta()` rather than
  `CREATE TABLE IF NOT EXISTS` / `ADD COLUMN IF NOT EXISTS`: dbDelta gives
  the same create-if-missing semantics portably, whereas the MariaDB-only
  `IF NOT EXISTS` column syntax is rejected outright by MySQL 8.
- **Demo images and logos generated locally with GD** — project covers,
  galleries, member photos, client logos, hero art, and a favicon, all
  imported through the Media Library. No external placeholder service, so
  a fresh install looks finished with zero outbound network access.
  Seeder is idempotent and supports `--fresh`.
- **Placeholder endpoint** (`/wp-json/maapkathi/v1/placeholder/{w}/{h}`) —
  branded gradient SVGs generated on demand, replacing the source app's
  `/api/placeholder` route.
- **Health endpoint** (`/wp-json/maapkathi/v1/health`) — app + DB +
  schema status, public, leaking no version or config detail.
- **Security hardening** (§13): version string and asset `?ver=` stripped,
  XML-RPC disabled, REST user enumeration and `?author=` scans blocked,
  generic login errors, upload MIME allowlist (SVG excluded), and
  `X-Content-Type-Options` / `X-Frame-Options` / `Referrer-Policy` /
  `Permissions-Policy` / CSP `frame-src` limited to the video embed
  allowlist. All confirmed present on live response headers.
- **Favicon fallback chain** (favicon → light logo → dark logo → built-in
  mark) and CSS-only light/dark logo swap, plus Media Library pickers for
  logo and favicon on the Settings screen.
- **Hero editor rebuilt**: media-kind toggles that show only the relevant
  fields, the §6.3 length note, and a client-side duration warning.
- **Deployment kit**: `deploy/wp-config-sample.php` and
  `deploy/htaccess-sample.txt`, plus a rewritten `docs/DEPLOYMENT.md`
  covering the real hPanel flow, the Range-request video test, backups,
  and a troubleshooting table.

## Verified live, end to end, with a real browser-equivalent HTTP session

- **Every admin screen returns 200** for an administrator, with zero
  entries in `wp-content/debug.log`: Dashboard, Hero, Approvals, Users,
  Appearance, Site Text, Settings, and both the list and "Add New" screens
  for all 10 custom post types.
- **Editor-role boundary is correct**: `mk_editor` gets 200 on Dashboard
  and every CPT's list/new screen, and a genuine 403 on
  Appearance/Approvals/Users/Settings — matching §7 exactly, not just
  "menu item hidden."
- **All 10 public routes** (`/`, `/work/`, `/work/{slug}`, `/services/`,
  `/services/{slug}`, `/services/{parent}/{child}/`, `/about/`, `/team/`,
  `/blog/`, `/contact/`) return 200 with well-formed, fully-closed HTML and
  zero PHP log entries.
- **The theme engine is live-confirmed working end to end**, not just
  unit-tested: saved 19 of the 30 Appearance/Motion controls through the
  real admin form (accent, pattern, radius, density, hero style, motion
  preset, all 6 motion selects, cursor/loader style, parallax/speed/
  stagger sliders, motion-on-mobile) and confirmed the **public homepage's
  rendered `<html>` reflected every change immediately** (`--accent:
  #0f5a5e`, `--radius: 9999px`, `data-hero-style="contained"`,
  `data-cursor-style="ring"`, `data-loader-style="bar"`) with no hard
  refresh — proving the transient cache-bust-on-save (§11.3) genuinely
  works.
- **Hero screen save** round-tripped through the real form (slide
  duration, media kind, headline, image URL) and persisted correctly into
  `mk_site_settings`.
- **Settings screen save** round-tripped (studio name, contact info, blog
  toggle, verification toggle) and persisted correctly.
- **Contact form**: a real unauthenticated POST through
  `admin-post.php` with nonce + honeypot fields correctly inserted a row
  into `wp_mk_inquiries` and redirected to `?mk_inquiry=sent`.
- **`wp maapkathi seed`** ran cleanly on a fresh install with zero errors,
  populating every content type.
- **Plugin activate → deactivate → reactivate** is clean (no fatals), and
  `dbDelta()` created all three custom tables correctly on first
  activation.

## Deliberate deviations from the spec (with reasons)

- **Native `register_post_meta()` + `add_meta_box()` instead of ACF
  Pro/Carbon Fields.** Carbon Fields is a Composer package this sandbox
  cannot install (same GitHub-archive-API block that stops
  PHPUnit/PHPCS), and a hard runtime dependency on it is a real
  deployment risk on shared hosting without guaranteed SSH Composer
  access. `Fields\MetaBoxes::schema()` is plain, version-controlled PHP —
  arguably a stronger fit for "no click-configure ACF" than the spec's
  own assumption.
- **`show_in_menu => 'maapkathi'` per CPT** instead of one hand-built
  "Content" unified manager (§3.3 #7). Testimonials, clients, awards,
  FAQs, values, stats, process steps, and team each get a full native WP
  list/edit screen nested under the custom menu — sortable, filterable,
  fully working CRUD, verified live above — just organized as several
  native screens instead of one custom-built one.
- **`edit_posts` (WordPress's literal, non-namespaced capability) granted
  to both custom roles**, discovered necessary by the live-testing bug #1
  above: `wp-admin/includes/menu.php`'s `user_can_access_admin_page()`
  independently gates whether a custom post type's admin screens are
  reachable at all on this literal string, regardless of any
  `capability_type` remapping. Documented inline in `Roles.php`.

## What is still thin

- The Settings screen doesn't yet manage logo/favicon/video uploads (needs
  a Media Library picker — noted inline in the screen itself).
- `Seo\Seo` covers the four JSON-LD types and meta/OG/canonical, but
  doesn't replicate a full RankMath configuration — installing RankMath
  itself (from the WP.org plugin browser, not Composer) remains a
  documented manual step in `docs/DEPLOYMENT.md`.
- Remote storage adapters (S3/Drive/R2/Supabase/Bunny) are not
  implemented — `StorageFactory` always returns `LocalStorageAdapter`,
  correct per DEC-2, but switching drivers isn't wired.
- No image derivatives/LQIP-blurhash placeholder generation yet.
- The hero video-**upload** path (as opposed to video-link) has not been
  exercised with a real MP4 file — no binary test asset was available in
  this sandbox. The chunked-upload REST endpoint's logic was reviewed but
  not driven through a real multi-chunk upload.

## What could not be done in this environment at all

- **No PHPUnit/PHPCS/PHPStan run** — `composer install` cannot reach
  GitHub's archive API through this session's proxy, confirmed on
  multiple attempts. The pure-logic tests in `tests/Unit/` were instead
  hand-verified via a standalone smoke script (32/32 passed) and,
  separately, exercised far more thoroughly through this session's live
  WordPress testing above.
- **No real browser / Playwright / axe-core / mobile-viewport
  screenshots.** All verification above is HTTP-level (status codes,
  response bodies, rendered HTML/CSS-variable output, database state) —
  genuinely strong evidence the backend is correct, but it cannot catch
  pure-CSS layout bugs, JS runtime errors, or visual regressions the way
  an actual browser run would.
- **No HTTPS / TLS**, no real domain, no production traffic, no
  Hostinger-specific quirks (LiteSpeed vs. Apache, actual shared-hosting
  resource limits).
- **No side-by-side comparison against `https://maapkathi.vercel.app`.**
- **No real video file exercised** through the upload/chunking/faststart
  path (§6.1) — no MP4 test asset was available.

## Recommended next steps

1. Get a real browser in front of this (Playwright, or just click through
   it) — this is the one category of bug this session's HTTP-level
   testing structurally cannot catch.
2. Upload a real MP4 through the Hero screen and confirm HTTP 206 Range
   support on the resulting static URL (§6.1's curl test).
3. Run `composer install`/`npm install` somewhere with normal GitHub
   access, then `composer test` and `npx playwright test`.
4. Add a Media Library picker to the Settings screen for logo/favicon.
5. Install RankMath and confirm it coexists cleanly with `Seo\Seo`.
6. Deploy to a Hostinger staging instance and repeat this session's
   sweep (every admin screen, every public page, editor-role boundary,
   theme-engine save round-trip) against the real target environment.
