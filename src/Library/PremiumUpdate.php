<?php

namespace LicenseBridge\WordPressSDK\Library;

class PremiumUpdate
{

    /**
     * Plugin slug.
     */
    private static $slug;

    /**
     *TO load admin css only once.
     */
    private static $cssIncluded = false;

    /**
     * Store plugin metadata.
     */
    private static $plugin = [];

    /**
     * To move plugin folder once.
     */
    private static $pluginFolderMoved = [];

    /**
     * Force update when download premium first time, even if version is the same.
     */
    private static $forceUpdate = false;

    /**
     * Array with singleton instances.
     *
     * @var array
     */
    private static $instances = [];

    /**
     * Init hooks.
     *
     * @return void
     */
    public static function init_hooks($slug)
    {
        add_filter('plugins_api', function ($res, $action, $args) use ($slug) {
            return self::pluginPopupInfo($res, $action, $args, $slug);
        }, 20, 3);
        add_filter('site_transient_update_plugins', function ($transient) use ($slug) {
            return self::licensePluginUpdate($transient, $slug);
        });
        add_filter('upgrader_source_selection', function ($source, $remote_source, $upgrader, $extra) use ($slug) {
            return self::upgraderMoveFolder($source, $remote_source, $upgrader, $extra, $slug);
        }, 10, 4);

        if (self::$cssIncluded === false) {
            add_action('admin_head', [static::class, 'pluginPopupCss']);
        }
    }

    /**
     * Opens popup with new plugin version informations
     * Attached to the plugins_api filter.
     *
     * @param object $res
     * @param string $action
     * @param object $args
     * @return mixed
     */
    public static function pluginPopupInfo($res, $action, $args, $slug)
    {
        // do nothing if this is not about getting plugin information
        if ($action !== 'plugin_information') {
            return false;
        }

        // do nothing if it is not our plugin
        if ($slug !== $args->slug) {
            return $res;
        }

        if ($remote = LicenseServer::instance()->fetchPluginDetails($slug)) {
            $remote = json_decode($remote['body']);
            $res = new \stdClass();
            $res->name = $remote->name;
            $res->slug = $slug;
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
                'high' => isset($remote->banner) ? $remote->banner : 'https://wpengine.com/wp-content/uploads/2017/03/plugged-in-hero.jpg',
            ];

            return $res;
        }

        return false;
    }

    /**
     * Popupinfo image width fix.
     */
    public static function pluginPopupCss()
    {
        echo '<style>
        #section-description img, #section-changelog img {
            max-width: 100%;
        }
        </style>';
    }

    /**
     * Updateplugin to the latest version
     * Attached to the site_transient_update_plugins filter.
     *
     * @param object $transient
     * @return object
     */
    public static function licensePluginUpdate($transient, $slug)
    {
        if (isset($transient->checked[$slug])) {
            //return $transient;
        }

        if (empty($transient->checked) && !static::$forceUpdate) {
            return $transient;
        }

        $remote = LicenseServer::instance()->fetchPluginDetails($slug);

        if (is_wp_error($remote)) {
            new AdminNotice($remote->get_error_message(), 'error');

            return $transient;
        }

        if ($remote) {
            $remote = json_decode($remote['body']);
            if (self::newVersionAvailable($remote, $slug)) {
                $res = new \stdClass();
                $res->slug = $slug;
                $res->plugin = $slug;
                $res->new_version = $remote->version;
                $res->tested = $remote->tested;
                $res->package = $remote->file_url;
                $res->subfolder = $remote->subfolder;

                // If $transient doesn't exist - create it
                if (!$transient) {
                    $transient = new \stdClass;
                }
                $transient->response[$res->plugin] = $res;
                $transient->checked[$res->plugin] = $remote->version;
                static::$plugin[$slug] = $res;
            }
        }
        
        return $transient;
    }

    /**
     * Check is nre plugin version available or not.
     *
     * @param object $remote
     * @return bool
     */
    private static function newVersionAvailable($remote, $slug)
    {
        $pluginVersion = BridgeConfig::getConfig($slug, 'plugin-version');
        if (static::$forceUpdate) {
            return true;
        }

        return  version_compare($pluginVersion, $remote->version, '<') && version_compare($remote->requires, get_bloginfo('version'), '<');
    }

    public static function upgraderMoveFolder($source, $remote_source, $upgrader, $extra, $slug)
    {
        if (!isset(static::$plugin[$slug])) {
            return $source;
        }

        if (!isset($extra['plugin'])) {
            return $source;
        }

        if ($slug !== $extra['plugin']) {
            return $source;
        }

        if (static::$plugin[$slug] && static::$plugin[$slug]->subfolder !== '') {
            $source = trailingslashit($source) . trailingslashit(static::$plugin[$slug]->subfolder);
        }

        $pluginDir = BridgeConfig::getConfig($slug, 'plugin-directory');
        $newSource = trailingslashit($remote_source) . trailingslashit($pluginDir);

        global $wp_filesystem;

        if (!isset(static::$pluginFolderMoved[$slug]) && !$wp_filesystem->move($source, $newSource, true)) {
            return new \WP_Error('license_bridge', "License Server couldn't find subdirectory in repository.");
        }

        static::$pluginFolderMoved[$slug] = true;

        return $newSource;
    }

    /**
     * Set force update.
     */
    public static function setForceUpdate(bool $force)
    {
        static::$forceUpdate = $force;
    }
}
