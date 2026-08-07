# Configuration

All runtime configuration is via `wp-config.php` constants (§5 of
`BUILD_INSTRUCTIONS.md`), validated at boot by `Maapkathi\Core\Config\Config`.

```php
define( 'MK_STORAGE_DRIVER', 3 );   // 3 = Local (shipped default)
define( 'MK_VIDEO_DRIVER', 0 );     // 0 = same as storage (shipped default)
define( 'MK_CACHE_DRIVER', 1 );     // 1 = transients
define( 'MK_MAIL_DRIVER', 0 );      // 0 = disabled, inbox only
define( 'MK_LOCAL_STORAGE_DIR', WP_CONTENT_DIR . '/uploads/maapkathi' );

define( 'MK_MAX_VIDEO_BYTES', 200 * 1024 * 1024 );
define( 'MK_MAX_IMAGE_BYTES', 10 * 1024 * 1024 );
define( 'MK_MAX_GIF_BYTES', 8 * 1024 * 1024 );
define( 'MK_CHUNK_BYTES', 2 * 1024 * 1024 );
define( 'MK_HERO_SLIDE_SECONDS', 6 );
define( 'MK_MAX_HERO_HOLD_SECONDS', 20 );

// Outbound email — sender identity + SMTP (SMTP only used when
// MK_MAIL_DRIVER=1 above)
define( 'MK_MAIL_FROM_EMAIL', 'info@maapkathi.com' );
define( 'MK_MAIL_FROM_NAME', 'Maapkathi Studio' );
define( 'MK_SMTP_HOST', 'smtp.hostinger.com' );
define( 'MK_SMTP_PORT', 587 );
define( 'MK_SMTP_USERNAME', 'info@maapkathi.com' );
define( 'MK_SMTP_PASSWORD', 'change-me' );
define( 'MK_SMTP_ENCRYPTION', 'tls' ); // tls | ssl
```

If a non-default storage driver is selected, the matching credential
constants (see `Config::check_driver_credentials()`) must also be defined,
or the plugin surfaces an admin notice at boot rather than failing silently
at upload time. The same applies to `MK_MAIL_DRIVER=1`: `Config::check_mail_credentials()`
requires `MK_SMTP_HOST`/`MK_SMTP_USERNAME`/`MK_SMTP_PASSWORD` in that case.

## First admin account

There is no `MK_ADMIN_*` bootstrap step to configure. The account
WordPress's own installer created is walked through a one-time **Maapkathi
Setup** screen the first time it opens wp-admin — username, email,
password, and full name, chosen on-site. The public site is unaffected the
entire time; only wp-admin is gated, and only until that screen is
completed once (`Maapkathi\Core\Setup\SetupWizard`, tracked by the
`mk_setup_complete` option).

## Email verification and account recovery

A user's registered email is never changed directly from the profile
screen (`Maapkathi\Core\Users\EmailVerification`). Submitting a new address
there emails a confirmation link to that *new* address; the change only
takes effect once that link is clicked. The address set during initial
setup is trusted immediately, since it's entered directly by an
already-authenticated administrator.

Account recovery (`wp-login.php?action=lostpassword`) is blocked for any
account whose current email has never been verified this way — an
unverified inbox has no business receiving a password-reset link.
Successfully completing a password reset via an emailed link counts as
verification too, since it already proves control of that mailbox (this is
how an admin-invited editor's first login gets verified, without a separate
step).

None of this sends real email unless `MK_MAIL_DRIVER=1` and `MK_SMTP_*` are
configured above — with the default driver 0, verification links and
password resets are silently never delivered.
