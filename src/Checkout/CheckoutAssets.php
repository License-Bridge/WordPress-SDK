<?php

namespace LicenseBridge\WordPressSDK\Checkout;

class CheckoutAssets
{
    private static $registered = false;

    public static function register(array $config): void
    {
        if (self::$registered || ! is_admin()) {
            return;
        }

        self::$registered = true;

        $base = dirname(__DIR__, 2);
        $version = self::sdkVersion();
        $assetBase = self::assetBaseUrl($config);

        wp_register_style(
            'lb-checkout-admin',
            $assetBase . 'css/checkout-admin.css',
            [],
            $version
        );

        wp_register_script(
            'lb-checkout-admin',
            $assetBase . 'js/checkout-admin.js',
            ['jquery'],
            $version,
            true
        );

        wp_enqueue_style('lb-checkout-admin');
        wp_enqueue_script('lb-checkout-admin');

        wp_localize_script('lb-checkout-admin', 'lbCheckout', [
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('lb_checkout'),
            'apiUrl'    => untrailingslashit($config['license-bridge-api-url']),
            'product'   => $config['license-product-slug'],
            'messages'  => [
                'loading'       => __('Processing…', 'license-bridge'),
                'billingTitle'  => __('Billing details', 'license-bridge'),
                'paymentTitle'  => __('Payment', 'license-bridge'),
                'continue'      => __('Continue to payment', 'license-bridge'),
                'close'         => __('Close', 'license-bridge'),
                'errorGeneric'  => __('Checkout could not be completed. Please try again.', 'license-bridge'),
                'perMonth'      => __('per month', 'license-bridge'),
                'perAnnual'     => __('per year', 'license-bridge'),
                'lifetime'      => __('one-time (Lifetime)', 'license-bridge'),
                'trialThen'     => __('%d-day free trial, then %s', 'license-bridge'),
            ],
        ]);

        $GLOBALS['lbCheckout'] = [
            'apiUrl' => untrailingslashit($config['license-bridge-api-url']),
        ];

        CheckoutAjax::register();
        BillingModal::register();
    }

    private static function sdkVersion(): string
    {
        $composer = dirname(__DIR__, 2) . '/composer.json';

        if (! is_readable($composer)) {
            return '2.0.0';
        }

        $json = json_decode((string) file_get_contents($composer), true);

        return $json['version'] ?? '2.0.0';
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function assetBaseUrl(array $config): string
    {
        if (! empty($config['plugin-file']) && file_exists($config['plugin-file'])) {
            return trailingslashit(
                plugins_url('vendor/license-bridge/wordpress-sdk/assets', $config['plugin-file'])
            );
        }

        $sdkRoot = dirname(__DIR__, 2);
        $contentDir = realpath(WP_CONTENT_DIR);
        $realRoot = realpath($sdkRoot);

        if ($contentDir && $realRoot && str_starts_with($realRoot, $contentDir)) {
            $relative = ltrim(str_replace($contentDir, '', $realRoot), '/\\');

            return trailingslashit(content_url($relative . '/assets'));
        }

        return trailingslashit(content_url('vendor/license-bridge/wordpress-sdk/assets'));
    }
}
