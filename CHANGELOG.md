# Changelog

All notable changes to this project will be documented in this file.

## [1.0.30] - 2026-09-08

### Changed

- Move SDK release instructions from public README to `RELEASE.md`.

## [1.0.29] - 2026-09-08

### Added

- Post-purchase admin redirect to `plugins.php` with `license_upgraded=1` or `license_saved=1` query args.

### Fixed

- **WordPress 6.7+** — avoid early text domain loading by reading plugin metadata with `get_plugin_data(..., false, false)` in `Loader`.
- **Admin callback page** — register hidden page with `load-{hook}` handler instead of rendering through `add_menu_page` + `remove_menu_page`, fixing `strip_tags(null)` and "not allowed to access this page" errors.
- **Plugin upgrade after purchase** — use `Automatic_Upgrader_Skin` and output buffering so `wp_safe_redirect()` works without "headers already sent" warnings.
- **Admin notices** — deduplicate error messages per request (`AdminNotice::add()`).
- **Purchase link** — properly URL-encode callback page parameter and `callback_url` query value.

### Changed

- Minimum PHP version raised from 5.4 to **7.2**.

## [1.0.28] - Previous release

See git history for earlier changes.
