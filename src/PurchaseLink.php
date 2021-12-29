<?php

namespace LicenseBridge\WordPressUpdater;

class PurchaseLink
{
    /**
     * Return Url for purchase a premium plugin
     */
    public static function get($slug)
    {
        $valuesUri = BridgeConfig::getConfig($slug, 'save-credentials-uri');
        $lbUrl = BridgeConfig::getConfig($slug, 'license-bridge-url');
        var_dump($lbUrl, $slug);
        $productSlug = BridgeConfig::getConfig($slug, 'license-product-slug');

        $nonce = wp_create_nonce($slug."_license_key_nonce");
        $callback = urlencode(admin_url('admin.php?page='.$valuesUri.'&_nonce=' . $nonce));
        return "${lbUrl}/product/${productSlug}?callback_url={$callback}";
    }
}