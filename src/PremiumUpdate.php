<?php

namespace LicenseBridge\WordPressUpdater;

class PremiumUpdate
{
    /**
     * Plugin slug
     */
    private $slug;

    /**
     *TO load admin css only once
     */
    private static $cssIncluded = false;

    /**
     * License Server Provider
     *
     * @var LicenseServer
     */
    private $licenseServer;

    /**
     * Mark it af faled to acoid miltiple calls on one page load
     */
    private static $apiCalled = [];

    /**
     * Store plugin metadata
     */
    private static $plugin = [];

    /**
     * To move plugin folder once
     */
    private static $pluginFolderMoved = [];

    /**
     * Force update when download premium first time, even if version is the same.
     */
    private $forceUpdate = false;

    /**
     * Init hooks and create object
     */
    public function __construct($slug)
    {
        $this->slug = $slug;
        $this->init_hooks();
        $this->licenseServer = new LicenseServer($slug);
    }

    /**
     * Init hooks
     *
     * @return void
     */
    private function init_hooks()
    {
        add_filter('plugins_api', [$this, 'pluginPopupInfo'], 20, 3);
        add_filter('site_transient_update_plugins', [$this, 'licensePluginUpdate']);
        add_filter('upgrader_source_selection', [$this, 'upgraderMoveFolder'], 10, 4);
        if (self::$cssIncluded === false) {
            add_action('admin_head', [$this, 'pluginPopupCss']);
        }
    }

    /**
     * Opens popup with new plugin version informations
     * Attached to the plugins_api filter
     *
     * @param object $res
     * @param string $action
     * @param object $args
     * @return mixed
     */
    public function pluginPopupInfo($res, $action, $args)
    {
        // do nothing if this is not about getting plugin information
        if ($action !== 'plugin_information') {
            return false;
        }

        // do nothing if it is not our plugin
        if ($this->slug !== $args->slug) {
            return $res;
        }

        if ($remote = $this->licenseServer->fetchPluginDetails()) {
            $remote = json_decode($remote['body']);
            $res = new \stdClass();
            $res->name = $remote->name;
            $res->slug = $this->slug;
            $res->version = $remote->version;
            $res->tested = $remote->tested;
            $res->requires = $remote->requires;
            $res->author = $remote->author;
            $res->author_profile = $remote->author_uri;
            $res->download_link = $remote->file_url;
            $res->trunk = $remote->file_url;
            $res->last_updated = $remote->last_updated;
            $res->sections = [];
            if (!empty($remote->sections->description)) {
                $res->sections['description'] = $remote->sections->description;
            }
            if (!empty($remote->sections->installation)) {
                $res->sections['installation'] = $remote->sections->installation;
            }
            if (!empty($remote->sections->changelog)) {
                $res->sections['changelog'] = $remote->sections->changelog;
            }
            if (!empty($remote->sections->screenshots)) {
                $res->sections['screenshots'] = $remote->sections->screenshots;
            }

            $res->banners = [
                'low'  => isset($remote->banner) ? $remote->banner : 'https://wpengine.com/wp-content/uploads/2017/03/plugged-in-hero.jpg',
                'high' => isset($remote->banner) ? $remote->banner : 'https://wpengine.com/wp-content/uploads/2017/03/plugged-in-hero.jpg'
            ];
            return $res;
        }

        return false;
    }

    /**
     * Popupinfo image width fix
     */
    public function pluginPopupCss()
    {
        echo '<style>
        #section-description img, #section-changelog img {
            max-width: 100%;
        }
        </style>';
    }

    /**
     * Updateplugin to the latest version
     * Attached to the site_transient_update_plugins filter
     *
     * @param object $transient
     * @return object
     */
    public function licensePluginUpdate($transient)
    {
        if (empty($transient->checked) && !$this->forceUpdate) {
            return $transient;
        }

        if ((isset(static::$apiCalled[$this->slug]) && static::$apiCalled[$this->slug] && !$this->forceUpdate) || isset($transient->response[$this->slug])) {
            return $transient;
        }

        if (false == $remote = get_transient($this->slug)) {
            $remote = $this->licenseServer->fetchPluginDetails();
            static::$apiCalled[$this->slug] = true;

            if (is_wp_error($remote)) {
                new AdminNotice($remote->get_error_message(), 'error');
                return $transient;
            }
        }

        if ($remote) {
            $remote = json_decode($remote['body']);
            if ($this->newVersionAvailable($remote)) {

                $res = new \stdClass();
                $res->slug = $this->slug;
                $res->plugin = $this->slug;
                $res->new_version = $remote->version;
                $res->tested = $remote->tested;
                $res->package = $remote->file_url;
                $res->subfolder = $remote->subfolder;

                // If $transient doesn't exist - create it
                if (!$transient) {
                    $transient = new \stdClass;
                };
                $transient->response[$res->plugin] = $res;
                $transient->checked[$res->plugin] = $remote->version;
                static::$plugin[$this->slug] = $res;
            }
        }

        return $transient;
    }

    /**
     * Check is nre plugin version available or not
     *
     * @param object $remote
     * @return bool
     */
    private function newVersionAvailable($remote)
    {
        $pluginVersion = BridgeConfig::getConfig($this->slug, 'plugin-version');
        if ($this->forceUpdate) {
            return true;
        }
        return  version_compare($pluginVersion, $remote->version, '<') && version_compare($remote->requires, get_bloginfo('version'), '<');
    }

    /**
     * Set license server
     *
     * @param LicenseServer $licenseServer
     * @return void
     */
    public function setLicenseServer(LicenseServer $licenseServer)
    {
        $this->licenseServer = $licenseServer;
    }

    public function upgraderMoveFolder($source, $remote_source, $upgrader, $extra)
    {
        if (!isset(static::$plugin[$this->slug])) {
            return $source;
        }

        if (!isset($extra['plugin'])) {
            return $source;
        }

        if ($this->slug !== $extra['plugin']) {
            return $source;
        }

        if (static::$plugin[$this->slug] && static::$plugin[$this->slug]->subfolder !== '') {
            $source = trailingslashit($source) . trailingslashit(static::$plugin[$this->slug]->subfolder);
        }

        $pluginDir = BridgeConfig::getConfig($this->slug, 'plugin-directory');
        $newSource = trailingslashit($remote_source) . trailingslashit($pluginDir);

        global $wp_filesystem;

        if (!isset(static::$pluginFolderMoved[$this->slug]) && !$wp_filesystem->move($source, $newSource, true)) {
            return new \WP_Error('license_bridge', "License Server couldn't find subdirectory in repository.");
        }

        static::$pluginFolderMoved[$this->slug] = true;

        return $newSource;
    }

    /**
     * Set force update
     */
    public function setForceUpdate(bool $force)
    {
        $this->forceUpdate = $force;
    }
}
