# Developer Guide — Where to Edit What

A practical map of this codebase for a developer making changes by hand.
For *why* things are built this way, see `ARCHITECTURE.md`; for what is
verified vs. not, see `BUILD_STATUS.md`. Read the "Do not break" section at
the bottom before you touch roles, capabilities, or meta fields.

---

## 1. The one rule

**Plugin = data + logic. Theme = HTML/CSS/JS only.**

| Folder | Owns | Never does |
|---|---|---|
| `plugins/maapkathi-core/` | Post types, fields, options, DB tables, admin screens, REST, roles, email, settings | Output front-end page markup |
| `themes/maapkathi-theme/` | Templates, CSS, front-end JS | Write to the DB, register CPTs/fields/options |

The theme reads plugin data via the helper functions in
`plugins/maapkathi-core/src/Support/template-functions.php` (`mk_text()`,
`mk_setting()`, `mk_content()`, `mk_meta()`, `mk_section()` …). Every theme
call to the plugin is guarded by `mk_theme_plugin_active()` so the site
degrades instead of white-screening if the plugin is off.

---

## 2. Quick lookup: "I want to change…"

| I want to change… | Edit this |
|---|---|
| Wording/copy on the site (headings, taglines) | Admin → Maapkathi → **Site Text** (no code). To add a new editable string: `src/Support/SiteText.php` `schema()` |
| Colours, fonts, motion, dark mode, radius | Admin → Maapkathi → **Appearance**. New setting: `src/Theme/ThemeSettings.php` (`defaults()`, `allowed_values()`, `sanitize()`) + `src/Theme/ThemeVarsBuilder.php` + `src/Admin/views/appearance.php` |
| Accent colours / patterns / font pairings list | `src/Theme/Accents.php`, `Patterns.php`, `Fonts.php`, `Motion.php` |
| Base CSS variables / design tokens | `themes/maapkathi-theme/assets/css/tokens.css` |
| General page styling (typography, layout, header, footer) | `themes/maapkathi-theme/assets/css/base.css` |
| Homepage section styling | `themes/maapkathi-theme/assets/css/sections.css` |
| Animations / reveal effects | `assets/css/motion.css` + `assets/js/motion-engine.js` |
| Header / navigation markup | `themes/maapkathi-theme/parts/header.php`, `parts/nav.php` |
| Footer markup | `parts/footer.php` → `parts/footer-classic.php` / `parts/footer-modern.php`; data in `src/Footer/FooterSettings.php` |
| Hero (top of homepage) | Markup `parts/hero.php`; admin `src/Admin/Screens/HeroScreen.php` + `src/Admin/views/hero.php`; video via `src/Video/VideoResolver.php` |
| A homepage section's markup (services, team, FAQ…) | `themes/maapkathi-theme/parts/sections/<type>.php` |
| Homepage section order/visibility | Admin → **Sections** / **Section Builder** (no code). Logic: `src/Sections/SectionRegistry.php`, `SectionLayout.php` |
| About / Contact / Services / Team pages | `themes/maapkathi-theme/page-about.php`, `page-contact.php`, `page-services.php`, `page-team.php` |
| Project archive / single project | `archive-mk_project.php`, `single-mk_project.php` |
| Single service page | `single-mk_service.php` |
| Blog | `home.php` (list), `single.php` (post), `index.php` (fallback) |
| 404 page | `404.php` |
| Contact form behaviour / enquiry storage | `src/Inquiries/Inquiries.php`; admin list `src/Admin/Screens/InquiriesScreen.php` |
| Outgoing emails / SMTP | `src/Mail/Mailer.php`; constants in `wp-config.php` (see `CONFIGURATION.md`) |
| Newsletter subscribe | `src/Footer/Subscribers.php` + `assets/js/subscribe.js` |
| Map (contact/home) | `src/Map/MapSettings.php`, `parts/section-map.php` |
| Gallery / lightbox | `src/Gallery/*`, `assets/js/gallery.js`, `assets/js/lightbox.js` |
| Icons | `src/Icons/IconLibrary.php` (add icons), `IconRenderer.php`; theme uses `mk_icon()` |
| Logos / branding | `src/Support/Logos.php`, `src/Support/Branding.php` |
| SEO meta / OG tags / schema | `src/Seo/Seo.php` |
| Admin menu items | `src/Admin/Menu.php` |
| Admin look & feel | `src/Admin/AdminSkin.php`, `assets/admin/admin.css`, `admin-skin.js` |
| Users & roles / permissions | `src/Roles/Roles.php` (read §7 first) |
| Editor-submits → admin-approves workflow | `src/Approval/ApprovalService.php`, `ContentVisibilityPolicy.php` |
| File/video uploads | `src/Rest/UploadController.php`, `src/Storage/*` |
| Login rate-limit / hardening | `src/Support/Security.php` |
| First-run setup wizard | `src/Setup/SetupWizard.php`, `src/Setup/view.php` |
| Demo content | `src/Cli/SeedCommand.php`, `src/Cli/DemoAssets.php` |
| Runtime config (drivers, size limits) | `wp-config.php` constants, validated in `src/Config/Config.php` |
| Custom DB tables | `src/Support/Database.php` (+ `Migrations.php` for upgrades) |

All `src/...` paths above are under `plugins/maapkathi-core/`.

---

## 3. Plugin walkthrough (`plugins/maapkathi-core/`)

### 3.1 Boot sequence
- `maapkathi-core.php` — plugin header, constants (`MK_PLUGIN_DIR`, `MK_PLUGIN_FILE`), autoloader, activation hooks.
- `src/Plugin.php` — `boot()` instantiates every subsystem and calls its
  `register_hooks()`. **A new subsystem class must be added here** or it
  never runs. Admin-only classes go inside the `is_admin()` block.

### 3.2 Content types (custom post types)
File: `src/PostTypes/PostTypes.php` → `definitions()`.

Existing: `mk_project`, `mk_service`, `mk_member`, `mk_testimonial`,
`mk_client`, `mk_partner`, `mk_award`, `mk_faq`, `mk_value`, `mk_stat`,
`mk_process_step`. Taxonomies: `src/PostTypes/Taxonomies.php`.

**Add a content type:**
1. Add an entry to `definitions()` (`label`, `singular`, `slug`, `icon`, `supports`, optional `public`/`has_archive`/`hierarchical`). Include `'custom-fields'` in `supports` if fields must be REST-editable.
2. Add its fields in `src/Fields/MetaBoxes.php` `schema()` (see 3.3).
3. Render it in the theme (a section partial or template).
4. Re-save **Settings → Permalinks** (or deactivate/reactivate the plugin) to flush rewrite rules.

It automatically appears under the Maapkathi admin menu (`show_in_menu => 'maapkathi'`).

### 3.3 Fields (post meta)
File: `src/Fields/MetaBoxes.php` → `schema()`, keyed by post type.
Each field: `key` (prefix `mk_`), `label`, `type`, optional `help`.

Supported `type`s: `text` (default), `textarea`, `checkbox`, `number`,
`date`, `url`, `gallery`, `icon`, `media`.

Registration (`register_post_meta`), the meta box UI, and saving are all
driven from this one schema — adding an array entry is usually all you need.
Read a value in the theme with `mk_meta( $post_id, 'mk_your_key' )`.

### 3.4 Site-wide options
| Option | Class | Admin screen |
|---|---|---|
| Site Text (copy strings) | `Support/SiteText.php` | `Admin/Screens/SiteTextScreen.php` + `views/site-text.php` |
| Theme/appearance (32 settings) | `Theme/ThemeSettings.php` | `views/appearance.php` |
| Sections | `Sections/SectionRegistry.php`, `SectionLayout.php` | `SectionsScreen.php`, `SectionBuilderScreen.php` |
| Footer | `Footer/FooterSettings.php` | `FooterScreen.php` |
| General settings | — | `SettingsScreen.php` + `views/settings.php` |

Pattern for every admin screen: **Screen class** (`src/Admin/Screens/*`)
handles saving/validation, **view** (`src/Admin/views/*.php`) is the HTML,
**Menu.php** registers the submenu and calls the screen.

**Add an admin screen:** create `Screens/FooScreen.php` + `views/foo.php`,
add an `add_submenu_page( 'maapkathi', …, 'maapkathi-foo', … )` and a
`render_foo()` method in `Menu.php`. Gate it with a `Roles::CAP_*` constant.

### 3.5 Theme engine
`ThemeSettings::defaults()` is the single source of truth.
`ThemeVarsBuilder::build()` turns settings into CSS custom properties +
`data-*` attributes printed inline in `<head>` by the theme's
`functions.php`. Output is cached in a transient, busted automatically on
save (`ThemeSettings::flush_cache()`).

**Add an appearance setting:** add default in `defaults()` → allowed
values/sanitising in `allowed_values()`/`sanitize()` → emit a CSS var in
`ThemeVarsBuilder` → add the control in `views/appearance.php` → use the
var (`var(--mk-…)`) in theme CSS.

### 3.6 REST endpoints (`src/Rest/`)
`UploadController` (chunked uploads), `HealthController`,
`PlaceholderController`. Add a new controller with `register_rest_route()`
in its `register_hooks()`, then wire it in `Plugin.php`.

### 3.7 Roles & capabilities (`src/Roles/Roles.php`)
Roles: `mk_admin`, `mk_editor`. Caps: `mk_manage_settings`,
`mk_manage_appearance`, `mk_manage_users`, `mk_approve_revisions`,
`mk_publish_content`, `mk_edit_content` (constants `Roles::CAP_*`).
After changing role caps, deactivate/reactivate the plugin so roles are re-registered.

### 3.8 Database
Only 3 custom tables: `{prefix}mk_inquiries`, `mk_audit_log`,
`mk_revisions` (`src/Support/Database.php`, via `dbDelta`). Schema changes:
bump the DB version and add an upgrade step in `Migrations.php` /
`Database::maybe_upgrade()`. Everything else is native WP posts/meta/options.

---

## 4. Theme walkthrough (`themes/maapkathi-theme/`)

```
functions.php          setup, image sizes, menus, enqueue CSS/JS, inline theme vars
inc/asset-cache.php    path-based cache busting (mk_asset_url / mk_asset_version)
header.php, footer.php thin wrappers → parts/header.php, parts/footer.php
front-page.php         homepage: hero + loop over mk_section_layout() + map
page-*.php             hand-built pages (about, contact, services, team)
single-*.php, archive-mk_project.php, home.php, single.php, index.php, 404.php
parts/                 reusable chunks (hero, nav, footers, map, partners, showcase)
parts/sections/*.php   one file per homepage section type
assets/css/            tokens.css → base.css → sections.css, motion.css
assets/js/             motion-engine, theme-toggle, gallery, lightbox, showcase,
                       partners-marquee, subscribe
style.css              theme header (Version) only
```

### 4.1 How the homepage renders
`front-page.php` loops over `mk_section_layout()` (admin-ordered rows). For
each enabled row it `require`s `parts/sections/<type>.php` with `$mk_id`
set to that instance's id. Inside a section partial, use:
- `mk_section( $mk_id )` — title/subtitle/visibility data
- `mk_section_anchor( $mk_id )` — the DOM id
- `mk_the_section_heading( $mk_id )` — prints the heading
- `mk_content( 'what', … )` — fetch the CPT items to list

### 4.2 Add a new homepage section
1. Register it in `plugins/.../src/Sections/SectionRegistry.php` `all()` (`label`, `anchor`, `text_key`, `page`, `core`).
2. If it has a heading, add the `text_key` to `Support/SiteText.php`.
3. Create `themes/maapkathi-theme/parts/sections/<id>.php` (copy an existing one like `faq.php`).
4. Style it in `assets/css/sections.css`.
5. Enable/position it in Admin → Sections.

### 4.3 CSS / JS
- Use the CSS variables from `tokens.css` / ThemeVarsBuilder instead of hard-coded colours, so Appearance settings keep working.
- New JS file: put it in `assets/js/`, enqueue in `functions.php` with `mk_asset_url()` / `mk_asset_version()` (don't use plain `?ver=` — production strips query strings; see `inc/asset-cache.php`).
- No build step: CSS/JS are plain files, edit and reload.

---

## 5. Local development

Docker is the tested route (see `CLAUDE.md` "Environment notes"):

```bash
docker compose up -d   # mariadb:10.6 + wordpress:apache + wordpress:cli
```
Mount `plugins/maapkathi-core` → `/var/www/html/wp-content/plugins/maapkathi-core`
and `themes/maapkathi-theme` → `/var/www/html/wp-content/themes/maapkathi-theme`.
Then activate and seed demo content:

```bash
wp plugin activate maapkathi-core && wp theme activate maapkathi-theme
```
```bash
wp maapkathi seed
```

Turn on `WP_DEBUG` + `WP_DEBUG_LOG` in `wp-config.php` and watch
`wp-content/debug.log`.

Tests: `tests/Unit`, `tests/Integration` (PHPUnit — see `TESTING.md`),
`tests/E2E` (Playwright: `npm install && npm run test:e2e`).
Deployment to Hostinger: `DEPLOYMENT.md` and `deploy/`.

---

## 6. Do not break (hard-won fixes)

1. Keep the literal `edit_posts` cap on `mk_admin` and `mk_editor` — removing it breaks every CPT admin screen for those roles.
2. Never map the singular CPT caps `edit_post` / `read_post` / `delete_post` to a cap string used elsewhere in bare `current_user_can()` checks. Leave them unset.
3. Checkbox meta must register as REST type `boolean`, not `string`.
4. CPTs needing REST-editable fields need `'custom-fields'` in `supports`.
5. Rate limiting hooks `wp_login_failed`, never the `authenticate` filter.
6. `mk_service` stays `has_archive => false` — `/services` is a Page (`page-services.php`).
7. No ACF / Carbon Fields — fields are native (`Fields/MetaBoxes.php`) on purpose.
8. Theme never writes to the database.

## 7. Workflow checklist for any change
- [ ] Data/logic change → plugin; markup/style change → theme.
- [ ] New class → wired in `src/Plugin.php`.
- [ ] New CPT/slug → flush permalinks.
- [ ] New user-facing string → wrapped in `__( '…', 'maapkathi' )` and escaped on output (`esc_html`, `esc_attr`, `esc_url`).
- [ ] Forms → nonce + capability check + sanitize input.
- [ ] Update `docs/BUILD_STATUS.md` if you change what's verified.
