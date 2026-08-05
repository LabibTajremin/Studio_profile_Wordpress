# Maapkathi Studio — WordPress Rebuild: Build Instructions for an AI Agent

> **How to use this file:** drop it at the root of a fresh repo as `BUILD_INSTRUCTIONS.md`, open the folder, run your coding agent, and say:
> *"Read BUILD_INSTRUCTIONS.md and build Phase 0 and Phase 1. Stop after Phase 1 and show me the verification output."*
>
> Build **one phase at a time**. Every phase ends with a **verification gate** and a **commit + push**. Do not start the next phase until the gate passes.

---

## 0. What we are building

A **pixel-and-feature-complete WordPress rebuild** of an existing Next.js 15 application ("Maapkathi Studio") — a white-label portfolio & showcase platform for interior/architectural design studios.

**The source application** lives at `LabibTajremin/MapKathi_Web_App` and is live at `https://maapkathi.vercel.app`. It is the specification. When this document and the source app disagree, **the source app wins** — go read its code.

### Non-negotiables

1. **Zero feature loss.** Every capability enumerated in §3 must exist and work. "Close enough" is a failure.
2. **MariaDB / MySQL only.** WordPress's native database, accessed only through `$wpdb`. No PostgreSQL, no external DB service, no ORM, no second datastore. It must install and run on Hostinger shared/Premium hosting, whose database is **MariaDB** — see §3.6.
3. **ALL media — images, video, logos, favicon — is stored on the hosting disk.** This is a settled product decision, not a default to be argued with. A **pluggable storage driver** (§6) still exists so S3/R2/Supabase/Bunny can be switched on later via one setting, but **driver 3 (Local) is what ships and what must work flawlessly**, including video.
4. **Real test coverage** — unit, integration, and end-to-end. See §14.
5. **Security is a first-class requirement**, not a final pass. See §13.
6. **Mobile must look as premium as desktop.** Hard acceptance criterion.
7. Every phase ends with a passing gate, a commit, and a push.

### Target environment

| | |
|---|---|
| Host | Hostinger shared/Premium hosting (LiteSpeed + PHP) |
| PHP | 8.1+ |
| Database | **MariaDB 10.6+ (what Hostinger actually runs) — the only database.** MySQL 8 compatibility is a bonus, not a target. No external DB service, no PostgreSQL. See §3.6 for type mapping and shared-hosting privilege limits. |
| WordPress | 6.4+ |
| Disk | 20 GB — **all images and video live here** |
| Constraints | 1 CPU core · 2 GB RAM · 40 PHP workers · 400,000 inodes · unlimited bandwidth |

> The **40 PHP workers** and **1 CPU core** are the numbers that matter most for the video decision. See §6.1 — video must never be streamed through PHP.

---

## 1. Stack decision

| Layer | Choice | Why |
|---|---|---|
| CMS | **WordPress 6.4+** | Runs on the client's existing shared hosting; no Node runtime required. |
| Custom code home | **One custom plugin** (`maapkathi-core`) + **one custom theme** (`maapkathi-theme`) | Plugin owns data & logic (survives theme changes). Theme owns presentation only. |
| Custom fields | **ACF Pro** (~$49/yr) | Repeaters and options pages are required and unavailable in the free tier. |
| Templating | **Plain PHP templates** | No page builder. Page builders cannot reproduce the design system and add bloat. |
| CSS | **Hand-written CSS with custom properties** | The theme engine sets CSS variables at runtime — exactly as the source app does. |
| JS | **Vanilla ES modules**, no framework | The motion engine is ~300 lines of IntersectionObserver + CSS. React is not needed. |
| SEO | **RankMath** (free tier) | Sitemaps, meta, breadcrumbs. Custom JSON-LD still hand-written (§12). |
| Forms | **Custom** (not a plugin) | The source app stores inquiries in its own table with a read/unread inbox. Replicate it. |
| Revisions/approval | **Custom** (not PublishPress) | The source app has specific approval semantics (§8). A generic plugin won't match. |
| Unit tests | **PHPUnit + Brain Monkey** | Standard for WP plugin logic. |
| Integration tests | **WP PHPUnit test suite** (`wp-env`) | Real WP + real MySQL. |
| E2E | **Playwright** | Same tool the source app uses. |
| Lint | **PHP_CodeSniffer** with **WordPress Coding Standards** | Enforced in CI. |
| Static analysis | **PHPStan** (level 6+) with `szepeviktor/phpstan-wordpress` | Catches real bugs before runtime. |
| Local dev | **`wp-env`** (Docker) or **LocalWP** | Reproducible. |
| CI | **GitHub Actions** | Runs lint + PHPStan + PHPUnit + Playwright on every push. |

### Decisions to surface before Phase 1

> **Agent: raise these with the user, then proceed with the stated default if there is no answer.**

- **DEC-1 — ACF Pro licence.** Required. Repeater fields and options pages are used throughout. Default: assume the user purchases it. Free alternatives (Meta Box, Carbon Fields) are acceptable substitutes if the user prefers — but pick one and use it consistently.
- **DEC-2 — Video on hosting disk. DECIDED — do not re-litigate.** All video is stored on and served from the 20 GB hosting disk. `MK_VIDEO_DRIVER=0` (same as storage) and `MK_STORAGE_DRIVER=3` (local). This is the product owner's final decision. **Your job is to make it work well, not to talk them out of it.** That means §6.1 is mandatory, not optional: direct web-server delivery, HTTP Range support, faststart MP4, chunked upload. Record the operational caveats (host ToS on streaming, shared-disk throughput) once in `docs/DEPLOYMENT.md` under "Operational limits" — then build it properly.
- **DEC-3 — Multi-tenancy.** The source app supports `DEPLOYMENT_MODE=2` (multi-tenant SaaS) but the production deployment runs `DEPLOYMENT_MODE=1` (single tenant). **This rebuild is single-tenant only.** WordPress Multisite is out of scope. Confirm the user does not need SaaS mode.
- **DEC-4 — Licensing.** The source app has an Ed25519 licence system, disabled in production (`LICENCE_MODE=1`). **Out of scope** unless the user says otherwise.

---

## 2. Repository layout

```
maapkathi-wordpress/
├── .github/workflows/ci.yml
├── .wp-env.json                        # local dev environment
├── composer.json                       # PHPUnit, PHPStan, PHPCS, WPCS
├── package.json                        # Playwright, build tooling
├── phpcs.xml.dist
├── phpstan.neon.dist
├── playwright.config.ts
├── BUILD_INSTRUCTIONS.md               # this file
├── README.md
├── docs/
│   ├── ARCHITECTURE.md
│   ├── CONFIGURATION.md
│   ├── DEPLOYMENT.md
│   ├── TESTING.md
│   ├── THEMING.md
│   └── CLIENT_HANDOVER.md
├── plugins/
│   └── maapkathi-core/
│       ├── maapkathi-core.php          # plugin bootstrap
│       ├── src/
│       │   ├── Config/                 # driver codes, constants, registries
│       │   ├── PostTypes/              # CPT + taxonomy registration
│       │   ├── Fields/                 # ACF field group definitions (PHP, version-controlled)
│       │   ├── Storage/                # storage abstraction (§6)
│       │   │   ├── StorageFactory.php
│       │   │   └── Adapters/           # Local, S3, R2, Supabase, Bunny
│       │   ├── Roles/                  # capabilities, verification workflow
│       │   ├── Approval/               # revision/approval queue (§8)
│       │   ├── Theme/                  # accent/pattern/font registries, CSS var builder
│       │   ├── Admin/                  # admin screens (§9)
│       │   ├── Inquiries/              # contact form handling + inbox
│       │   ├── Seo/                    # JSON-LD, meta
│       │   ├── Rest/                   # REST endpoints (upload, etc.)
│       │   └── Support/                # sanitizers, validators, helpers
│       └── assets/
│           ├── admin.css / admin.js
│           └── patterns/               # 22 SVG background patterns
└── themes/
    └── maapkathi-theme/
        ├── style.css
        ├── functions.php
        ├── index.php
        ├── front-page.php
        ├── page-*.php                  # about, contact, services, team, work
        ├── single-mk_project.php
        ├── single-mk_service.php
        ├── single.php                  # blog post
        ├── archive-mk_project.php
        ├── parts/                      # header, footer, hero, sections
        └── assets/
            ├── css/                    # base, tokens, sections, motion
            └── js/                     # motion engine, lightbox, carousel
```

### Architectural rule

**The plugin owns all data and business logic. The theme only renders.**

The theme may call plugin functions but must never write to the database, register post types, or define fields. If the theme is swapped, all content and settings survive. Enforce this in code review.

---

## 3. Complete feature inventory (the acceptance checklist)

This is the source of truth for "not a single feature missing." Every row must be checked off before the build is complete.

### 3.1 Content types — mapped from the source app's 23 tables

| Source table | WordPress implementation | Notes |
|---|---|---|
| `tenants` | **Omitted** | Single-tenant only (DEC-3). |
| `licences` | **Omitted** | Out of scope (DEC-4). |
| `users` | Native `wp_users` + custom roles | See §7. Fields: email, role, name, is_active, must_change_password, last_login_at. |
| `categories` | Taxonomy `mk_project_category` | name, slug, sort_order (term meta). |
| `projects` | CPT `mk_project` | slug, title, summary, body, category, status, is_featured, sort_order, completed_at, location, client_name, area_sqft, cover image, created_by, reviewed_by, reviewed_at. |
| `media_assets` | Native Media Library + post meta | type, storage_driver, storage_key, url, width, height, duration_s, blurhash, alt_text, is_decorative, sort_order, status. See §6. |
| `members` | CPT `mk_member` | name, role_title, bio, photo, socials (repeater), sort_order, status. |
| `services` | CPT `mk_service` (hierarchical) | title, slug, icon, cover, excerpt, body, parent, sort_order, status. Parent/child = category/sub-service. |
| `posts` | Native `post` | title, slug, excerpt, body, cover, author, tags, published_at, status. |
| `hero_slides` | ACF repeater on Theme Options | eyebrow, headline, kicker_word, body, **media group (§6.3 — kind: image \| gif \| video upload \| video link)**, poster, hold_until_video_ends, cta_label, cta_href, sort_order, is_active. Plus a `hero_slide_duration` option (3–20s, default 6) on the Hero screen. |
| `testimonials` | CPT `mk_testimonial` | author_name, author_role, company, quote, avatar, rating, sort_order, status. |
| `clients` | CPT `mk_client` | name, logo, website, sort_order, is_featured. |
| `awards` | CPT `mk_award` | title, issuer, year, media, link, sort_order. |
| `faqs` | CPT `mk_faq` | question, answer, group, sort_order, status. |
| `values` | CPT `mk_value` | title, body, icon, sort_order. |
| `stats` | CPT `mk_stat` | label, value_number, suffix, sort_order. |
| `process_steps` | CPT `mk_process_step` | step_no, title, body, icon, sort_order. |
| `site_settings` | ACF options page | studio_name, tagline, logo_light, logo_dark, favicon, contact_email, contact_phone, whatsapp, address, socials, nav_config, footer, editor_verification_required, blog_enabled, vision_mission_enabled, vision_text, mission_text, logo_show_title, show_admin_shield, copy (29 fields), media_ratios. |
| `theme_settings` | ACF options page | All 31 settings — see §10 and §11. |
| `seo_settings` | ACF options page + RankMath | default_title, default_description, og_image, ga_id, gtm_id, meta_pixel_id, robots. |
| `inquiries` | Custom table `{prefix}mk_inquiries` | name, email, phone, message, source, is_read. Custom admin inbox. |
| `audit_log` | Custom table `{prefix}mk_audit_log` | actor_id, action, entity, entity_id, diff (JSON). |
| `revisions` | Custom table `{prefix}mk_revisions` | entity, entity_id, payload (JSON), status, submitted_by, reviewed_by, reviewed_at, note. See §8. |

> **Three custom tables only** (`mk_inquiries`, `mk_audit_log`, `mk_revisions`). Everything else uses native WordPress storage. Create them with `dbDelta()` on plugin activation, versioned with a `mk_db_version` option so upgrades are safe and idempotent.

### 3.2 Public pages (10)

| Route | Template | Content |
|---|---|---|
| `/` | `front-page.php` | Hero carousel, tagline note, client logo wall, portfolio categories, featured projects, services, stats band, values, team, testimonials, awards, FAQ accordion, closing CTA band. |
| `/work` | `archive-mk_project.php` | All published projects, grid. |
| `/work/{slug}` | `single-mk_project.php` | Cover hero, summary, body, spec sidebar (client/location/area/year/category), gallery with lightbox, JSON-LD. |
| `/services` | `page-services.php` | Categories with sub-service cards. |
| `/services/{slug}` | `single-mk_service.php` | Cover banner, body, gallery. |
| `/about` | `page-about.php` | Studio name, tagline, vision & mission (toggleable), stats, values, awards, team preview. |
| `/team` | `page-team.php` | All published members with photo, role, bio. |
| `/blog` | `home.php` | Post grid (only when blog is enabled). |
| `/blog/{slug}` | `single.php` | Post detail. |
| `/contact` | `page-contact.php` | Contact details (email/phone/address), contact form. |

### 3.3 Admin screens (17)

Every one of these exists in the source app and must exist here.

| # | Screen | Purpose |
|---|---|---|
| 1 | Dashboard | Counts, recent activity, pending approvals. |
| 2 | Projects (list) | Sortable, filterable, status badges. |
| 3 | Project editor | All fields + cover + gallery reorder. |
| 4 | New project | Same form, create mode. |
| 5 | Services (list) | Hierarchical list. |
| 6 | Service editor | Fields + icon + cover + gallery. |
| 7 | Content | Unified manager for clients, testimonials, awards, stats, values, FAQs, categories. |
| 8 | Blog (list) | Posts list. |
| 9 | Blog editor | Post fields + cover. |
| 10 | Team | Members manager with photo upload + reorder. |
| 11 | Hero | Carousel slide manager (add/edit/reorder/toggle). Each slide takes **an image, a GIF, an uploaded MP4, or a video link** (§6.3), with the length warning and per-slide hold toggle. Global slide-duration setting lives here. |
| 12 | Approvals | Pending revisions queue — approve/reject with note. |
| 13 | Users | Invite editor, toggle active, change role. |
| 14 | Appearance | Two tabs. **Theme** — the 14 appearance settings (mode, accent, custom hex, pattern, opacity, font pair, per-area overrides, heading/body colour, radius, density, grain, glass, hero style). **Motion** — the 16 motion controls. **All 31 `theme_settings` columns exposed and working — see §11, this is a hard requirement.** |
| 15 | **Site Text** | All 29 editable copy strings, grouped by page/section. |
| 16 | Settings | Studio name, tagline, contact, socials, SEO, logos, favicon, blog toggle, vision/mission, verification toggle, admin-shield toggle. |
| 17 | Account | Own profile, change password. |

### 3.4 Feature-level checklist

- [ ] Dark / light / system mode with no flash of wrong theme (inline script before paint)
- [ ] 24 accent colours (§10)
- [ ] 22 background patterns with opacity control (§10)
- [ ] 8 font pairings + per-area font & colour overrides (headings, body, nav, buttons, hero, accents)
- [ ] Radius scale, density scale
- [ ] **All 31 `theme_settings` exposed as working admin controls (§11)** — 14 appearance + 17 motion, every one of them visibly changing the rendered site
- [ ] `prefers-reduced-motion` respected everywhere
- [ ] Admin ↔ Editor roles with verification toggle gating editor content (§8)
- [ ] Approval queue for edits to already-published items
- [ ] Audit log of all content changes
- [ ] Media upload with magic-byte type validation (never trust extension)
- [ ] Image derivatives + LQIP/blurhash placeholders
- [ ] Pluggable storage driver by numeric code (§6), shipping on driver 3 (Local)
- [ ] **Video stored and served from the hosting disk** — direct static URL, HTTP 206 Range support, faststart MP4 (§6.1)
- [ ] **Chunked upload** for files exceeding PHP POST limits, with progress and resume
- [ ] **Disk + inode usage** visible on the admin Dashboard with a warning band
- [ ] **Hero slides accept image, animated GIF, uploaded MP4, or video link** (§6.3) — one kind per slide
- [ ] **Hero uploader shows the slide-length note** and warns above 10s, with a per-slide "hold until video ends" toggle and an admin-editable slide duration
- [ ] **Reduced-motion GIF handling** — first frame only, never a looping animation
- [ ] **Per-video source choice — Upload or Link** (§6.2), available on hero slides, project galleries, and service galleries
- [ ] **External links play**: YouTube, Vimeo, and direct `.mp4`/`.webm` URLs all render and play correctly
- [ ] **All three sources autoplay muted and loop** — uploaded files, direct links, YouTube, and Vimeo
- [ ] Invalid/unsupported video URLs rejected at save with a clear inline error
- [ ] Poster image renders for every video, so a browser-refused autoplay degrades to a still frame
- [ ] Admin-configurable favicon with fallback chain (favicon → light logo → dark logo → default mark)
- [ ] Admin-toggleable admin-login shield in the public header, with copyable `/login` URL when hidden
- [ ] Site-wide footer with nav, NAP contact info, socials, Organization JSON-LD
- [ ] Contact form → inquiries inbox with read/unread
- [ ] SEO: dynamic meta, OG/Twitter cards, canonicals, sitemap, robots
- [ ] JSON-LD: Organization, FAQPage, CreativeWork (projects), BreadcrumbList
- [ ] Blog can be fully disabled (hides nav item and 404s the routes)
- [ ] Vision & Mission section toggleable on About
- [ ] Mobile parity — every page verified at 375px with zero horizontal overflow
- [ ] **Every item in §3.5** (visitor theme toggle, lightbox keyboard + swipe, default logo mark, light/dark logo swap, "Call us" button, mobile overlay menu, placeholder generator, health endpoint, media-ratio hints, custom nav)

---

### 3.5 Component-level details that are easy to miss

These exist in the source app and are individually small — which is exactly why they get dropped in a rebuild. Each is a required behaviour.

| Behaviour | Source | Requirement |
|---|---|---|
| **Light/dark toggle in the public header** | `ThemeToggle.tsx` | A visible control in the site header letting **visitors** switch light/dark, independent of the admin's `mode` setting. Persists across pages (cookie + `localStorage`) and must not flash on load. `mode: 'system'` follows the OS until the visitor overrides it. |
| **Lightbox keyboard + touch** | `Lightbox.tsx` | Project gallery lightbox supports `Escape` to close, `←`/`→` to navigate, and **swipe gestures** on touch. Focus trapped while open; focus restored to the trigger on close. |
| **Default logo mark** | `Logo.tsx` | When no logo image is uploaded, render the built-in wordmark/mark — never an empty space or a broken image. Respects `logo_show_title`. |
| **Light/dark logo swap** | `SiteHeader.tsx` | Two logo uploads. The correct one shows per active theme, switched in **CSS** (not JS) so there is no flash on load. |
| **"Call us" button** | `SiteHeader.tsx` | When a contact phone is set, the header shows an accent-filled `tel:` button on desktop. Hidden on mobile (the mobile menu carries it). |
| **Mobile overlay menu** | `SiteHeader.tsx` | Full-screen overlay with staggered link reveals, ≥44px tap targets, closes on navigation. |
| **Placeholder image generator** | `/api/placeholder/[...spec]` | An endpoint generating on-the-fly gradient SVG placeholders with a text label, sized to request (e.g. `/2400/1350?label=Hero`). Used by seed/demo content so a fresh install is never full of broken images. Reimplement as a WP endpoint or bundle equivalent static assets. |
| **Health check** | `/api/health` | A lightweight endpoint returning app + DB status, for uptime monitoring. Must not require auth and must not leak version or config detail. |
| **Media aspect-ratio hints** | `site_settings.media_ratios` | Admin-set expected ratios (`heroImage` 16:9, `heroVideo` 16:9, `projectCover` 4:5, `projectGallery` 4:5, `memberPhoto` 3:4, `ogImage` 1.91:1) surfaced as **helper text in each upload field**, so admins upload correctly-shaped images. |
| **Custom navigation** | `site_settings.nav_config` | Admin can override the default nav. When unset, nav is built automatically and **includes Blog only when the blog is enabled**. |
| **Path-traversal-safe media serving** | `/uploads/[...path]` | The source app resolves and normalises paths to block `../` escapes. In WordPress uploads are served statically by the web server (§6.1), so this route is superseded — **but if any PHP ever reads a user-supplied media path, the same protection is mandatory.** |

---

### 3.6 Database — MariaDB / MySQL only

**Hostinger shared hosting runs MariaDB.** The build must work on stock MariaDB 10.6+ with no extensions, no superuser rights, and no external database service. Target MariaDB first; anything that also works on MySQL 8 is a bonus, not a requirement.

**Use WordPress's own database layer, never a direct connection.** All access goes through `$wpdb` with `prepare()`. No PDO, no `mysqli_connect()`, no ORM, no connection string in the plugin. WordPress already holds the credentials from `wp-config.php`, which is what hPanel's "MySQL Databases" screen generates.

#### Type mapping — the source app's Postgres types have no direct equivalents

This is where a naive port breaks. The source schema is PostgreSQL; **every one of these needs a deliberate translation**, and only the three custom tables (`mk_inquiries`, `mk_audit_log`, `mk_revisions`) are affected — everything else is native WP storage.

| Source (Postgres) | MariaDB | Why / gotcha |
|---|---|---|
| `uuid` PK | `BIGINT UNSIGNED AUTO_INCREMENT` | MariaDB's native `UUID` type is 10.7+; Hostinger may be on 10.6 or 10.11 — don't depend on it. WP is integer-ID-native anyway. If a UUID is genuinely needed, `CHAR(36)` with `utf8mb4_bin` collation. |
| `jsonb` | `LONGTEXT` | **MariaDB's `JSON` is an alias for `LONGTEXT` with a validity CHECK — it is not MySQL 8's binary JSON.** No native JSON indexing, and JSON path functions differ. Encode with `wp_json_encode()`, decode with `json_decode()`, do all filtering **in PHP**. Never write SQL that queries inside a JSON column. |
| `timestamptz` | `DATETIME` storing **UTC** | MariaDB `DATETIME` carries no timezone. Follow WP's own convention (`post_date_gmt`) — store UTC, convert for display with `wp_date()`. |
| `boolean` | `TINYINT(1)` | 0/1. Cast explicitly in PHP; `$wpdb` returns strings. |
| `text` (unlimited) | `TEXT` / `LONGTEXT` | MariaDB `TEXT` caps at 65,535 **bytes** — under utf8mb4 that's ~16k characters. Use `LONGTEXT` for project bodies and revision payloads. |
| `text` used in an index | `VARCHAR(191)` | See the index-length rule below. |
| `smallint` | `SMALLINT` | Direct. |
| `numeric` | `DECIMAL(10,2)` | Never `FLOAT` for anything counted or displayed. |
| Array columns | Separate rows, or a JSON `LONGTEXT` | MariaDB has no array type. |
| Partial / expression indexes | **Not available** | Plain `KEY` only. Filter in PHP if needed. |
| `tsvector` full-text | `FULLTEXT` index, or `LIKE` | A portfolio site's data volume doesn't justify FULLTEXT — `LIKE` is fine and simpler. |

#### Schema creation rules

- **`dbDelta()` only** for the three custom tables, called on activation and on version bump. Version it with an `mk_db_version` option and run upgrades idempotently.
- `dbDelta()` is famously picky. It will silently re-run `ALTER`s forever unless you match its expected format: **two spaces** after `PRIMARY KEY`, the keyword `KEY` (not `INDEX`), lowercase type names, one field per line, and a field type spelled exactly as MariaDB reports it back.
- Always append `$wpdb->get_charset_collate()` — never hardcode a charset. Hostinger's default is `utf8mb4_general_ci`.
- **Index key length:** under `utf8mb4` each character reserves 4 bytes, so an indexed `VARCHAR(255)` needs 1020 bytes. Older InnoDB configs cap an index key at 767. **Index-bearing string columns must be `VARCHAR(191)`** — this is exactly why WordPress core uses 191 everywhere. Getting this wrong produces "Specified key was too long" on activation.
- **Do not use `ALTER TABLE … ADD COLUMN IF NOT EXISTS`.** It's a MariaDB extension that MySQL 8 rejects outright — and `dbDelta()` already handles add-if-missing correctly. This is the direct MariaDB equivalent of the idempotency lesson the source project learned the hard way; solve it with `dbDelta()`, not vendor-specific SQL.
- No foreign keys with `ON DELETE CASCADE` across WP core tables — WP core doesn't use FKs and some hosts run tables that can't take them. Enforce referential integrity in PHP.

#### Privileges you will not have on shared hosting

Assume all of these are unavailable and never write code that needs them:

- `CREATE DATABASE` / `DROP DATABASE` — the database is created in **hPanel → Databases → Management** before install
- `SET GLOBAL`, `SUPER`, any server-variable tuning
- Stored procedures, triggers, and events (`DEFINER` clauses fail without SUPER)
- `LOAD DATA INFILE`
- Remote/external MySQL connections — **off by default**; the app connects over `localhost`

Also budget for a **`max_user_connections` cap**. Long-running or parallel DB work will hit it. Keep queries short, never hold a connection across a long file operation, and do heavy seeding through WP-CLI rather than a web request.

#### Query discipline

- Every query through `$wpdb->prepare()` — **zero string interpolation** (§13).
- Use `$wpdb->prefix`, never a hardcoded `wp_`; Hostinger installs often use a randomised prefix.
- Index the columns actually filtered on: `mk_inquiries.is_read`, `mk_inquiries.created_at`, `mk_revisions.status`, `mk_revisions.entity` + `entity_id`, `mk_audit_log.created_at`.
- Avoid `SELECT *` on the revisions table — the `payload` LONGTEXT will dominate the result set.
- Beware WP's own `meta_query` cost on 1 CPU core. For the portfolio's ordered lists, prefer a `menu_order`/`post_date` sort over meta sorting where possible.

#### Verification

- [ ] Integration tests run against **real MariaDB**, not SQLite and not a mock. Pin the `wp-env` / CI service image to `mariadb:10.6` so CI matches Hostinger rather than a newer MySQL that would hide incompatibilities.
- [ ] A test activates the plugin on an empty database, then activates it **again**, and asserts `dbDelta()` produced no second round of `ALTER`s and no duplicate columns.
- [ ] A test asserts every custom table is created with the `utf8mb4` collation from `get_charset_collate()`.
- [ ] `docs/DEPLOYMENT.md` documents the exact hPanel path to create the database and where the credentials go in `wp-config.php`.

---

## 4. Naming conventions

- **Prefix everything `mk_`** — post types, taxonomies, meta keys, options, functions, tables, hooks.
- PHP namespace: `Maapkathi\Core\`.
- CSS custom properties keep the source app's names exactly: `--accent`, `--accent-foreground`, `--background`, `--font-headings`, `--font-body`, `--font-nav`, `--font-hero`, `--radius`, `--motion-duration`, `--motion-ease`, `--motion-stagger`. **Do not rename them** — the ported JS and CSS depend on them.
- Text domain: `maapkathi`.

---

## 5. Configuration layer

Mirror the source app's numeric-code config. Store in `wp-config.php` constants (with sane defaults in code), so it is deployable without touching the database.

```php
// wp-config.php
define( 'MK_STORAGE_DRIVER', 3 );   // 1 S3 · 2 Google Drive · 3 Local (SHIPPED) · 4 R2 · 5 Supabase · 6 Bunny
define( 'MK_VIDEO_DRIVER', 0 );     // 0 same-as-storage (SHIPPED) · 1 Bunny Stream · 2 Cloudflare Stream · 3 Mux · 4 external embed
                                    // NOTE: this is only the default for UPLOADED video. Admins choose
                                    // upload-vs-link per video in the editor (§6.2); external links always work.
define( 'MK_CACHE_DRIVER', 1 );     // 1 transients · 2 Redis (object-cache.php)
define( 'MK_MAIL_DRIVER', 0 );      // 0 disabled (inbox only) · 1 SMTP · 2 external API
define( 'MK_LOCAL_STORAGE_DIR', WP_CONTENT_DIR . '/uploads/maapkathi' );

// Video limits — tuned for shared hosting. Enforced server-side.
define( 'MK_MAX_VIDEO_BYTES', 200 * 1024 * 1024 );  // 200 MB per file
define( 'MK_MAX_IMAGE_BYTES', 10 * 1024 * 1024 );   // 10 MB per file
define( 'MK_MAX_GIF_BYTES', 8 * 1024 * 1024 );      // 8 MB per animated GIF (see §6.3)
define( 'MK_CHUNK_BYTES', 2 * 1024 * 1024 );        // 2 MB upload chunks (see §6.1)
define( 'MK_HERO_SLIDE_SECONDS', 6 );               // default carousel interval; admin-editable 3–20
define( 'MK_MAX_HERO_HOLD_SECONDS', 20 );           // ceiling for "hold slide until video ends"
```

Build a `Maapkathi\Core\Config\Config` class that reads these constants, validates them, and throws a readable admin notice if a driver is selected without its required credentials. **Fail loudly at boot, not silently at runtime.**

---

## 6. Storage abstraction

**Requirement:** images and video default to the hosting disk, but the driver must be swappable by changing one constant.

```php
interface StorageAdapter {
    public function put( string $key, string $tmp_path, string $mime, string $visibility ): StoredObject;
    public function delete( string $key ): void;
    public function url( string $key ): string;
    public function exists( string $key ): bool;
    public function driver_code(): int;
}
```

`StorageFactory::create()` reads `MK_STORAGE_DRIVER` and returns the matching adapter.

**Adapters to implement:**

| Code | Adapter | Notes |
|---|---|---|
| 1 | `S3StorageAdapter` | AWS SDK or signed REST. |
| 2 | `GoogleDriveStorageAdapter` | Service account. Archival only — warn about streaming. |
| **3** | **`LocalStorageAdapter`** | **Default.** Writes under `MK_LOCAL_STORAGE_DIR`. Must handle Hostinger's path structure. |
| 4 | `R2StorageAdapter` | S3-compatible; reuse the S3 adapter with a custom endpoint. |
| 5 | `SupabaseStorageAdapter` | REST with `Authorization: Bearer` + `apikey` headers. |
| 6 | `BunnyStorageAdapter` | REST with `AccessKey` header. |

**Every adapter must pass the same contract test suite** (`tests/Integration/Storage/StorageContractTest.php`). Write the contract once, run it against each adapter. Local runs always; remote drivers run behind env flags.

**Local adapter specifics:**
- Store under `uploads/maapkathi/{yyyy}/{mm}/{uuid}.{ext}`
- Write an `.htaccess` in the media dir denying execution of PHP (`php_flag engine off` / `<FilesMatch "\.ph(p[0-9]?|tml)$"> Deny from all </FilesMatch>`) — **this is a hard security requirement**
- Integrate with the WP Media Library so uploads appear normally in wp-admin

---

### 6.1 Serving video from the hosting disk — mandatory requirements

Storing video locally on shared hosting works, but only if built correctly. Get any of these wrong and the site falls over under normal traffic. **All five are required.**

**1. Never stream video through PHP.**
`LocalStorageAdapter::url()` must return a **direct URL to the file** (e.g. `https://maapkathi.com/wp-content/uploads/maapkathi/2026/07/abc.mp4`), served by LiteSpeed as a static file. It must **never** return a PHP endpoint like `/?mk_media=abc` that reads the file with `readfile()`.

*Why this is critical:* the plan has **40 PHP workers**. A PHP-streamed video occupies one worker for the entire duration of playback. Ten people watching a 2-minute video = 10 workers tied up for 2 minutes. The site stops responding. Static delivery costs zero workers.

**2. HTTP Range requests must work (HTTP 206).**
Browsers seek video with `Range:` headers. LiteSpeed handles this natively for static files — which is another reason rule 1 is non-negotiable. If a future adapter proxies bytes, it **must** implement `Range` / `206 Partial Content` / `Accept-Ranges: bytes` correctly.
*Test:* `curl -H "Range: bytes=0-1023" <video-url> -o /dev/null -w "%{http_code}"` must return **206**, not 200.

**3. MP4 files must be "faststart" (moov atom at the front).**
Otherwise the browser downloads the entire file before the first frame plays. On upload, run `ffmpeg -i in.mp4 -c copy -movflags +faststart out.mp4` if ffmpeg is available; if not, **detect a non-faststart MP4 and warn the admin in the upload UI** with instructions. Never silently accept a file that will appear broken.

**4. Chunked upload is required.**
Shared hosting caps `upload_max_filesize` / `post_max_size` (typically 64–128 MB) and `max_execution_time`. A 200 MB video **cannot** be uploaded in one POST.
Implement chunked upload in the admin: slice the file client-side into `MK_CHUNK_BYTES` pieces, POST each to a REST endpoint, reassemble server-side, then validate the completed file. Show real progress. Resume on failure if feasible.
Document the SFTP fallback in `docs/DEPLOYMENT.md` for very large files.

**5. Enforce limits and quota server-side.**
- Reject video over `MK_MAX_VIDEO_BYTES`, images over `MK_MAX_IMAGE_BYTES` — checked **after** reassembly, not just client-side
- Surface **disk usage** on the admin Dashboard (used / 20 GB, with a warning band past 80%)
- Surface **inode count** — the plan allows 400,000, and image derivatives multiply file counts fast
- Refuse new uploads past a configurable ceiling rather than letting the account hit a hard host limit

**Recommended admin guidance** (put this in the upload UI, not buried in docs): H.264 MP4, ≤1080p, ~2–4 Mbps. **Hero videos should be ~6 seconds** — see §6.3, the carousel auto-advances and longer clips get cut off. Project/service gallery videos can run to ~2 minutes. A 60-second 1080p clip at 3 Mbps is ~22 MB; a 6-second hero loop is ~2–3 MB. At those sizes 20 GB holds hundreds of clips comfortably.

---

### 6.2 Video source: upload **or** external link (per video, admin's choice)

**Requirement:** everywhere a video can be attached, the admin picks one of two sources. Both must fully work.

| Source | Admin does | App plays |
|---|---|---|
| **Upload** | Uploads a file → stored on the 20 GB disk via `LocalStorageAdapter` (§6.1) | Native `<video>` from a direct static URL |
| **Link** | Pastes a URL to a video hosted elsewhere | YouTube/Vimeo embed, or native `<video>` for a direct file URL |

This is a **per-field choice, not a global setting.** One hero slide can use an uploaded file while another uses a YouTube link.

#### Field structure

Every video attachment point uses an ACF group with conditional logic:

```
video_source   radio        'upload' (default) | 'link'
video_upload   file         shown when source = upload   → attachment ID
video_url      url          shown when source = link     → external URL
video_poster   image        optional for both — poster/first frame
```

Applies to: **hero slides** (`video_media_id` in the source app), **project galleries**, and **service galleries**.

#### Accepted link types

| Type | Example | Playback |
|---|---|---|
| YouTube | `youtube.com/watch?v=ID`, `youtu.be/ID`, `youtube.com/embed/ID` | Privacy-enhanced iframe (`youtube-nocookie.com`) |
| Vimeo | `vimeo.com/ID`, `player.vimeo.com/video/ID` | Vimeo player iframe |
| Direct file | any `https://…/file.mp4` or `.webm` | Native `<video>` — works for Bunny, Cloudflare R2, S3, any CDN |

Anything else → **reject at save time with a clear inline error** naming what is supported. Never save an unvalidated URL.

#### `VideoResolver` — one service, one shape

Build `Maapkathi\Core\Video\VideoResolver` that takes the field group and returns a normalised descriptor the templates consume:

```php
final class ResolvedVideo {
    public string $kind;        // 'file' | 'youtube' | 'vimeo'
    public string $src;         // direct file URL, or a rebuilt embed URL (autoplay params baked in)
    public ?string $poster;     // poster image URL
    public bool $is_background; // true when used as a hero background (drives embed params)
}
```

Templates render from `ResolvedVideo` only. **No template ever inspects raw admin input.** One resolver, tested once, used everywhere.

#### Autoplay — required for ALL three sources

**Every video source autoplays, muted and looping.** Uploaded files, direct links, YouTube, and Vimeo alike. This is a product requirement, not a per-kind capability.

Muted autoplay is permitted by browsers and supported by both platforms — it just needs the right parameters. Build the embed URLs exactly like this:

**YouTube** — construct from the extracted ID:
```
https://www.youtube-nocookie.com/embed/{ID}
  ?autoplay=1&mute=1&loop=1&playlist={ID}
  &controls=0&playsinline=1&rel=0&modestbranding=1
  &iv_load_policy=3&disablekb=1&fs=0
```
`mute=1` is what makes autoplay legal. **`loop=1` does nothing without `playlist={ID}` repeating the same ID** — this is a real YouTube API quirk and the most common way background loops silently fail.

**Vimeo** — construct from the extracted ID:
```
https://player.vimeo.com/video/{ID}
  ?autoplay=1&muted=1&loop=1&background=1
```
`background=1` is Vimeo's purpose-built background mode: autoplays, loops, mutes, and strips all controls and chrome in one flag. Prefer it over setting the flags individually.

**File** (uploaded or direct link):
```html
<video autoplay muted loop playsinline preload="metadata" poster="…">
```
`muted` **must** be present in the markup for autoplay to be allowed, and `playsinline` is required or iOS opens fullscreen.

#### The performance trade-off — state it, don't hide it

Autoplaying embeds means the YouTube/Vimeo iframe must load on page load. A click-to-play facade is impossible when the video is meant to be already playing. So:

- **Uploaded and direct-link files:** no third-party cost. Best performance. Recommend these in the admin UI for hero backgrounds.
- **YouTube/Vimeo:** ~500 KB+ of third-party JS on the critical path, and it will measurably affect LCP.

Mitigate, don't block:
- `loading="lazy"` on any embed **below the fold**; hero embeds load eagerly since they must play immediately
- `<link rel="preconnect">` to the player origin when an embed is present on the page
- Always set a `poster`/placeholder image behind the embed so something renders instantly while the player boots
- Note the trade-off inline in the admin video field — one sentence, so the admin can choose knowingly

#### Playback rules

- All three kinds autoplay muted and loop, using the URLs/attributes above
- Embeds are built from the **extracted ID**, never the raw pasted URL (§13)
- Add `referrerpolicy="strict-origin-when-cross-origin"` and a `title` on every iframe for a11y
- **`prefers-reduced-motion: reduce` disables autoplay for every kind, always** — this overrides the requirement above. Show the poster with a play button instead.
- Autoplay can still be refused by the browser (low-power mode, data saver, aggressive privacy settings). This is outside our control — so the `poster` image is **mandatory**, not optional. A refused autoplay must degrade to a still frame, never a black box.

---

### 6.3 Hero slide media — image, GIF, or MP4

The source app's hero slide takes an image *and* an optional video. **The WordPress build replaces that with a single media field that accepts four kinds**, one per slide:

| Kind | Accepted | Rendered as | Notes |
|---|---|---|---|
| **Still image** | JPEG, PNG, WebP | `<img>` with the Ken Burns drift (`hero_animation`, §11.1 #18) | The default. Fastest, always works. |
| **Animated GIF** | GIF | `<img>` — no `<video>` element, no autoplay attributes; browsers animate GIFs natively | Ken Burns is **disabled** for GIFs — the drift fights the animation and looks broken |
| **Uploaded video** | MP4 (H.264/AAC) | `<video autoplay muted loop playsinline>` + mandatory poster (§6.2) | Served from local disk via the Range handler (§6.1) |
| **External video link** | YouTube · Vimeo · direct `.mp4` | per §6.2 | Autoplay params applied for all three |

**Field structure** — one radio group (`Image` / `GIF` / `Video upload` / `Video link`) that shows only the relevant sub-fields. A slide has exactly one media kind; picking a new one clears the others so a slide can never carry two conflicting sources.

**Poster image stays required for both video kinds.** If the admin doesn't supply one, generate a still from the first frame on upload; for links, fall back to the provider thumbnail, and if that fails, the accent-colour block the source app uses.

#### ⚠️ The length note the uploader must show

**Hero slides auto-advance every 6 seconds** (`setInterval(..., 6000)` in `src/presentation/components/public/Hero.tsx`). A 30-second video on a rotating hero is never seen past its first 6 seconds. This has to be surfaced *in the upload field*, not in documentation nobody reads.

Required helper text, shown inline whenever `Video upload`, `Video link`, or `GIF` is selected:

> **Keep it to about 6 seconds.** Hero slides change automatically every 6 seconds, so anything longer gets cut off mid-play. Silent, seamless loops work best. Recommended: MP4 (H.264), 1080p, 2–4 Mbps — roughly 2–3 MB for a 6-second clip.

Plus these behaviours, so the note is enforced rather than merely advisory:

1. **Read the duration client-side before upload** (`HTMLVideoElement.duration` after loading metadata) and **warn — do not block — above 10 seconds**: *"This clip is 24s but slides change every 6s. Viewers will only see the first 6 seconds. Trim it, or turn on 'Hold slide until video ends' below."* The admin may proceed knowingly.
2. **Per-slide "Hold slide until video ends" toggle** (default off). When on, that slide's timer uses the video's real duration instead of the global interval. **Cap the hold at `MK_MAX_HERO_HOLD_SECONDS` (default 20)** so one long clip can't strand the carousel.
3. **A global "Slide duration" setting** on the Hero screen, `3–20` seconds, default **6**. The 6-second figure in the helper text must read from this setting, not be hardcoded — if the admin sets 10s, the note says 10s.
4. **Single-slide case:** when there is exactly one hero slide, there is no rotation (the source returns early at `slides.length < 2`), so no length limit applies. Suppress the warning entirely in that case — it would be wrong.

#### GIF-specific rules

GIFs are the one format admins reach for and shouldn't. Handle it honestly rather than silently:

- **Cap GIFs at `MK_MAX_GIF_BYTES` (default 8 MB)** — enforced server-side after upload, magic-byte verified like every other type (§13).
- Over ~5 MB, show: *"This GIF is 12 MB. The same animation as MP4 would be around 1 MB and look better. Convert it if you can — the hero is the first thing every visitor loads."* Warn, don't block.
- GIFs get `loading="eager"` + `fetchpriority="high"` like any hero image, but **never** a `<video>` wrapper — they are images.
- Animated GIFs cannot be paused for `prefers-reduced-motion`. Where the visitor prefers reduced motion, **render only the first frame** — extract it at upload time and store it as the slide's poster, then swap the `src` under the reduced-motion media query. This is the only way to honour the guarantee in §11.3.

#### Validation

- MIME by magic bytes only: `FF D8 FF` (JPEG), `89 50 4E 47` (PNG), `RIFF….WEBP`, `GIF87a`/`GIF89a`, `….ftyp` (MP4). Extension and client `Content-Type` are ignored (§13).
- An MP4 uploaded here goes through the **same** faststart/moov-atom check as any other video (§6.1 rule 3) — reject or remux a file whose moov atom is at the end, or it won't start playing until fully downloaded.
- Reject a GIF uploaded into the video field and vice versa, with a message naming the right field.

---

## 7. Roles & capabilities

Two custom roles, mirroring the source app:

| Role | Capabilities |
|---|---|
| `mk_admin` | Everything: manage settings, appearance, users, approve revisions, publish directly. |
| `mk_editor` | Create/edit content. **Cannot** publish directly when the verification toggle is on. Cannot access Settings, Appearance, Users, or Approvals. |

Register custom capabilities: `mk_manage_settings`, `mk_manage_appearance`, `mk_manage_users`, `mk_approve_revisions`, `mk_publish_content`, `mk_edit_content`.

**Every admin screen must check capability before rendering, and every write action must re-check.** Never rely on hiding a menu item as access control.

Additional requirements ported from the source app:
- Admin bootstrap from constants on first activation (`MK_ADMIN_EMAIL`, `MK_ADMIN_PASSWORD`, `MK_ADMIN_NAME`)
- If the bootstrap password is still the shipped sentinel, force a password change on first login and show a persistent admin notice
- `is_active` flag — deactivated users cannot log in
- Login rate limiting: max 5 attempts per IP per 15 minutes, then exponential backoff

---

## 8. Verification / approval workflow

This is the subtlest feature. Replicate the source app's `ContentVisibilityPolicy` exactly.

**Rules:**

1. Setting `editor_verification_required` (bool, default **true**) lives in Site Settings.
2. When **ON**:
   - An **editor** creating new content → saved as `pending`, invisible on the public site.
   - An **editor** editing an **already-published** item → the live version stays untouched; the edit is stored as a **pending revision** in `mk_revisions` and queued for review. **The public site keeps showing the old version.**
   - An **admin** creating or editing → publishes immediately.
3. When **OFF** → editors publish directly, no queue.
4. Admin's Approvals screen lists pending revisions with a diff view. Approve → apply payload to the live item, stamp `reviewed_by`/`reviewed_at`. Reject → mark rejected with a note.
5. Every approve/reject writes to `mk_audit_log`.

**Write unit tests for this policy first, before implementing the UI.** It is the highest-risk logic in the build. Test matrix: {admin, editor} × {create, edit-published, edit-draft} × {verification on, off} = 12 cases minimum.

---

## 9. Admin panel

Build a **custom top-level admin menu** ("Maapkathi") with the 17 screens from §3.3 — do not scatter them across the default WordPress menus.

Design requirements (match the source app):
- Accent-coloured sidebar panel, content in a bordered card on a tinted background
- Sidebar recolours automatically from the theme accent (uses `--accent` / `--accent-foreground`)
- Logo badge, active-item pill, user footer with sign-out
- "View public site ↗" button
- All tap targets ≥ 44px
- Fully responsive — the admin must be usable on a phone

**Security for every screen:**
- `wp_nonce_field()` on every form; `check_admin_referer()` on every handler
- `current_user_can()` on both render and write
- Sanitize on input (`sanitize_text_field`, `wp_kses_post`, `esc_url_raw`, `absint`)
- Escape on output (`esc_html`, `esc_attr`, `esc_url`) — **always, no exceptions**

---

## 10. Theme engine

Port the source app's registries **verbatim**. Read them from the source repo at `src/presentation/theme/`.

**24 accents** — `obsidian-gold`, `terracotta`, `sage`, `deep-teal`, `midnight-blue`, `burnt-sienna`, `olive`, `plum`, `copper`, `slate`, `sand`, `forest`, `oxblood` *(default)*, `cobalt`, `champagne`, `charcoal-rose`, `emerald`, `amber`, `indigo`, `clay`, `moss`, `bronze`, `ink-violet`, `warm-graphite`, plus a **custom hex** override.

**22 patterns** — `none` *(default)*, `fine-grid`, `blueprint-grid`, `dot-matrix`, `diagonal-hatch`, `cross-hatch`, `isometric`, `subtle-noise`, `topographic`, `concentric-arcs`, `herringbone`, `terrazzo`, `linen-weave`, `marble-veins`, `art-deco-fan`, `moroccan-trellis`, `hexagon-mesh`, `vertical-pinstripe`, `wave-contour`, `blueprint-corner-rules`, `soft-radial-glow`, `mesh-gradient`. Each is an SVG; opacity is admin-controlled (0–100, default 6).

**8 font pairings** — `fraunces-manrope` *(default)*, `cormorant-inter`, `playfair-source`, `instrument-geist`, `libre-inter`, `dmserif-dmsans`, `spectral-work`, `bricolage-manrope`.

**Per-area overrides** — headings, body, nav, buttons, hero, accents. Each area can take its own font and colour, overriding the global pair.

**Other tokens** — `mode` (light/dark/system), `radius`, `density`, `grain`, `glass`, `hero_style`, `heading_color_hex`, `body_color_hex`.

**Implementation:**
1. `ThemeVarsBuilder` turns settings into a CSS custom-property block.
2. Inject it in `<head>` via `wp_head` — **inline, not a separate request**, so there is no flash of unstyled theme.
3. An inline script before paint reads the mode cookie/localStorage and sets `data-theme` on `<html>` — prevents flash of wrong mode.
4. Fonts load from Google Fonts with `display=swap` and preconnect.

---

## 11. Appearance + Motion engine — all 31 settings must be live

### ⚠️ Read this first — do NOT use the source app's Appearance screen as your reference

An audit of the source repo found a gap between what is **stored**, what is **used**, and what is **editable**:

| | Count | Detail |
|---|---|---|
| Columns in `theme_settings` | **31** | All exist in the database with defaults |
| Actually read by `buildThemeVars.ts` | **14** | `accentId`, `customAccentHex`, `patternId`, `patternOpacity`, `fontPairId`, `fontOverrides`, `headingColorHex`, `bodyColorHex`, `radius`, `density`, `motionPreset`, `motionSpeed`, `parallaxIntensity`, `staggerMs` |
| Editable in the Appearance screen (`AppearanceForm.tsx`) | **6** | `mode`, `accentId`, `patternId`, `patternOpacity`, `fontPairId`, `motionPreset` |

Roughly **17 of the 31 settings are dormant in the live source app** — stored, but never editable by an admin, and several never read at all. The Appearance UI simply never caught up with the schema.

> ### 🔒 DECIDED — this is not a decision point
>
> **The WordPress build MUST expose all 31 settings as working admin controls.**
>
> `AppearanceForm.tsx` in the source repo is **incomplete** and must **not** be treated as the specification for the admin UI. The schema (`src/infrastructure/db/schema/settings.ts` → `themeSettings`) is the specification. Build every one of the 31 rows in the table below.
>
> Do **not** ask the user to reconsider this. Do **not** ship a subset. Do **not** hide a setting behind "advanced" and leave it non-functional.
>
> **Every setting exposed in the UI must demonstrably change the rendered site.** A control that does nothing is worse than no control. If you cannot make a setting visibly do something, that is a bug in your implementation, not a reason to drop the control.

This means the WordPress version is intentionally **more capable** than the source app in this one area. That is expected and correct — every other area is a strict 1:1 port.

---

### 11.1 The complete 31 — settings, exact values, and the admin control for each

All 31 columns of `theme_settings`. **Option IDs below are copied verbatim from the source registries** (`src/presentation/theme/motion.ts`, `fonts.ts`, `accents.ts`, `patterns.ts`) — read those files and copy the arrays; do not retype them from this document and do not invent IDs. The CSS and JS key off these exact strings.

Three settings marked **🆕** are stored in the source schema but have **no registry and no renderer anywhere in the source app**. Their option lists are defined *by this document* and you must build both the control and the rendering.

#### Appearance group — 14 settings · admin screen *Appearance → Theme*

| # | Setting | Default | Admin control | Exact values | Renders as |
|---|---|---|---|---|---|
| 1 | `mode` | `system` | Segmented radio, 3 buttons | `light` · `dark` · `system` | `data-theme` on `<html>` + pre-paint script |
| 2 | `accent_id` | `oxblood` | **Swatch grid** — 24 tiles, each filled with its own real colour, selected tile ringed | the 24 IDs in `accents.ts` (§10) | `--accent`, `--accent-foreground` |
| 3 | `custom_accent_hex` | `null` | Colour picker + hex text input + **Clear** button | any hex, or empty | overrides #2 entirely when set |
| 4 | `pattern_id` | `none` | **Thumbnail grid** — 22 tiles each rendering its actual SVG at low opacity | the 22 IDs in `patterns.ts` (§10) | `--pattern-css` on body |
| 5 | `pattern_opacity` | `6` | Slider `0–100`, step 1, live numeric readout | 0–100 | `--pattern-opacity` (value ÷ 100) |
| 6 | `font_pair_id` | `fraunces-manrope` | Select where **each option renders in its own typeface** | the 8 IDs in `fonts.ts` (§10) | `--font-headings` / `--font-body` etc. |
| 7 | `font_overrides` | `{}` | **6 fixed rows** (headings, body, nav, buttons, hero, accents), each = font select + colour picker + **Clear row** | per-area `{fontId, colorHex}` | per-area vars; **wins over #6** |
| 8 | `heading_color_hex` | `null` | Colour picker + **Clear** | hex or empty | `--heading-color` |
| 9 | `body_color_hex` | `null` | Colour picker + **Clear** | hex or empty | `--body-color` |
| 10 | `radius` | `subtle` | Segmented radio, 4 buttons, each **showing a preview box at that radius** | `sharp` (0px) · `subtle` (6px) · `soft` (14px) · `pill` (9999px) | `--radius` |
| 11 | `density` | `comfortable` | Segmented radio, 3 buttons | `compact` (0.85) · `comfortable` (1) · `spacious` (1.2) | `--density` |
| 12 | `grain` | `false` | Toggle | bool | body grain overlay layer |
| 13 | `glass` | `true` | Toggle | bool | header/card `backdrop-filter` |
| 14 | 🆕 `hero_style` | `full-bleed` | Select | `full-bleed` · `contained` · `split` · `minimal` | hero section layout class |

> ⚠️ **#10 `radius`:** the values are `sharp / subtle / soft / pill` — **not** `none / soft / round`. `RADIUS_SCALE` in `fonts.ts` is keyed on those exact four strings; anything else silently falls back to `subtle`.

#### Motion group — 17 settings · admin screen *Appearance → Motion*

| # | Setting | Default | Admin control | Exact values | Renders as |
|---|---|---|---|---|---|
| 15 | `motion_preset` | `refined` | Segmented radio, **5** buttons | `off` · `subtle` · `refined` · `expressive` · `custom` | master preset — see §11.2 |
| 16 | `motion_level` | `refined` | **none** — hidden mirror of #15 | same 5 values | nothing; parity column only |
| 17 | `scroll_reveal_style` | `fade-up-soft` | Select, **12 options** | `none` `fade` `fade-up` `fade-up-soft` `slide-in-side` `scale-in` `blur-in` `clip-reveal` `curtain-wipe` `line-draw` `stagger-grid` `perspective-tilt` | `IntersectionObserver` reveal class |
| 18 | `hero_animation` | `ken-burns-drift` | Select, **8 options** | `static` `ken-burns-drift` `crossfade-slides` `split-curtain` `masked-word-swap` `zoom-out-on-load` `video-loop` `layered-parallax` | hero media animation |
| 19 | `image_hover_style` | `zoom` | Select, **8 options** | `none` `zoom` `zoom-darken` `duotone-colour` `grayscale-colour` `caption-slide-up` `accent-border-draw` `tilt-follow` | `:hover` on media |
| 20 | `card_hover_style` | `lift-shadow` | Select, **6 options** | `none` `lift-shadow` `accent-underline` `border-trace` `inner-glow` `content-shift` | `:hover` on cards |
| 21 | `text_reveal_style` | `mask-slide-up` | Select, **6 options** | `none` `fade` `mask-slide-up` `word-stagger` `character-stagger` `blur-focus` | heading reveal |
| 22 | `page_transition` | `fade` | Select, **6 options** | `none` `fade` `accent-wipe` `curtain-up` `slide-push` `logo-mask` | navigation overlay |
| 23 | 🆕 `cursor_style` | `none` | Select | `none` · `dot` · `ring` · `accent-blend` | custom cursor element |
| 24 | 🆕 `loader_style` | `none` | Select | `none` · `bar` · `logo-fade` | initial page loader |
| 25 | `scroll_progress` | `false` | Toggle | bool | fixed progress bar |
| 26 | `smooth_scroll` | `false` | Toggle | bool | `scroll-behavior: smooth` |
| 27 | `parallax_intensity` | `20` | Slider `0–100`, step 5, readout | 0–100 | `--parallax-intensity` (÷ 100) |
| 28 | `motion_speed` | `100` | Slider **`50–150`**, step 5, readout `%` | 50–150 | multiplies `--motion-duration` |
| 29 | `stagger_ms` | `70` | Slider `0–300`, step 10 — **enabled only when #15 = `custom`** | 0–300 | `--motion-stagger` |
| 30 | `animate_once` | `true` | Toggle | bool | unobserve after first reveal vs. replay |
| 31 | `motion_on_mobile` | `reduced` | Segmented radio, 3 buttons | `full` · `reduced` · `off` | gates motion under 768px |

> ⚠️ **#15 includes `off` and `custom`** — the source type is `'off' \| 'subtle' \| 'refined' \| 'expressive' \| 'custom'`. There is no `none`.
> ⚠️ **#28 clamps to 50–150**, not 50–200. `resolveMotionVars()` does `Math.min(150, Math.max(50, motionSpeed))` — a slider that goes to 200 would let the admin drag into a dead zone where nothing changes. Cap the control at 150.

`motion_level` (#16) is the only setting with no control — a legacy duplicate of `motion_preset`. Write `motion_preset`'s value into it on every save so the data shape matches the source exactly. **That leaves 30 visible controls covering 31 columns.**

### 11.2 Making the tuning actually work

These are the rules that separate a control that *saves a value* from a control that *tunes the site*. Every one is testable.

**Preset ↔ individual-control interaction (this is the subtle one)**

`resolveMotionVars()` in the source does two things you must reproduce exactly:

1. Each preset carries its own `durationMs`, `ease`, `distancePx`, `staggerMs`:

   | Preset | duration | distance | stagger |
   |---|---|---|---|
   | `off` | 0ms | 0px | 0ms |
   | `subtle` | 450ms | 8px | 50ms |
   | `refined` | 600ms | 12px | 70ms |
   | `expressive` | 750ms | 20px | 90ms |
   | `custom` | uses `refined`'s base | 12px | **the admin's `stagger_ms`** |

   Ease is `cubic-bezier(0.16, 1, 0.3, 1)` for everything except `off`, which is `linear`.

2. **`stagger_ms` is ignored unless the preset is `custom`.** The preset's own stagger wins otherwise.

   That means a naive implementation ships a slider that silently does nothing for 4 of the 5 presets — exactly the failure mode this section exists to prevent. Required behaviour:

   - When #15 ≠ `custom`, the `stagger_ms` slider is **visibly disabled**, shows the preset's effective value, and carries the helper text *"Set the preset to Custom to tune this."*
   - Selecting `custom` enables it, seeded with the current effective value.
   - Selecting `off` disables **and greys out** #17–#22 and #27–#29 with *"Motion is off."* — they genuinely have no effect.

**Every control needs all four of these**

- **A label and one line of helper text** saying what it does in plain language — "Ken Burns Drift: the hero image slowly pans and zooms." Not just the raw ID.
- **A visible current value.** Sliders show a live numeric readout; toggles show On/Off; selects show the friendly `name` from the registry, never the `id`.
- **Reset to default** — per-control for colours and overrides (the **Clear** buttons above), plus one **Reset all appearance settings** button per tab with a confirm step.
- **Server-side validation against the registry.** Every select value is checked against the allowed ID list on save and rejected if unknown; every slider is clamped to its range with `absint()` + min/max; every hex is validated with `sanitize_hex_color()`. Never trust the posted value — a stale or hand-crafted POST must not write a garbage ID that silently falls back at render time.

**Live preview**

- Both tabs render an **inline preview panel** — a miniature hero, a card, a heading, and a button — that updates **immediately on change, before saving**, by writing the CSS vars and `data-*` attributes onto the preview container.
- Motion controls replay their animation in the preview on change, so the admin can compare `blur-in` against `clip-reveal` without saving and switching tabs.
- The preview is a convenience, not the source of truth: the **Save** button still persists, and the §11.4 tests assert against the real public site, not the preview.

**Save UX**

- One Save per tab, with a dirty-state indicator and an "unsaved changes" guard on navigate-away.
- After save, a success notice with a **"View site ↗"** link that opens the public homepage in a new tab.
- Saving must bust the theme-vars cache immediately — the admin must never have to hard-refresh to see the change (§11.3).

### 11.3 Implementation rules

- Everything is driven by CSS custom properties + `data-*` attributes on `<html>`. The JS reads them; it does not hardcode values.
- The theme-vars block is injected inline in `<head>` (§10) — motion vars go in the same block, no second request.
- Scroll reveals use `IntersectionObserver`, never scroll listeners.
- `@media (prefers-reduced-motion: reduce)` disables all motion — **this overrides admin settings**, always. The source collapses to `0ms` / `linear` / `0px` / `0ms` / `0`; match that exactly.
- `motion_on_mobile` gates motion under 768px.
- `cursor_style` must be inert on touch devices — gate on `(pointer: fine)`.
- **Settings are cached, and the cache must be invalidated on save.** Store the resolved var block in a transient; delete it in the save handler. An admin who saves and sees no change will assume the control is broken.
- Unknown stored values must fall back to the default *and* log a notice — never render an empty var. (`RADIUS_SCALE[x] ?? RADIUS_SCALE.subtle` is the source's pattern.)
- **A test must assert no hardcoded animation durations exist in the theme CSS** — the source app has exactly this test (`tests/unit/theme/no-hardcoded-motion.test.ts`). Port it.

### 11.4 Required proof (gate for Phases 6 and 7)

Neither phase may be marked complete without all of the following:

- [ ] A unit test asserting the settings registry contains exactly **31** keys, with the exact names and defaults from §11.1. This test fails loudly if a setting is dropped.
- [ ] A unit test per setting asserting the theme-var builder emits a **different** output for at least two distinct values of that setting. 31 assertions, no exceptions — this is what proves nothing is dormant.
- [ ] **A test asserting every option ID in every select matches the source registry exactly** — 12 scroll-reveal, 8 hero, 8 image-hover, 6 card-hover, 6 text-reveal, 6 page-transition, 24 accents, 22 patterns, 8 font pairs, 4 radius, 3 density, 5 motion presets. Counts and IDs both.
- [ ] **A test proving invalid input is rejected**: POST an unknown ID and an out-of-range slider value to each handler; assert the stored value is unchanged and an error is surfaced.
- [ ] **A test proving the `stagger_ms` gating works** — disabled and inert when the preset is not `custom`, enabled and effective when it is.
- [ ] A Playwright test that, for each of the 30 visible controls: loads the admin screen, changes the control, saves, reloads the public homepage, and asserts the corresponding `data-*` attribute or CSS custom property on `<html>` changed. A control that saves but does not alter the rendered output is a **failing test**, not a passing one.
- [ ] A screenshot-diff or computed-style check for the visual settings that do not map to a single CSS var (`hero_style`, `hero_animation`, `loader_style`, `page_transition`, `cursor_style`, `scroll_progress`, `grain`, `glass`) confirming the DOM actually changes.
- [ ] **A cache-invalidation test** — save, then request the public page with no hard refresh, and assert the new value is already live.
- [ ] Manual sign-off note in the phase commit listing all 31 settings and the observed effect of each.

---

## 12. SEO

- **RankMath** for sitemaps, meta boxes, breadcrumbs, robots.
- **Hand-written JSON-LD** (RankMath's is not equivalent):
  - `Organization` in the footer — name, description, address, contactPoint, sameAs (socials)
  - `FAQPage` on the homepage FAQ accordion
  - `CreativeWork` on each project — name, description, image, locationCreated, dateCreated
  - `BreadcrumbList` on detail pages
- Dynamic per-page meta from Site/SEO settings with sensible fallbacks
- OG + Twitter cards, `summary_large_image` when an image exists
- Canonical URL on every page
- Sitemap covers: `/`, `/work`, `/work/*`, `/services`, `/services/*`, `/about`, `/team`, `/contact`, and `/blog` + posts **only when the blog is enabled**
- `robots.txt` disallows `/wp-admin`

---

## 13. Security requirements

Non-negotiable. Each item needs a test or a documented verification step.

**Input / output**
- [ ] Every form has a nonce; every handler verifies it
- [ ] Every write action checks `current_user_can()`
- [ ] All input sanitized with the correct WP sanitizer for its type
- [ ] All output escaped — `esc_html` / `esc_attr` / `esc_url` / `wp_kses_post`
- [ ] All DB access via `$wpdb->prepare()` — **zero string-interpolated SQL**

**File uploads**
- [ ] MIME detected by **magic bytes**, never file extension or client-supplied type
- [ ] Allowlist only: JPEG, PNG, WebP, **GIF**, MP4 — GIF accepted for hero slides only (§6.3)
- [ ] `.htaccess` in the uploads dir blocks PHP execution
- [ ] Filenames randomised (UUID), never derived from user input
- [ ] Size limits enforced server-side (§6.1 rule 5)
- [ ] **Chunked upload endpoint is capability-checked and nonce-verified on every chunk**, not just the first
- [ ] **Chunk reassembly is path-traversal safe** — chunk IDs are server-generated, never taken from client input
- [ ] **Orphaned chunks are garbage-collected** on a scheduled event (abandoned uploads must not fill the 20 GB disk)
- [ ] Video validated by magic bytes **after** reassembly completes, and deleted immediately if it fails
- [ ] Upload endpoints rate-limited per user

**External video URLs (§6.2)** — accepting user-supplied URLs is a real attack surface. All of these are required:
- [ ] Scheme allowlist: **`https` only**. Reject `http`, and hard-reject `javascript:`, `data:`, `file:`, `//`-protocol-relative
- [ ] Host allowlist for embeds: only `youtube.com`, `www.youtube.com`, `youtu.be`, `m.youtube.com`, `vimeo.com`, `player.vimeo.com`. Match the **parsed host exactly** — never `strpos()`, or `evil.com/youtube.com` slips through
- [ ] **Embed URLs are rebuilt from an extracted ID**, never echoed from user input. Extract with a strict regex (`[A-Za-z0-9_-]{11}` for YouTube, `\d+` for Vimeo), then construct `https://www.youtube-nocookie.com/embed/{ID}`
- [ ] Direct file URLs: `https` + extension in {`.mp4`, `.webm`} + host is a public IP
- [ ] **SSRF protection** — if the server ever fetches the URL (HEAD check, poster extraction, oEmbed): resolve DNS and **reject private/reserved ranges** (`10/8`, `172.16/12`, `192.168/16`, `127/8`, `169.254/16`, `::1`, `fc00::/7`), cap redirects at 3, re-validate the host after **every** redirect, and set a short timeout
- [ ] `esc_url()` on every URL at output; `esc_attr()` on every iframe attribute
- [ ] Iframes carry `sandbox="allow-scripts allow-same-origin allow-presentation"` where the player still functions, plus `referrerpolicy="strict-origin-when-cross-origin"`
- [ ] CSP `frame-src` limited to the embed allowlist — so even a bypass cannot frame arbitrary origins
- [ ] Validation runs **server-side on save**, not only in the browser

**Auth**
- [ ] Login rate limiting (5 / 15 min / IP)
- [ ] Session cookies `HttpOnly`, `Secure`, `SameSite=Lax`
- [ ] Password hashing via WP's native `wp_hash_password` (bcrypt/argon2)
- [ ] Force password change while the bootstrap sentinel is unchanged
- [ ] Deactivated users blocked at login

**Hardening**
- [ ] `DISALLOW_FILE_EDIT` set — no plugin/theme editor in wp-admin
- [ ] XML-RPC disabled
- [ ] WP version string removed from `<head>`
- [ ] REST API user enumeration blocked for unauthenticated requests
- [ ] Security headers: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Content-Security-Policy`
- [ ] `wp-config.php` outside webroot if the host allows, else protected by `.htaccess`
- [ ] Admin area not indexable

---

## 14. Testing requirements

**Coverage target: 80% line coverage on plugin PHP, 100% on business-logic classes** (approval policy, storage adapters, theme var builder, sanitizers).

| Layer | Tool | What it covers |
|---|---|---|
| Unit | PHPUnit + Brain Monkey | Approval policy, role policy, theme var builder, config validation, sanitizers, slug generation, **the 31-setting registry test + the 31 "two values → two outputs" assertions (§11.4)** |
| Integration | WP PHPUnit (`wp-env`, **real MariaDB 10.6** — pin the image to match Hostinger) | CPT registration, meta round-trips, storage adapters against the contract, custom table CRUD, approval queue end-to-end, `dbDelta()` idempotency and charset (§3.6) |
| E2E | Playwright | Login, create project, upload image, approval flow, **all 30 appearance/motion controls round-tripped and verified on the public site (§11.4)**, video autoplay across all three sources (§6.2), contact form → inbox, mobile viewport checks |
| Static | PHPStan level 6+ | Type errors |
| Lint | PHPCS + WPCS | Coding standards, escaping violations (WPCS catches unescaped output) |
| A11y | axe-core in Playwright | No critical violations on any public page |
| Visual | Playwright screenshots at 375px | Zero horizontal overflow on every public page |

**CI must run all of the above on every push.** A phase gate is not passed until CI is green.

---

## 15. Build phases

> Each phase: implement → run the gate → **commit and push** → stop and report. Do not proceed until the gate passes.
>
> Commit message format: `phase(N): short description`

### Phase 0 — Scaffold & tooling
Repo structure, `composer.json`, `package.json`, `.wp-env.json`, PHPCS/PHPStan configs, Playwright config, GitHub Actions CI, `README.md`, `.gitignore`.
Empty plugin + theme that activate cleanly.

**Gate:** `wp-env start` boots. Plugin and theme activate with zero notices/warnings. `composer lint`, `composer analyse`, `composer test`, and `npx playwright test` all run (may have zero tests) and CI is green.
**Commit & push.**

### Phase 1 — Config layer
`Config` class reading the §5 constants, with validation and readable admin notices on misconfiguration.

**Gate:** Unit tests cover valid config, each invalid driver code, and missing-credential cases. All pass.
**Commit & push.**

### Phase 2 — Content model
All CPTs, the taxonomy, and every ACF field group from §3.1 — defined **in PHP under version control**, not clicked in the UI. Three custom tables via `dbDelta()` with a versioned upgrade routine, following every rule in **§3.6** (MariaDB type mapping, `VARCHAR(191)` on indexed columns, `get_charset_collate()`, no vendor-specific DDL).

**Gate:** Integration tests assert every post type and taxonomy is registered with correct args; every meta field round-trips; all three tables exist with correct columns and indexes. Activating, deactivating, and reactivating the plugin is idempotent.

**Database gate (§3.6)** — run against **real MariaDB 10.6**, not a newer MySQL:
- Activate on an empty database, then activate again; assert the second run produces **no** `ALTER` statements and no duplicate columns (the classic `dbDelta()` format trap)
- Assert every custom table reports a `utf8mb4` collation
- Assert no index key exceeds 767 bytes — i.e. no indexed `VARCHAR` wider than 191
- Assert JSON-bearing columns are `LONGTEXT` and are read/written **only** via `wp_json_encode()`/`json_decode()`; grep the codebase for SQL JSON functions and fail if any exist
- Grep for `mysqli_`, `new PDO`, and hardcoded `wp_` prefixes; fail on any hit

**Commit & push.**

### Phase 3 — Storage abstraction + local video delivery
`StorageAdapter` interface, `StorageFactory`, and all six adapters. Local adapter is the shipped default, fully wired into the Media Library. **All of §6.1 is implemented in this phase** — chunked upload, faststart handling, quota reporting.

**Gate:**
- Storage contract suite passes against the Local adapter; remote adapters pass behind env flags (skipped, not failed)
- `.htaccess` PHP-execution block verified: a test uploads a `.php` file and asserts rejection, and asserts the uploads dir denies execution
- **`LocalStorageAdapter::url()` returns a direct static file URL** — a test asserts the URL does not route through PHP
- **Range request test:** `curl -H "Range: bytes=0-1023"` against an uploaded video returns **HTTP 206** with `Accept-Ranges: bytes`
- **Chunked upload test:** a file larger than `post_max_size` uploads successfully via chunking, reassembles byte-identical (compare checksums), and validates
- **Abandoned-chunk GC test:** orphaned chunks are removed by the scheduled event
- **Quota reporting test:** disk usage and inode count render on the Dashboard

**`VideoResolver` gate (§6.2)** — unit tests covering the full matrix:
- Uploaded file → `kind='file'`, direct static URL
- YouTube in all accepted forms (`watch?v=`, `youtu.be/`, `/embed/`, with extra query params) → `kind='youtube'`, correct extracted ID, `youtube-nocookie.com` embed URL
- **YouTube embed URL contains `autoplay=1`, `mute=1`, `loop=1`, and `playlist={ID}` matching the video ID** — assert the `playlist` param explicitly, since `loop` silently fails without it
- Vimeo in both forms → `kind='vimeo'`, correct ID, embed URL contains `autoplay=1`, `muted=1`, `loop=1`, `background=1`
- Direct `.mp4` / `.webm` URL → `kind='file'`
- **Rejected:** `http://`, `javascript:`, `data:`, protocol-relative `//`, `evil.com/youtube.com/watch?v=X`, `youtube.com.evil.com`, unknown hosts, non-video extensions
- **SSRF:** URLs resolving to `127.0.0.1`, `10.x`, `192.168.x`, `169.254.x`, `::1` are rejected; redirect chains re-validate the host at every hop

**Commit & push.**

### Phase 4 — Roles, auth, security baseline
Custom roles + capabilities, admin bootstrap, forced password change, `is_active` enforcement, login rate limiting, and the §13 hardening items.

**Gate:** Unit + integration tests for every capability boundary. A test asserts an editor receives 403 on every admin-only screen. Rate limiting verified. All §13 checkboxes ticked with a linked test or documented check.
**Commit & push.**

### Phase 5 — Approval workflow
`mk_revisions` logic and the `ContentVisibilityPolicy` port. Audit log writes.

**Gate:** The full 12-case test matrix from §8 passes. An integration test proves that an editor's edit to a published project does **not** change public output until approved.
**Commit & push.**

### Phase 6 — Theme engine
24 accents, 22 pattern SVGs, 8 font pairings, per-area overrides, radius/density/grain/glass, hero style, CSS var injection, no-flash mode script.

Build the **Appearance → Theme** admin screen with all **14** Appearance-group controls from §11.1 (#1–#14). Do not copy the source app's 6-control `AppearanceForm.tsx` — it is incomplete (§11).

**Gate:** Unit tests assert every accent produces a valid contrast-passing foreground colour, every pattern SVG exists and is well-formed, and the var builder output snapshot-matches expectations. **Plus the §11.4 proof gate for settings #1–#14**: registry-count test, per-setting "two values → two outputs" test, and a Playwright round-trip per control proving the public site changes. Manual check: switching accent in admin changes the public site immediately.
**Commit & push.**

### Phase 7 — Motion engine
All **17** Motion-group settings from §11.1 (#15–#31), driven entirely by CSS variables and `data-*` attributes, with the **Appearance → Motion** admin screen exposing 16 controls (`motion_level` mirrors `motion_preset`, no separate control).

**Gate:** The "no hardcoded animation durations" test passes. `prefers-reduced-motion` disables motion (Playwright test with the media feature emulated). `motion_on_mobile` verified at 375px. `cursor_style` inert under `(pointer: coarse)`. **Plus the §11.4 proof gate for settings #15–#31** — including the DOM/computed-style checks for `hero_animation`, `loader_style`, `page_transition`, `cursor_style`, and `scroll_progress`.

**Combined gate for Phases 6+7:** the registry test must assert exactly **31** settings exist, and the commit message must list all 31 with the observed effect of each. Neither phase is complete while any setting is stored-but-inert.
**Commit & push.**

### Phase 8 — Admin panel
All 17 screens from §3.3, custom menu, responsive, nonces + capability checks throughout.

**Gate:** Playwright covers login → each screen renders → a create/edit/delete round-trip on projects, services, team, hero, and content. Every form's nonce and capability check verified. Admin usable at 375px.
**Commit & push.**

### Phase 9 — Public templates
All 10 pages from §3.2, all homepage sections, lightbox, FAQ accordion, hero carousel.

**Gate:** Every page renders with seeded content. Playwright screenshots at 375px show zero horizontal overflow on all 10 pages. axe-core reports no critical violations.

**Video playback gate (§6.2)** — Playwright asserts all three sources actually autoplay:
- Hero slide with an **uploaded** file → `<video>` has `autoplay muted loop playsinline`; assert `video.paused === false` and `video.currentTime` advances after ~1s
- Hero slide with a **direct `.mp4` link** → identical behaviour to upload
- Hero slide with a **YouTube link** → iframe present, `src` on `youtube-nocookie.com`, and query contains `autoplay=1`, `mute=1`, `loop=1`, `playlist={ID}`
- Hero slide with a **Vimeo link** → iframe present, `src` contains `background=1` and `autoplay=1`
- **Every** video has a non-empty `poster` / placeholder
- With `prefers-reduced-motion: reduce` emulated, **no video autoplays** and the poster shows with a play control

**Hero media gate (§6.3)** — Playwright asserts all four hero kinds:
- **Still image** slide → `<img>`, Ken Burns animation applied
- **GIF** slide → `<img>` with the GIF `src`, **no `<video>` element**, and **no Ken Burns**
- **Uploaded MP4** and **video-link** slides → covered by the video gate above
- Carousel advances at the configured interval; changing the slide-duration setting to 3s changes the observed interval
- A slide with **hold-until-video-ends** on stays visible for its video's duration, and a clip longer than `MK_MAX_HERO_HOLD_SECONDS` is still capped at 20s
- With **one** slide only, no rotation occurs and no length warning is shown
- With `prefers-reduced-motion: reduce`, the GIF slide renders its **first frame only**

**Commit & push.**

### Phase 10 — Site Text & settings
The 29-field Site Text screen, Settings screen, favicon with fallback chain, admin-shield toggle with copyable login URL, blog enable/disable, vision & mission toggle.

**Gate:** Changing any Site Text field updates the public site. Favicon fallback chain tested at each level. Disabling the blog hides the nav item and 404s `/blog`. Admin-shield toggle verified both ways.
**Commit & push.**

### Phase 11 — SEO & structured data
RankMath configuration, all four JSON-LD types, dynamic meta, sitemap, canonicals, footer.

**Gate:** JSON-LD validates against Schema.org for all four types. Sitemap includes the correct routes and respects the blog toggle. Every page has a canonical and non-empty meta description.
**Commit & push.**

### Phase 12 — Contact form & inquiries
Public form with server-side validation, honeypot + rate limiting, storage in `mk_inquiries`, admin inbox with read/unread, optional email notification per `MK_MAIL_DRIVER`.

**Gate:** Playwright submits the form and asserts the inquiry appears in the admin inbox. Spam protections verified. Invalid input rejected server-side (not just client-side).
**Commit & push.**

### Phase 13 — Demo content seeder
> **No PostgreSQL/Neon dependency.** The product owner has decided the WordPress build is standalone on MySQL. There is no live migration from the old database.

A WP-CLI command `wp maapkathi seed` that creates a complete, presentable demo site from scratch, entirely within WordPress:
- Demo studio settings, theme settings, and all 29 Site Text defaults
- 3 project categories; 6 projects with cover + 3-image gallery each
- 4 services (2 parent + 2 child) with icons and covers
- 4 team members, 3 testimonials, 6 clients, 3 awards, 4 stats, 4 values, 3 FAQs
- 4 hero slides, one per media kind (§6.3) — 1 still image, 1 animated GIF, 1 uploaded ~6s MP4, 1 external video link. This exercises every hero path end to end on a fresh install.
- Demo images bundled with the plugin under `assets/demo/` (CC0 licensed) and imported through `StorageFactory`, so the seeder exercises the real storage path rather than writing files directly

**Idempotency is required.** Running the command twice must not duplicate anything. Guard on an existing marker (e.g. the demo settings row), exactly as the source app's seeder does.

*Optional, only if the user later asks:* a separate `wp maapkathi import-legacy` command reading the old Postgres export. Do not build this unless requested.

**Gate:** On a clean WordPress install, `wp maapkathi seed` produces a fully populated site with every homepage section rendering and the demo video playing from local storage. Running it a second time changes nothing (assert row counts identical before and after).
**Commit & push.**

### Phase 14 — Deployment & handover
Hostinger deployment guide with hPanel paths, `wp-config.php` template, backup/restore procedure, and the six docs from §2.

**Gate:** A clean install on Hostinger from the documented steps produces a working site. All docs reviewed for accuracy. Full CI green. Every box in §3.4 and §13 ticked.
**Commit & push. Tag `v1.0.0`.**

---

## 16. Definition of done

The build is complete only when **all** of these hold:

1. Every checkbox in §3.4 (features) is ticked and demonstrable.
2. Every checkbox in §13 (security) is ticked with a test or documented verification.
3. Coverage targets in §14 are met.
4. CI is green: lint, static analysis, unit, integration, E2E, a11y.
5. All 10 public pages verified at 375px with zero horizontal overflow.
6. A side-by-side comparison against `https://maapkathi.vercel.app` shows no missing feature.
7. A fresh install on Hostinger works from the documented steps alone.
8. The six docs in `docs/` are accurate and complete.
9. **Every §6.1 rule is implemented and tested** — a video uploaded through the admin plays and seeks correctly on the live Hostinger site, from local storage, with zero PHP workers involved in delivery.
10. **The build has no dependency on any external database or storage service.** MariaDB (via `$wpdb`) and the hosting disk are sufficient to run everything. Verified by installing on a stock MariaDB 10.6 with no superuser rights (§3.6).
11. **All 31 theme settings (§11.1) are admin-editable and each one demonstrably changes the rendered site.** The §11.4 proof gate passes in full. Zero stored-but-inert settings.

---

## 17. Notes for the agent

- **The source app is the spec.** When in doubt, read `LabibTajremin/MapKathi_Web_App` — particularly `src/presentation/theme/` (registries), `src/domain/services/` (policies), and `src/app/(admin)/` (admin behaviour).
- **Do not use a page builder.** Not Elementor, not WPBakery, not Divi. They cannot reproduce this design system and they make the result unmaintainable.
- **Do not click-configure ACF.** Define field groups in PHP so they are version-controlled and deployable.
- **Verify with a real browser before claiming a UI phase is done.** Typecheck-equivalents are not enough; take screenshots.
- **Never claim a gate passed without running it.** Paste the actual output.
- **Ask when genuinely blocked** — an unanswered architectural question is cheaper than a wrong build.
- **The source app is the spec — with exactly one documented exception:** the Appearance/Motion admin UI (§11), where the source is incomplete and you must build all 31 controls. Everywhere else, match the source.
- **Three decisions are settled. Do not reopen them:**
  1. **MariaDB / MySQL only.** No Neon, no PostgreSQL, no external database, no ORM, no direct connection. The build must run on nothing but WordPress + MariaDB via `$wpdb` (§3.6).
  2. **All media on the hosting disk**, video included. Do not propose Bunny/Cloudflare/YouTube as the default. Build §6.1 properly instead.
  3. **All 31 theme settings exposed and working** (§11). Do not ship the source app's 6-control subset, and do not ask the user to choose — this was already decided.
  If you hit a genuine technical wall on any of these, state the specific obstacle and what you tried — do not quietly substitute a different architecture.
