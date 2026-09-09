# WordPress-SDK

Official [License Bridge](https://licensebridge.com) WordPress SDK. Add it to your plugin or theme to enable:

- License storage and validation
- Secure checkout landing page (hosted by License Bridge)
- Automatic upgrade from free to premium after purchase
- Recurring payments (subscriptions)
- Automatic plugin updates for licensed customers

## Requirements

- **PHP** 7.2 or higher
- **WordPress** 6.4 or higher (tested up to 6.7+)
- **Composer** (recommended)

## Installation

```bash
composer require license-bridge/wordpress-sdk
```

Commit `composer.json` and `composer.lock`. Do not edit files inside `vendor/` directly.

## Quick integration

Create a helper function in your main plugin file (rename `my_license` to something unique to your plugin):

```php
if (!function_exists('my_license')) {
    function my_license()
    {
        global $my_license;

        if ($my_license) {
            return $my_license;
        }

        if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
            return null;
        }

        require_once __DIR__ . '/vendor/autoload.php';
        include __DIR__ . '/vendor/license-bridge/wordpress-sdk/src/Boot/bootstrap.php';

        $my_license = \LicenseBridge\WordPressSDK\Boot\Loader::register(__FILE__, [
            'plugin-slug'          => plugin_basename(__FILE__),
            'license-product-slug' => 'my-first-product',
        ]);

        return $my_license;
    }

    my_license();
}
```

`Loader::register()` automatically wires:

- Hidden admin callback page (post-purchase license storage)
- Premium plugin upgrade
- WordPress update API integration

You do **not** need to manually instantiate internal SDK classes.

## Configuration

Pass these keys to `Loader::register()` (merged with SDK defaults):

| Key | Required | Default | Description |
|-----|----------|---------|-------------|
| `plugin-slug` | Yes | — | `plugin_basename(__FILE__)` |
| `license-product-slug` | Yes | — | Product slug on License Bridge |
| `license-bridge-url` | No | `https://licensebridge.com` | Market / checkout base URL |
| `license-bridge-api-url` | No | `https://app.licensebridge.com` | API base URL (OAuth, license, updates) |
| `license-bridge-oauth-token-uri` | No | `/oauth/token` | OAuth token path |
| `plugin-transient-cache-expire` | No | `43200` (12h) | Plugin details cache (seconds) |
| `cache-expire` | No | `3600` (1h) | General cache (seconds) |

### Local / staging example

```php
$my_license = \LicenseBridge\WordPressSDK\Boot\Loader::register(__FILE__, [
    'plugin-slug'            => plugin_basename(__FILE__),
    'license-product-slug'   => 'my-first-product',
    'license-bridge-url'     => 'http://market.lb.test',
    'license-bridge-api-url' => 'https://your-tunnel.ngrok-free.app',
]);
```

## Usage

Access the SDK via your helper or global:

```php
$bridge = my_license();
$slug   = plugin_basename(__FILE__);
```

### Purchase link

```php
$link = $bridge->purchase_link($slug);
// https://licensebridge.com/market/my-first-product?callback_url=...
```

Use in a button:

```php
echo '<a href="' . esc_url($link) . '">Buy Premium</a>';
```

### Inline checkout (wp-admin modal) — SDK 2.0+

Keep customers inside WordPress admin with a billing modal. Supports **Paddle overlay** and **Stripe Elements**. PayPal still uses the hosted purchase link (redirect).

Fixed plan on the button:

```php
echo my_license()->checkout_button($slug, [
    'plugin-file' => __FILE__,
    'gateway'     => 'paddle', // or stripe
    'plan-slug'   => 'pro',
    'plan-type'   => 'annual',
    'label'       => 'Upgrade to Pro',
]);
```

Let the customer pick a plan:

```php
echo my_license()->checkout_button($slug, [
    'plugin-file'          => __FILE__,
    'gateway'              => 'stripe',
    'allow-plan-selection' => true,
]);
```

| Key | Description |
|-----|-------------|
| `plugin-file` | Main plugin file path (`__FILE__`) so SDK assets load from `vendor/license-bridge/wordpress-sdk/assets` |
| `gateway` | `paddle` or `stripe` |
| `plan-slug` | Fixed plan slug (optional when `allow-plan-selection` is true) |
| `plan-type` | `month`, `annual`, or `life` (optional; inferred from API when omitted) |
| `allow-plan-selection` | Show plan / billing-cycle selectors |
| `label` | Button text |

The hosted market link remains available as a fallback via `purchase_link()`.

### Check license

```php
if ($bridge->license_exists($slug)) {
    // Credentials stored locally
}

if ($bridge->is_license_active($slug)) {
    // Active license on License Bridge
}
```

### License details

```php
$details = $bridge->license($slug); // array|false
```

Example fields: `full_name`, `email`, `plan_type`, `active`, `subscribed`, `subscription`, etc.

### Cancel subscription

```php
if ($bridge->cancel_license($slug)) {
    // Cancelled on License Bridge
}
```

## Post-purchase flow

After checkout, License Bridge redirects the customer to your WordPress admin. The SDK:

1. Verifies the nonce
2. Stores `lk`, `client_id`, `client_secret`
3. Runs premium plugin upgrade (if a newer package is available)
4. Redirects to **Plugins** screen:
   - `wp-admin/plugins.php?license_upgraded=1` — upgrade ran
   - `wp-admin/plugins.php?license_saved=1` — license saved, no newer version

Show admin feedback:

```php
add_action('admin_notices', function () {
    if (isset($_GET['license_upgraded'])) {
        echo '<div class="notice notice-success"><p>Premium activated.</p></div>';
    }
});
```

**Important:** Premium version on License Bridge must be **higher** than the installed free version for auto-upgrade to run.

## Hooks

```php
// Before/after upgrade (callback handler)
apply_filters('before_upgrade_plugin_' . $plugin_slug, '');
apply_filters('after_upgrade_plugin_' . $plugin_slug, '');
```

## Example plugin

See [license-example-plugin](https://github.com/Djuki/license-example-plugin) for a full working integration.

## License

Copyright (c) License Bridge.

Licensed under the GNU General Public License v3.0.
