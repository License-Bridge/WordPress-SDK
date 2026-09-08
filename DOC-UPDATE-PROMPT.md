# AI prompt — update License Bridge website documentation

Copy everything below the line into a new AI chat (with access to your docs CMS or repo).

---

You are updating the **License Bridge** public documentation at https://licensebridge.com/docs/ to match **WordPress SDK v1.0.29** (GitHub: https://github.com/License-Bridge/WordPress-SDK).

## Context

The SDK is installed via Composer:

```bash
composer require license-bridge/wordpress-sdk
```

Integration uses `Loader::register(__FILE__, $config)` and a plugin-specific helper (e.g. `my_license()`). The SDK automatically handles callback URL, license storage, OAuth, and premium upgrade. Developers must **not** manually wire internal classes.

## Pages to update

### 1. SDK Installation (`/docs/sdk/installation` or equivalent)

- Primary install method: **Composer** (`license-bridge/wordpress-sdk`)
- Requirements: **PHP 7.2+**, **WordPress 6.4+** (compatible with 6.7+)
- Mention `composer.json` + `composer.lock` should be committed; do not document copying `bridge/` folder manually

### 2. SDK Usage (`/docs/sdk/usage`)

Keep existing API examples (`purchase_link`, `license_exists`, `is_license_active`, `license`, `cancel_license`) — they are still correct.

**Add new section: Configuration**

Document `Loader::register()` config array:

| Key | Required | Default |
|-----|----------|---------|
| plugin-slug | Yes | — |
| license-product-slug | Yes | — |
| license-bridge-url | No | https://licensebridge.com |
| license-bridge-api-url | No | https://app.licensebridge.com |
| license-bridge-oauth-token-uri | No | /oauth/token |
| plugin-transient-cache-expire | No | 43200 |
| cache-expire | No | 3600 |

Include local dev example with custom `license-bridge-url` and `license-bridge-api-url`.

**Add new section: Post-purchase redirect**

After successful purchase, user is redirected to:

- `wp-admin/plugins.php?license_upgraded=1`
- `wp-admin/plugins.php?license_saved=1`

Explain that premium version on License Bridge must be **higher** than installed free version for upgrade to run.

**Update integration snippet** to include:

```php
require_once __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/vendor/license-bridge/wordpress-sdk/src/Boot/bootstrap.php';

$my_license = \LicenseBridge\WordPressSDK\Boot\Loader::register(__FILE__, [
    'plugin-slug'          => plugin_basename(__FILE__),
    'license-product-slug' => 'my-product-slug',
]);
```

Note: `Loader::register()` bootstraps all hooks — no manual PremiumBuy/PremiumUpdate setup.

### 3. Changelog / release notes (if exists)

**Version 1.0.29 (2026-09-08)**

- Fixed WordPress 6.7+ compatibility (plugin header loading)
- Fixed post-purchase admin callback (access denied, duplicate notices, redirect headers)
- Silent plugin upgrader during callback
- Improved purchase link URL encoding
- Minimum PHP raised to 7.2

### 4. Remove or archive outdated content

- Any docs referencing copying a local `bridge/` folder into plugins
- PHP 5.4 / 5.6 minimum requirements
- Manual `define('LB_URL', ...)` constant setup (replaced by `Loader::register()` config)
- Manual nonce + base64 callback URL construction (handled by `purchase_link()`)

## Tone and format

- Match existing License Bridge docs style (clear headings, code blocks, tables)
- Keep examples copy-paste ready
- Link to GitHub README: https://github.com/License-Bridge/WordPress-SDK/blob/master/README.md

## Do not change

- Product setup docs (create product, payment, landing page) unless they contradict SDK config key names
- API reference for OAuth/Product/License REST endpoints (separate from SDK)

When done, list every page you changed and a one-line summary per page.
