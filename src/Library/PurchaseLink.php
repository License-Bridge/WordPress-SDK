<?php

namespace LicenseBridge\WordPressSDK\Library;

class PurchaseLink
{
    /**
     * Return Url for purchase a premium plugin
     */
    public static function get($slug)
    {
        $valuesUri = BridgeConfig::getConfig($slug, 'save-credentials-uri');
        $lbUrl = BridgeConfig::getConfig($slug, 'license-bridge-url');
        $productSlug = BridgeConfig::getConfig($slug, 'license-product-slug');

        $nonce = wp_create_nonce($slug."_license_key_nonce");
        $callback = base64_encode(admin_url('admin.php?page='.$valuesUri.'&_nonce=' . $nonce));
        return "${lbUrl}/market/${productSlug}?callback_url={$callback}";
    }
}