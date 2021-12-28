<?php

namespace LicenseBridge\WordPressUpdater;

class Token
{
    /**
     * Plugin slug
     */
    private $slug;

    public function __construct($slug)
    {
        $this->slug = $slug;
    }

    /**
     * This method get oauth tokent from the database if exist, and check is it still valid.
     * If token do not exists, or if expired we will try to get a fresh one from License Bridge server
     *
     * @return void
     */
    public function getLicenceOauthToken()
    {
        if (!Credentials::checkCredentials($this->slug)) {
            return false;
        }
        $prefix = BridgeConfig::getConfig($this->slug, 'option-prefix');
        $lbUrl = BridgeConfig::getConfig($this->slug, 'license-bridge-url');
        $tokenUri = BridgeConfig::getConfig($this->slug, 'license-bridge-oauth-token-uri');

        $token = false;

        try {
            if ($dbToken = get_option($prefix . 'my_access_token', false)) {
                $token = unserialize($dbToken);
            }

            if (!$token || $token['expires'] < time()) {
                $response = wp_remote_post($lbUrl . $tokenUri, [
                    'body' => [
                        'grant_type'    => 'client_credentials',
                        'client_id'     => get_option($prefix . 'my_client_id'),
                        'client_secret' => get_option($prefix . 'my_client_secret'),
                    ]
                ]);

                if ($response['response']['code'] != 200) {
                    new AdminNotice("We can't get key from Licence Bridge. The error has occurred.", 'error');
                    return false;
                }
                $jsonResponse = json_decode($response['body']);

                if (!$jsonResponse) {
                    new AdminNotice("We can't get key from Licence Bridge. The error has occurred.", 'error');
                    return false;
                }

                $token = [
                    'access_token' => $jsonResponse->access_token ?? false,
                    'expires'      => $jsonResponse->expires_in != 0 ? time() + $jsonResponse->expires_in : 0,
                ];
                update_option($prefix . 'my_access_token', serialize($token));
            }
        } catch (\Exception $e) {
            new AdminNotice("We can't get key from Licence Bridge. The error has occurred.", 'error');
        }

        return $token;
    }F
}
