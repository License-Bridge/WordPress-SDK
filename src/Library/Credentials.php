<?php

namespace LicenseBridge\WordPressSDK\Library;

class Credentials
{
    /**
     * Check are credentials stored in WordPress
     *
     * @return bool
     */
    public static function checkCredentials($slug)
    {
        $prefix = BridgeConfig::getConfig($slug, 'option-prefix');

        $clientId = get_option($prefix . 'my_client_id');
        $secret = get_option($prefix . 'my_client_secret');

        return !empty($clientId) && !empty($secret);
    }

    public static function hasLicenseKey($slug): bool
    {
        $prefix = BridgeConfig::getConfig($slug, 'option-prefix');

        return ! empty(get_option($prefix . 'my_license_key'));
    }

    public static function needsOAuthProvisioning($slug): bool
    {
        return self::hasLicenseKey($slug) && ! self::checkCredentials($slug);
    }

    /**
     * Persist OAuth credentials using the same storage as the checkout callback flow.
     */
    public static function storeOAuthCredentials($slug, $licenseKey, $clientId, $clientSecret): void
    {
        $prefix = BridgeConfig::getConfig($slug, 'option-prefix');
        update_option($prefix . 'my_license_key', $licenseKey);
        update_option($prefix . 'my_client_id', $clientId);
        update_option($prefix . 'my_client_secret', $clientSecret);
        update_option($prefix . 'my_access_token', false);
    }

    /**
     * Get credentials
     */
    public static function get($slug): array
    {
        $prefix = BridgeConfig::getConfig($slug, 'option-prefix');
        return [
            'client_id'     => get_option($prefix . 'my_client_id'),
            'client_secret' => get_option($prefix . 'my_client_secret'),
            'license_key'   => get_option($prefix . 'my_license_key')
        ];
    }
}
