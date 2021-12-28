<?php

namespace LicenseBridge\WordPressUpdater;

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
