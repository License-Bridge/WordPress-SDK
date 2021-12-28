<?php

namespace LicenseBridge\WordPressUpdater;

use WP_Error;

class LicenseServer
{
    /**
     * Plugin slug
     */
    private $slug;

    /**
     * Token
     *
     * @var Token
     */
    private $token;

    /**
     * Remote
     *
     * @var Remote
     */
    private $remote;

    /**
     * Constructor
     */
    public function __construct($slug)
    {
        $this->slug = $slug;
        $this->token = new Token($slug);
        $this->remote = new Remote;
    }

    /**
     * Fetch plugin details from LicenseBridge API
     *
     * @return array
     */
    public function fetchPluginDetails()
    {
        $prefix = BridgeConfig::getConfig($this->slug, 'option-prefix');
        $lbUrl = BridgeConfig::getConfig($this->slug, 'license-bridge-url');
        $slug = BridgeConfig::getConfig($this->slug, 'license-product-slug');
        $cache = BridgeConfig::getConfig($this->slug, 'plugin-transient-cache-expire');

        if (false == $remote = get_transient($this->slug)) {
            if (!$token = $this->token->getLicenceOauthToken()) {
                return false;
            }
            $headers = [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token['access_token'],
                'LicenseKey'    => get_option($prefix . 'my_license_key'),
            ];

            $remote = wp_remote_post("{$lbUrl}/api/plugin/details/{$slug}", [
                'headers' => $headers
            ]);

            if (is_wp_error($remote)) {
                return $remote;
            }

            if (!$this->validResponse($remote)) {
                $plugin_data = get_plugin_data(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $this->slug);
                return new WP_Error('404', "We could not get plugin information from License Bridge for \"{$plugin_data['Name']}\" plugin. Please check is your license expired.");
            }

            set_transient($this->slug, $remote, $cache);
        }

        return $remote;
    }

    /**
     * Set token, used for mocking in unit testing
     *
     * @param Token $token
     * @return void
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Set remote, used for mocking in unit testing
     *
     * @param Remote $remote
     * @return void
     */
    public function setRemote($remote)
    {
        $this->remote = $remote;
    }

    /**
     * Check is response from server valid
     *
     * @param array $remote
     * @return void
     */
    private function validResponse($remote)
    {
        return isset($remote['response']['code']) && $remote['response']['code'] == 200 && !empty($remote['body']);
    }
}
