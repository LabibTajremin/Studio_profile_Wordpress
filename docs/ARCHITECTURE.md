# Architecture

**The plugin owns all data and business logic. The theme only renders.**
The theme (`themes/maapkathi-theme`) may call plugin functions/classes but
must never write to the database, register post types, or define fields.

## Plugin layout (`plugins/maapkathi-core/src/`)

- `Config/` — reads and validates the §5 constants.
- `PostTypes/` — CPT + taxonomy registration (§3.1).
- `Storage/` — `StorageAdapter` interface, `StorageFactory`,
  `Adapters/LocalStorageAdapter` (shipped default, §6).
- `Video/` — `VideoResolver`, the single service every template renders
  video through (§6.2).
- `Roles/` — `mk_admin` / `mk_editor` capabilities (§7).
- `Approval/` — `ContentVisibilityPolicy` (pure decision logic) +
  `ApprovalService` (mk_revisions queue, audit log) — §8.
- `Theme/` — `Accents`, `Patterns`, `Fonts`, `Motion` registries ported
  verbatim from the source app, plus `ThemeSettings` (the 31-setting
  registry) and `ThemeVarsBuilder` (CSS custom-property + data-* output).
- `Admin/` — the custom "Maapkathi" top-level menu (§9).
- `Inquiries/` — contact form handling (§3.2, §12).
- `Rest/` — `UploadController`, the chunked-upload REST endpoint (§6.1).
- `Support/` — `Database` (the three custom tables via `dbDelta()`, §3.6).

## Data model

Only three custom tables exist: `{prefix}mk_inquiries`,
`{prefix}mk_audit_log`, `{prefix}mk_revisions`. Everything else — projects,
services, team, testimonials, etc. — is native WordPress storage (custom
post types + post meta + a taxonomy), per §3.1.

## Why no page builder / no ACF Pro-only assumption

Page builders cannot reproduce the CSS-custom-property-driven theme engine
and add bloat the 1-CPU-core / 40-worker Hostinger plan can't absorb. ACF
Pro requires a paid license this build does not assume is available;
Carbon Fields (free, PHP-only, version-controlled) is the planned
substitute for repeaters and options pages.
