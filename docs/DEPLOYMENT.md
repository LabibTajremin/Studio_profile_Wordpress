# Deployment (Hostinger shared/Premium)

> Draft — written from `BUILD_INSTRUCTIONS.md` §3.6/§6.1/§14; not yet
> verified against a real Hostinger account in this session.

## 1. Database

hPanel → **Databases → Management** → create a new MySQL/MariaDB database
and user. Hostinger runs MariaDB 10.6+; there is no `CREATE DATABASE`
privilege from the app itself — the database must exist before install.

Copy the generated host, database name, username, and password into
`wp-config.php`:

```php
define( 'DB_NAME', '...' );
define( 'DB_USER', '...' );
define( 'DB_PASSWORD', '...' );
define( 'DB_HOST', 'localhost' );
```

## 2. Files

Upload WordPress core as normal, then this repo's `plugins/maapkathi-core`
into `wp-content/plugins/` and `themes/maapkathi-theme` into
`wp-content/themes/`. Activate both from wp-admin.

## 3. `wp-config.php`

Add the §5 constants from `docs/CONFIGURATION.md`. Leave
`MK_STORAGE_DRIVER=3` and `MK_VIDEO_DRIVER=0` — this is the decided,
shipped configuration (DEC-2).

## 4. Operational limits (video on shared disk — DEC-2)

- Video is served as a static file by LiteSpeed, never through PHP — this
  is what makes it viable on 40 PHP workers / 1 CPU core (§6.1).
- Large uploads (>~64–128MB, Hostinger's typical `post_max_size`) must go
  through the chunked-upload endpoint in the admin, not a single POST.
  For files too large even for that, use SFTP directly into
  `wp-content/uploads/maapkathi/{yyyy}/{mm}/` and register the media item
  manually.
- Watch disk usage (20GB) and inode count (400,000) on the admin
  Dashboard; image derivatives multiply file counts quickly.
- Some hosts' ToS restricts sustained video streaming from shared hosting
  — confirm Hostinger's current terms before high-traffic launch.

## 5. Backup / restore

Use hPanel's built-in backup tool (or a scheduled `wp db export` +
`rsync`/SFTP of `wp-content/uploads/maapkathi`) — both the database and the
local media directory must be backed up together; one without the other
is not a restorable backup.

## 6. Security hardening checklist

See §13 of `BUILD_INSTRUCTIONS.md` and `docs/BUILD_STATUS.md` for what is
implemented vs. still to verify on a live install (security headers,
`DISALLOW_FILE_EDIT`, XML-RPC disabled, etc.).
