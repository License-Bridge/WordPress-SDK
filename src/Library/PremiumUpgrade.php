<?php

namespace LicenseBridge\WordPressSDK\Library;

use Plugin_Upgrader;

class PremiumUpgrade
{
    public static function init_hooks($slug)
    {
        add_action('admin_menu', function () use ($slug) {
            $url = BridgeConfig::getConfig($slug, 'save-credentials-uri');
            $page_hook = add_menu_page(
                'License Bridge Store',
                'License Bridge Store',
                'manage_options',
                $url,
                '__return_empty_string'
            );
            remove_menu_page($url);
            add_action('load-' . $page_hook, function () use ($slug) {
                self::handleLicenseCallbackPage($slug);
            });
        });
    }

    /**
     * Process the post-purchase callback before admin-header is rendered.
     *
     * @param string $slug
     * @return void
     */
    public static function handleLicenseCallbackPage($slug)
    {
        global $title;
        $title = 'License Bridge Store';

        if (!wp_verify_nonce($_REQUEST['_nonce'] ?? '', $slug . '_license_key_nonce')) {
            wp_die(esc_html__('Invalid security token.', 'license-bridge'));
        }

        self::storeLicenseCredentials($slug);
        self::clearLicenseCaches($slug);
        delete_site_transient('update_plugins');

        ob_start();
        $upgraded = self::upgradePlugin($slug);
        ob_end_clean();

        $query_arg = $upgraded ? 'license_upgraded=1' : 'license_saved=1';
        wp_safe_redirect(admin_url('plugins.php?' . $query_arg));
        exit;
    }

    /**
     * After the plugin user purchase the premium plugin version
     * It will be redirected to this method to store his credencials:
     *  - license key
     *  - oauth client id
     *  - oauth clinet secret.
     *
     * @param string $slug
     * @return void
     */
    public static function saveLicenseKey($slug)
    {
        if (!wp_verify_nonce($_REQUEST['_nonce'] ?? '', $slug . '_license_key_nonce')) {
            return;
        }

        self::storeLicenseCredentials($slug);
        self::clearLicenseCaches($slug);

        echo apply_filters('before_upgrade_plugin_' . $slug, '');
        self::upgradePlugin($slug);
        echo apply_filters('after_upgrade_plugin_' . $slug, '');
    }

    /**
     * @param string $slug
     * @return void
     */
    private static function storeLicenseCredentials($slug)
    {
        $prefix = BridgeConfig::getConfig($slug, 'option-prefix');
        update_option($prefix . 'my_license_key', $_REQUEST['lk']);
        update_option($prefix . 'my_client_id', $_REQUEST['client_id']);
        update_option($prefix . 'my_client_secret', $_REQUEST['client_secret']);
        update_option($prefix . 'my_access_token', false);
    }

    /**
     * @param string $slug
     * @return void
     */
    private static function clearLicenseCaches($slug)
    {
        $prefix = BridgeConfig::getConfig($slug, 'option-prefix');
        delete_transient($prefix . '.details.' . md5($slug));
        delete_transient($prefix . '.getLicense.' . md5($slug));
    }

    /**
     * Upgrade and activate the plugin.
     *
     * @param string $slug
     * @return bool|\WP_Error|null
     */
    public static function upgradePlugin($slug)
    {
        WP_Filesystem();
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        include_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';

        wp_cache_flush();

        $upgrader = new Plugin_Upgrader(new \Automatic_Upgrader_Skin());
        PremiumUpdate::init_hooks($slug);
        PremiumUpdate::setForceUpdate(true);
        $upgraded = $upgrader->upgrade($slug);

        if (!is_wp_error($upgraded) && !is_plugin_active($slug)) {
            activate_plugin($slug);
        }

        $upgrader->maintenance_mode(false);
        PremiumUpdate::setForceUpdate(false);

        return $upgraded;
    }
}
