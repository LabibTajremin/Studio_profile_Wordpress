# QA Sign-off — Change Request R1

Gate for the ten feature requirements in *Studio Profile WP Theme — Change
Request Specification, Revision R1* (spec §14).

**Environment.** WordPress 6.7 + PHP 8.2 + MariaDB 10.6, run in Docker with
`WP_DEBUG` on, the plugin and theme mounted from the working tree, and demo
content seeded via `wp maapkathi seed`. Browser checks ran in Chromium
through Playwright.

**A note on how to read this.** Every line below says how it was checked.
Where something was *not* verified, it says so rather than being ticked —
the point of a gate is that an unticked line is information.

---

## 1. Two defects this gate caught

Both were found by running the stack, not by reading the code.

**Deprecations on every admin request (GR-08).** The setup wizard
registered its hidden page with `add_submenu_page( null, … )`. WordPress
passes the parent slug through `plugin_basename()`, so PHP 8.1+ emitted two
deprecation notices per admin request — 62 log lines from one sweep. Fixed
by using `'options.php'` as the parent, which keeps the page registered and
reachable by URL while showing nothing in the menu.

The first run of this check reported "clean" and was wrong: `WP_DEBUG` was
false in the container doing the asking, and `WP_DEBUG_LOG` was set per
container rather than in `wp-config.php`. The result only became meaningful
after a deliberate canary notice proved the log captured anything at all.

**Horizontal scrollbar at ≤768px (GR-04).** The header overflowed on every
inner page. The inline nav was still rendering at 768px, where its five
links measured 415px and pushed the row to 750px inside a 707px content
box; and below that, the studio name held the logo at full width because a
flex item will not shrink past its content unless told it may. Fixed by
moving the hamburger hand-off to 900px and allowing the logo to shrink and
its title to truncate.

---

## 2. Functional

| Check | Result | How |
|---|---|---|
| Header: all four priority states (accent / hex / palette / fallback) | Pass | Unit tests per rule; live check of `--header-bg` flipping from `#6e1f2a` to `#0f172a` and back |
| Header: invalid hex rejected, previous value intact | Pass | Unit tests over `12xyz`, `#12345`, `red`, `''`, `#`, null, 42 |
| Header: auto-contrast foreground | Pass | Live: `--header-fg` = `#faf6f1` on `#0f172a`, `#141110` on `#ffffff` |
| Every section title editable, inner pages included | Pass | Sections screen covers 12 homepage sections + 5 inner pages |
| Renaming a section does not break its anchor | Pass | Live: renamed "Trusted by" → "Our Clients"; `id="clients"` unchanged |
| Clearing a title restores the default, not a blank | Pass | Live: cleared field re-rendered "Trusted by" |
| Hiding a title removes it | Pass | Live: heading absent after `show_title` off |
| Sections add / duplicate / reorder / disable / delete | Pass | Live: reorder moved Values to first; disable removed it; duplicate rendered a second instance |
| Two instances of a type are independent | Pass | Live: anchors `#values` and `#values-two`, headings "What we stand for" and "Second values block" |
| Gallery renders mixed sizes with no cropping or overlap | Pass | Chromium: aspect ratios matched source exactly (delta 0), largest gap within 1px of the gutter |
| Lightbox: arrows, keyboard, counter, Escape, focus return | Pass | Chromium: stepped 3→4→2, wrapped 1→12, Escape returned focus to the trigger |
| Map from address and from lat/lng; lat/lng wins | Pass | Live: `q=House%2042…` then `q=23.7806%2C90.4074` with both set |
| Map hidden when unconfigured; no visitor-facing error | Pass | Live: no `mk-map__frame` before configuring, and again after clearing |
| Services icons at size, title beside icon, both templates | Pass | `--services-icon-size: 84px`; `mk-services--icon-beside` present on both |
| Icons follow the accent | Pass | Single `currentColor` rule for bundled, uploaded and legacy icons |
| Footer matches the reference structure; strings editable | Pass | Live screenshot: brand, links, subscribe; every string from settings or Site Text |
| Social icons icon-only, labelled, new tab | Pass | Markup carries `aria-label`, `title`, `rel="noopener noreferrer"` |
| Contact items one per line with icons; mailto/tel | Pass | Rendered from the repeater; hrefs derived per row type |
| Subscribe validates, stores, reports inline | Pass | Endpoint: 403 no nonce, 200 valid, 200 duplicate, 400 invalid, honeypot accepted-but-unstored |
| Copyright unchanged in design, same background, no seam | Pass | Chromium: both wrappers computed `rgb(20, 24, 31)`, `border-top-width: 0px` |
| Partners renders above the footer, hidden when empty | Pass | Live: absent with no partners; Chromium: logos within the height cap, ratios intact |

## 3. Non-functional

| Check | Result | How |
|---|---|---|
| No PHP notices / warnings / deprecations, `WP_DEBUG` on | Pass | Six front-end templates and eleven admin screens plus ten CPT list and editor screens: log empty, after a canary proved the log works |
| No JavaScript console errors | Pass | 48 page-and-width combinations: zero real errors |
| 1920 / 1440 / 1280 / 1024 / 768 / 425 / 375 / 320 px | Pass | 48 combinations, zero horizontal scrollbars |
| Light and dark mode | Pass | Contrast sampled in both |
| Contrast ≥ 4.5:1 where colours changed | Pass | Copyright 6.58:1, footer links 16.54:1, headings 16.54:1, helper 6.58:1 — both modes |
| Section scripts load only where the section exists | Pass | Gallery and lightbox scripts absent under the default layout; subscribe present only where the newsletter column renders |
| All new strings translatable | Pass | Every user-visible string goes through `__()` / `esc_html__()` |
| Upgrade path: existing site unchanged until settings change | Pass | Migration gave an existing site Classic, a fresh install Modern |
| No orphaned option rows from deleted sections | Pass | Deleting a section drops its title, subtitle and anchor record |
| Coding standards | Pass | phpcs clean across 110 files |
| Static analysis | Pass | phpstan reports only two ignore-pattern mismatches that predate this work |

## 4. Not verified

Listed rather than ticked.

- **Firefox, Safari, Chrome Android, Safari iOS.** Only Chromium is
  available here. Everything used has broad support; `overflow-x: clip`
  and `:has()` (admin only) are the newest, and both degrade rather than
  break.
- **Lighthouse CLS and performance score.** No Lighthouse in this
  environment. CLS is addressed structurally — every gallery image carries
  width, height and an `aspect-ratio` — but the number is unmeasured.
- **Settings export/import round-trip (GR-10).** The theme has no
  export/import feature, so there is nothing to round-trip. If one is added
  later, the new option keys are `mk_footer_settings`, `mk_map_settings`,
  `mk_sections` and `mk_section_layout`.
- **A real 30-section builder load (FR-03.9).** Tested with the default
  twelve plus duplicates, not thirty.
- **Real uploaded SVG icons end to end.** The sanitiser is covered by unit
  tests against a file carrying every vector at once; no file has been
  through the WordPress media library in a browser.
- **Screen-reader pass.** Roles, labels and focus management are in place
  and focus return is verified programmatically, but no assistive
  technology was driven.

## 5. Open questions from the spec

Answered as the working assumptions in spec §15, except one, which the
client overrode directly:

- **OQ-1** — "200% bigger" and "100% bigger" read as 3× and 2×, and both
  exposed as px controls so the number can be tuned without a code change.
- **Projects layout default** — the spec has Grid as the default. The
  client supplied a reference recording and asked for that style as the
  default, so Featured work ships as **Showcase**, with Classic one click
  away.
