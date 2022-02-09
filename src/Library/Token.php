<?php

namespace LicenseBridge\WordPressSDK\Library;

class Token
{
    private static $instance;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * This method get oauth tokent from the database if exist, and check is it still valid.
     * If token do not exists, or if expired we will try to get a fresh one from License Bridge server.
     *
     * @return void
     */
    public function getLicenceOauthToken($slug)
    {
        if (!Credentials::checkCredentials($slug)) {
            return false;
        }
        $prefix = BridgeConfig::getConfig($slug, 'option-prefix');
        $lbUrl = BridgeConfig::getConfig($slug, 'license-bridge-api-url');
        $tokenUri = BridgeConfig::getConfig($slug, 'license-bridge-oauth-token-uri');

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
                    ],
                ]);

                if ($response['response']['code'] != 200) {
                    return false;
                }
                $jsonResponse = json_decode($response['body']);

                if (!$jsonResponse) {
                    return false;
                }

                $token = [
                    'access_token' => $jsonResponse->access_token ?? false,
                    'expires'      => $jsonResponse->expires_in != 0 ? time() + $jsonResponse->expires_in : 0,
                ];
                update_option($prefix . 'my_access_token', serialize($token));
            }
        } catch (\Exception $e) {
            return false;
        }

        return $token;
    }
}
