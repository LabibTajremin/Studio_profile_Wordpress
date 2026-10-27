# Client handover

> Draft. Complete this once the admin CRUD screens (§9) are finished and a
> real install has been walked through end-to-end.

## What the client gets

- A single custom plugin (`maapkathi-core`) holding all content and
  business logic, and a single custom theme (`maapkathi-theme`) for
  presentation — swap the theme without losing any content or settings.
- A custom "Maapkathi" admin menu (not scattered across default WP menus)
  with everything needed to run the site day to day: projects, services,
  team, blog, hero carousel, appearance/motion tuning, site text, contact
  inbox, and user management.
- All media — images and video — lives on the hosting disk. No third-party
  storage account, API key, or monthly bill is required to keep the site
  running.

## Logging in

- Admin URL: `/wp-admin/` (or the custom login path, if the admin-shield
  toggle in Settings is enabled — the public header then shows a
  "Copy /login URL" control for whoever needs it).
- The first admin account is created from `MK_ADMIN_EMAIL` /
  `MK_ADMIN_PASSWORD` in `wp-config.php` (see `docs/CONFIGURATION.md`) and
  is forced to change its password on first login.

## Day-to-day tasks

- Adding a project/service/team member/testimonial: use the matching
  screen under the Maapkathi menu.
- Changing colours, fonts, or motion: Appearance → Theme / Motion. Every
  control updates the live site immediately on save — no developer needed.
- The hero always fills the whole screen, and whatever image, GIF or video
  you upload is scaled to cover it. There is deliberately no size or crop
  setting — you cannot accidentally make the hero small.
- Reading contact form submissions: the Approvals/Content/Settings area
  inbox (see the relevant admin screen once built out).

## Support boundaries

This is a standard WordPress + MariaDB site with one custom plugin and one
custom theme — any competent WordPress developer can maintain it going
forward. There is no proprietary hosting dependency: it runs on any
PHP 8.1+/MariaDB 10.6+ host.
