# Theming

The entire visual system is driven by CSS custom properties set on `:root`
by `Maapkathi\Core\Theme\ThemeVarsBuilder::build()`, injected inline in
`<head>` by the theme's `functions.php` — no second request, no flash of
unstyled/wrong theme.

## Registries (ported verbatim from the source app)

- `Theme\Accents` — 24 accent colours + custom hex override.
- `Theme\Patterns` — 22 CSS/SVG background patterns.
- `Theme\Fonts` — 8 font pairings + the radius/density scales.
- `Theme\Motion` — the reveal/hover/transition style registries and
  `resolve_vars()` (preset → duration/ease/distance/stagger math).

**Do not retype these from `BUILD_INSTRUCTIONS.md`.** If the source app's
registries change, re-copy from
`LabibTajremin/MapKathi_Web_App/src/presentation/theme/`.

## The 32 settings

`Theme\ThemeSettings::defaults()` is the single source of truth for all 32
`theme_settings` columns (§11.1). The Appearance admin screen
(`Admin/views/appearance.php`) renders all 30 visible controls from this
same registry, so the admin UI can never drift out of sync with what the
var builder actually reads. See `BUILD_INSTRUCTIONS.md` §11 for why this
matters — the source app itself ships 17 dormant settings, which this
build is required not to repeat.

## Cache invalidation

`ThemeVarsBuilder::build()` caches its output in a transient
(`ThemeSettings::CACHE_KEY`), busted by `ThemeSettings::bust_cache()` on
every option save. An admin never needs to hard-refresh after saving.
