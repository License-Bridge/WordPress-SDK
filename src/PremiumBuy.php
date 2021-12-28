<?php

namespace LicenseBridge\WordPressUpdater;

use Plugin_Upgrader;

class PremiumBuy
{

    /**
     * Plugin slug
     */
    private $slug;

    /**
     * Creates admin page for storing credentials
     */
    public function __construct($slug)
    {
        $this->slug = $slug;
        add_action('admin_menu', [$this, 'licenseStoreValues']);
    }

    /**
     * Creates admin menu page and hide from main menu
     *
     * @return void
     */
    public function licenseStoreValues()
    {
        $url = BridgeConfig::getConfig($this->slug, 'save-credentials-uri');
        add_menu_page('License Bridge Store', 'License Bridge Store', 'manage_options', $url, [$this, 'saveLicenseKey']);
        remove_menu_page($url);
    }

    /**
     * After the plugin user purchase the premium plugin version
     * It will be redirected to this method to store his credencials:
     *  - license key
     *  - oauth client id
     *  - oauth clinet secret
     *
     * @return void
     */
    public function saveLicenseKey()
    {
        if (!wp_verify_nonce($_REQUEST['_nonce'], $this->slug."_license_key_nonce")) {
            return;
        }
        $prefix = BridgeConfig::getConfig($this->slug, 'option-prefix');
        // Check license key and save it
        update_option($prefix . 'my_license_key', $_REQUEST['lk']);
        update_option($prefix . 'my_client_id', $_REQUEST['client_id']);
        update_option($prefix . 'my_client_secret', $_REQUEST['client_secret']);
        update_option($prefix . 'my_access_token', false);

        // Delete viewCache ID
        $cacheId = $prefix . '.details.' . md5($this->slug);
        delete_transient($cacheId);

        echo apply_filters('before_upgrade_plugin_'.$this->slug, '');
        $this->upgradePlugin($this->slug);
        echo apply_filters('after_upgrade_plugin_' . $this->slug, '');
    }

    /**
     * Upgrade and activate the plugin
     *
     * @param string $plugin_slug
     * @return bool
     */
    public function upgradePlugin($plugin_slug)
    {
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        wp_cache_flush();

        $upgrader = new Plugin_Upgrader();
        $update = new PremiumUpdate($this->slug);
        $update->setForceUpdate(true);
        $upgraded = $upgrader->upgrade($plugin_slug);
        activate_plugin($plugin_slug);
        $upgrader->maintenance_mode(false);
        return $upgraded;
    }

    /**
     * Only for unit tests
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }
}
