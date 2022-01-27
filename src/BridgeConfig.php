<?php

namespace LicenseBridge\WordPressSDK;

class BridgeConfig
{
    protected static $bridgeConfig = [];

    public static function setConfig($slug, $config)
    {
        $hash = md5($slug);
        $default = [
            'plugin-version'                 => '1.0.0',
            'option-prefix'                  => $hash . '_',
            'save-credentials-uri'           => 'license-store-values-' . $hash,
            'license-bridge-url'             => 'https://licensebridge.com',
            'license-bridge-api-url'             => 'https://app.licensebridge.com',
            'license-bridge-oauth-token-uri' => '/oauth/token',
            'plugin-transient-cache-expire'  => 43200, // value is in seconds (43200 seconds -> 12h)
            'cache-expire'              => 3600, // value is in seconds (3600 seconds -> 1h)
        ];

        self::$bridgeConfig[$slug] = array_merge($default, $config);
    }

    public static function getConfig($slug, $key)
    {
        return self::$bridgeConfig[$slug][$key] ?? null;
    }
}
