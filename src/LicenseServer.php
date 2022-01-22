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
        if (false == $remote = get_transient($slug)) {
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
                $plugin_data = get_plugin_data(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $slug);

                return new WP_Error('404', "We could not get plugin information from License Bridge for \"{$plugin_data['Name']}\" plugin. Please check is your license expired.");
            }

            set_transient($slug, $remote, $cache);
        }

        return $remote;
    }

    public function viewLicense($slug)
    {
        $credentials = Credentials::get($slug);
        $lbUrl = BridgeConfig::getConfig($slug, 'license-bridge-api-url');
        $prefix = BridgeConfig::getConfig($slug, 'option-prefix');
        $cacheTime = BridgeConfig::getConfig($slug, 'view-cache-expire');

        $cacheId = $prefix . '.details.' . md5($slug);

        if (true || !($result = get_transient($cacheId))) {
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

                $result = json_decode($remote['body']);
                set_transient($cacheId, $result, $cacheTime);
            }
        }

        return $result ?? false;
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
