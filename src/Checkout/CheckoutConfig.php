<?php

namespace LicenseBridge\WordPressSDK\Checkout;

class CheckoutConfig
{
    private const DEFAULTS = [
        'license-bridge-url'     => 'https://licensebridge.com',
        'license-bridge-api-url' => 'https://app.licensebridge.com',
        'gateway'                => 'paddle',
        'allow-plan-selection'   => false,
        'show-order-summary'     => true,
        'label'                  => 'Buy Premium',
    ];

    public static function merge(array $config): array
    {
        $slug = $config['plugin-slug'] ?? '';

        if ($slug && class_exists('\LicenseBridge\WordPressSDK\Library\BridgeConfig')) {
            $stored = [
                'license-bridge-url'     => \LicenseBridge\WordPressSDK\Library\BridgeConfig::getConfig($slug, 'license-bridge-url'),
                'license-bridge-api-url' => \LicenseBridge\WordPressSDK\Library\BridgeConfig::getConfig($slug, 'license-bridge-api-url'),
                'save-credentials-uri' => \LicenseBridge\WordPressSDK\Library\BridgeConfig::getConfig($slug, 'save-credentials-uri'),
            ];
            $config = array_filter($stored) + $config;
        }

        return array_merge(self::DEFAULTS, array_filter($config, static function ($value) {
            return $value !== null && $value !== '';
        }));
    }

    public static function callbackUrl(array $config): string
    {
        $slug = $config['plugin-slug'];
        $valuesUri = $config['save-credentials-uri'] ?? 'license-store-values-' . md5($slug);
        $nonce = wp_create_nonce($slug . '_license_key_nonce');

        return base64_encode(admin_url('admin.php?page=' . urlencode($valuesUri) . '&_nonce=' . $nonce));
    }
}
