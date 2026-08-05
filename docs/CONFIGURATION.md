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

// Optional admin bootstrap on first activation
define( 'MK_ADMIN_EMAIL', 'you@example.com' );
define( 'MK_ADMIN_PASSWORD', 'change-me-immediately' );
define( 'MK_ADMIN_NAME', 'Studio Admin' );
```

If a non-default storage driver is selected, the matching credential
constants (see `Config::check_driver_credentials()`) must also be defined,
or the plugin surfaces an admin notice at boot rather than failing silently
at upload time.
