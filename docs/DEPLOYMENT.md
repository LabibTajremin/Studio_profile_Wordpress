# Deploying to Hostinger (Premium shared hosting)

Target: PHP 8.1+, MariaDB 10.6+, LiteSpeed. Everything below assumes the
hPanel interface.

Budget ~30 minutes for a first deploy.

---

## 1. Create the database

hPanel → **Databases → Management** → *Create new database*.

Note the database name, username, and password — Hostinger prefixes them
(e.g. `u123456789_maapkathi`). There is no `CREATE DATABASE` privilege from
the application, so this must exist before install.

## 2. Install WordPress

Either use hPanel → **Website → Auto Installer** (choose WordPress, then
skip its sample content), or upload a fresh WordPress release to
`public_html/` via **Files → File Manager** / SFTP.

## 3. Configure `wp-config.php`

Copy `deploy/wp-config-sample.php` from this repo to `public_html/wp-config.php`
and fill in:

- the database credentials from step 1
- fresh salts from <https://api.wordpress.org/secret-key/1.1/salt/>
- `$table_prefix` — keep whatever the installer generated

The template already sets the production hardening flags (`DISALLOW_FILE_EDIT`,
`WP_DEBUG` off, `FORCE_SSL_ADMIN`, revision limits, memory) and the
Maapkathi `MK_*` constants from §5.

**Outbound email:** fill in `MK_MAIL_FROM_EMAIL`/`MK_MAIL_FROM_NAME` and, if
you want email verification and password recovery to actually send
(recommended), set `MK_MAIL_DRIVER` to `1` and fill in `MK_SMTP_*` from
hPanel → Emails.

The `MK_ADMIN_*` lines are legacy/optional — see step 7, "First login,"
for the normal path.

## 4. Upload the plugin and theme

Upload from this repo:

| From | To |
|---|---|
| `plugins/maapkathi-core/` | `public_html/wp-content/plugins/maapkathi-core/` |
| `themes/maapkathi-theme/` | `public_html/wp-content/themes/maapkathi-theme/` |

Nothing needs to be built and there is no `composer install` step — the
plugin has **zero runtime dependencies** by design.

## 5. Root `.htaccess`

Copy `deploy/htaccess-sample.txt` to `public_html/.htaccess`. It protects
`wp-config.php` and dotfiles, disables directory listings and XML-RPC at
the server level, sets security and caching headers, and keeps
`Accept-Ranges` on so video seeking works.

## 6. Activate

wp-admin → **Plugins** → activate *Maapkathi Core*, then
**Appearance → Themes** → activate *Maapkathi Theme*.

On activation the plugin:

- runs the versioned migrations, creating `{prefix}mk_inquiries`,
  `{prefix}mk_audit_log`, `{prefix}mk_revisions`
- registers the `mk_admin` / `mk_editor` roles
- registers all post types and flushes rewrite rules

## 7. First login

Log into wp-admin with the account WordPress's own installer just created.
The plugin redirects you straight to a one-time **Maapkathi Setup** screen —
set your real username, email, password, and full name there (no
wp-config.php editing needed). The public site is already live the whole
time; this screen only ever gates wp-admin.

That email is trusted immediately (you're already an authenticated admin
setting it directly). Any *later* change to it goes through the
verification flow: a confirmation link is emailed to the new address, and
account recovery for a given address only works once it has been verified
this way — see `docs/CONFIGURATION.md`.

## 8. Permalinks

**Settings → Permalinks** → choose **Post name** → Save. The CPT routes
(`/work/{slug}`, `/services/{slug}`) depend on pretty permalinks.

If custom URLs 404 after a migration, re-save this screen — that
regenerates the rewrite rules.

## 9. Seed demo content (optional but recommended for a first look)

Hostinger Premium includes SSH. From `public_html`:

```bash
wp maapkathi seed
```

This creates a complete demo site — projects, services, team,
testimonials, clients, awards, stats, values, FAQs, hero slides — with
**locally generated demo images and logos** (no external image host).
Running it twice changes nothing; `wp maapkathi seed --fresh` rebuilds.

Without SSH, create the pages manually (Home, About, Team, Services,
Contact, Blog), assign the matching page templates, and set
**Settings → Reading → front page** to *Home*.

## 10. SSL

hPanel → **Security → SSL** → install the free certificate for the domain,
then in `wp-config.php` confirm `FORCE_SSL_ADMIN` is enabled and uncomment
the HTTPS redirect block in `.htaccess`.

Update **Settings → General** so both the WordPress Address and Site
Address use `https://`.

## 11. Post-deploy checklist

- [ ] `https://yourdomain.com/wp-json/maapkathi/v1/health` returns
      `{"status":"ok","database":"ok","schema":"ok"}`
- [ ] Homepage renders all sections with images
- [ ] `/work/`, `/services/`, `/about/`, `/team/`, `/contact/` all load
- [ ] A contact-form submission appears under **Maapkathi → Enquiries**
- [ ] **Maapkathi → Appearance** — change the accent, save, confirm the
      public site changes immediately
- [ ] Upload a real ~6-second MP4 on **Maapkathi → Hero** and confirm it
      plays (see "Video" below)
- [ ] Check the site on a phone

---

## Video on the hosting disk — operational limits

This build stores and serves **all media, including video, from the 20 GB
hosting disk** (a settled product decision).

**Why it works:** `LocalStorageAdapter::url()` returns a direct static file
URL, so LiteSpeed serves video itself and handles HTTP Range (206)
requests natively. No PHP worker is involved in playback. This matters —
the plan has 40 PHP workers, and a PHP-streamed video would occupy one for
the entire duration of playback.

**Verify Range support after deploy:**

```bash
curl -H "Range: bytes=0-1023" -o /dev/null -w "%{http_code}\n" \
  https://yourdomain.com/wp-content/uploads/maapkathi/2026/07/clip.mp4
# must print 206, not 200
```

**Limits to respect:**

- Hostinger caps `upload_max_filesize` / `post_max_size` (typically
  64–128 MB). Larger videos must go through the admin's chunked uploader,
  or straight to `wp-content/uploads/maapkathi/{yyyy}/{mm}/` over SFTP.
- Keep hero clips to ~6 seconds (the carousel advances); gallery clips to
  ~2 minutes. H.264 MP4, ≤1080p, 2–4 Mbps.
- MP4s should be "faststart" (moov atom at the front) or the browser waits
  for the whole file before the first frame. `ffmpeg -i in.mp4 -c copy
  -movflags +faststart out.mp4`.
- Watch disk and inode usage on the **Maapkathi → Dashboard** — the plan
  allows 400,000 inodes and image derivatives multiply file counts fast.
- Sustained high-traffic video streaming from shared hosting may run into
  Hostinger's fair-use terms. For a portfolio site's traffic this is a
  non-issue; if the site becomes video-heavy, switch
  `MK_STORAGE_DRIVER` to a CDN driver.

---

## Backup and restore

A restorable backup is **both** of these, taken together:

1. The database — hPanel → **Files → Backups**, or
   `wp db export backup.sql`
2. `wp-content/uploads/` — all media lives here

Restore: import the SQL, restore the uploads directory, then re-save
**Settings → Permalinks**.

## Updating the site later

Overwrite `plugins/maapkathi-core/` and `themes/maapkathi-theme/` with the
new versions. The migration runner detects a schema version bump on the
next page load and upgrades the database automatically — no
deactivate/reactivate needed, and re-running is harmless.

## Troubleshooting

| Symptom | Fix |
|---|---|
| Custom URLs 404 | Re-save **Settings → Permalinks** |
| Homepage shows blog posts | **Settings → Reading** → front page = *Home* |
| Images not appearing after seed | `wp-content/uploads` must be writable (755, owned by the web user) |
| "Not allowed to access this page" for an editor | Confirm the user's role is `mk_editor`; the role grants the literal `edit_posts` capability that WordPress's admin bootstrap requires |
| Video downloads instead of streaming | Check the Range test above; if it returns 200, confirm `.htaccess` has `Accept-Ranges bytes` |
| Changes not visible after saving Appearance | The theme-vars transient is busted on save; if you use a page cache (LiteSpeed Cache), purge it |
