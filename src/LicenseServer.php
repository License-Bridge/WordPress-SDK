<?php

namespace LicenseBridge\WordPressSDK;

use WP_Error;

class LicenseServer
{
    /**
     * Remote.
     *
     * @var Remote
     */
    private $remote;

    /**
     * Singleton instance.
     *
     * @var LicenseServer
     */
    private static $instance;

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->remote = new Remote;
    }

    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Fetch plugin details from LicenseBridge API.
     *
     * @return array
     */
    public function fetchPluginDetails($slug)
    {
        $prefix = BridgeConfig::getConfig($slug, 'option-prefix');
        $lbUrl = BridgeConfig::getConfig($slug, 'license-bridge-api-url');
        $product = BridgeConfig::getConfig($slug, 'license-product-slug');
        $cache = BridgeConfig::getConfig($slug, 'plugin-transient-cache-expire');

        $tokenService = Token::instance();

        if (false === $remote = get_transient(md5('details'.$slug))) {
            if (!$token = $tokenService->getLicenceOauthToken($slug)) {
                
                return false;
            }
            $headers = [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token['access_token'],
                'LicenseKey'    => get_option($prefix . 'my_license_key'),
            ];

            $remote = wp_remote_post("{$lbUrl}/api/plugin/details/{$product}", [
                'headers' => $headers,
            ]);

            if (is_wp_error($remote)) {
                return $remote;
            }

            if (!$this->validResponse($remote)) {
                set_transient(md5('details'.$slug), null, $cache);
                return null;
            }
            
            set_transient(md5('details'.$slug), $remote, $cache);
        }
        
        return $remote;
    }

    /**
     * API call to view license details
     *
     * @param string $slug
     * @return array || false
     */
    public function getLicense($slug)
    {
        $credentials = Credentials::get($slug);
        $lbUrl = BridgeConfig::getConfig($slug, 'license-bridge-api-url');
        $prefix = BridgeConfig::getConfig($slug, 'option-prefix');
        $cacheTime = BridgeConfig::getConfig($slug, 'cache-expire');

        $cacheId = $prefix . '.getLicense.' . md5($slug);

        if (!($result = get_transient($cacheId))) {
            $token = Token::instance()->getLicenceOauthToken($slug);
            if ($token) {
                $headers = [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer ' . $token['access_token'],
                    'LicenseKey'    => $credentials['license_key'],
                ];

                $remote = wp_remote_get("{$lbUrl}/api/license/view/", [
                    'headers' => $headers
                ]);

                $result = json_decode($remote['body'], true);
                set_transient($cacheId, $result, $cacheTime);
            }
        }

        return $result ?? false;
    }

    /**
     * API call to cancel license
     *
     * @param string $slug
     * @return array
     */
    public function cancelLicense($slug)
    {
        $credentials = Credentials::get($slug);
        $lbUrl = BridgeConfig::getConfig($slug, 'license-bridge-api-url');
        $prefix = BridgeConfig::getConfig($slug, 'option-prefix');
        $token = new Token($slug);
        $token = Token::instance()->getLicenceOauthToken($slug);

        $headers = [
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $token['access_token'],
            'LicenseKey'    => $credentials['license_key'],
        ];

        $remote = wp_remote_get("{$lbUrl}/api/license/cancel/", [
            'headers' => $headers
        ]);

        $cacheId = $prefix . '.getLicense.' . md5($slug);
        delete_transient($cacheId);

        return json_decode($remote['body'], true);
    }

    /**
     * Check is response from server valid.
     *
     * @param array $remote
     * @return void
     */
    private function validResponse($remote)
    {
        return isset($remote['response']['code']) && $remote['response']['code'] == 200 && !empty($remote['body']);
    }
}
