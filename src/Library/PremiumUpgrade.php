<?php

namespace LicenseBridge\WordPressSDK\Library;

use Plugin_Upgrader;

class PremiumUpgrade
{
    public static function init_hooks($slug)
    {
        add_action('admin_menu', function () use ($slug) {
            $url = BridgeConfig::getConfig($slug, 'save-credentials-uri');
            add_menu_page(
                'License Bridge Store',
                'License Bridge Store',
                'manage_options',
                $url,
                function () use ($slug) {
                    self::saveLicenseKey($slug);
                }
            );
            remove_menu_page($url);
        });
    }

    /**
     * After the plugin user purchase the premium plugin version
     * It will be redirected to this method to store his credencials:
     *  - license key
     *  - oauth client id
     *  - oauth clinet secret.
     *
     * @return void
     */
    public static function saveLicenseKey($slug)
    {
        if (!wp_verify_nonce($_REQUEST['_nonce'], $slug . '_license_key_nonce')) {
            return;
        }
        $prefix = BridgeConfig::getConfig($slug, 'option-prefix');
        // Check license key and save it
        update_option($prefix . 'my_license_key', $_REQUEST['lk']);
        update_option($prefix . 'my_client_id', $_REQUEST['client_id']);
        update_option($prefix . 'my_client_secret', $_REQUEST['client_secret']);
        update_option($prefix . 'my_access_token', false);

        // Delete viewCache ID
        $cacheId = $prefix . '.details.' . md5($slug);
        delete_transient($cacheId);

        echo apply_filters('before_upgrade_plugin_' . $slug, '');
        self::upgradePlugin($slug);
        echo apply_filters('after_upgrade_plugin_' . $slug, '');
    }

    /**
     * Upgrade and activate the plugin.
     *
     * @param string $plugin_slug
     * @return bool
     */
    public static function upgradePlugin($slug)
    {
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        wp_cache_flush();

        $upgrader = new Plugin_Upgrader();
        PremiumUpdate::init_hooks($slug);
        PremiumUpdate::setForceUpdate(true);
        $upgraded = $upgrader->upgrade($slug);
        activate_plugin($slug);
        $upgrader->maintenance_mode(false);

        PremiumUpdate::setForceUpdate(false);
        return $upgraded;
    }
}
