<?php

namespace LicenseBridge\WordPressSDK\Library;

class OAuthProvisioner
{
    /**
     * Request OAuth credentials for an imported license that only has a license key stored locally.
     */
    public static function provision($slug): bool
    {
        if (! Credentials::hasLicenseKey($slug) || ! Credentials::needsOAuthProvisioning($slug)) {
            return false;
        }

        $provisioningKey = BridgeConfig::getConfig($slug, 'provisioning-key');

        if (empty($provisioningKey)) {
            return false;
        }

        $prefix = BridgeConfig::getConfig($slug, 'option-prefix');
        $lbUrl = rtrim((string) BridgeConfig::getConfig($slug, 'license-bridge-api-url'), '/');
        $product = BridgeConfig::getConfig($slug, 'license-product-slug');
        $licenseKey = get_option($prefix . 'my_license_key');
        $uri = sprintf('/api/product/%s/license/provision', rawurlencode((string) $product));

        $response = wp_remote_post($lbUrl . $uri, [
            'body' => [
                'license_key'      => $licenseKey,
                'provisioning_key' => $provisioningKey,
            ],
            'headers' => [
                'Accept' => 'application/json',
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        if (! isset($response['response']['code']) || (int) $response['response']['code'] !== 200) {
            return false;
        }

        $json = json_decode($response['body'], true);

        if (empty($json['success']) || empty($json['client_id']) || empty($json['client_secret'])) {
            return false;
        }

        Credentials::storeOAuthCredentials(
            $slug,
            $json['license_key'] ?? $licenseKey,
            $json['client_id'],
            $json['client_secret']
        );

        return true;
    }
}
