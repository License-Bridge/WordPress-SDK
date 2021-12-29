<?php

namespace LicenseBridge\WordPressUpdater\Boot;

use LicenseBridge\WordPressUpdater\BridgeConfig;

class Loader
{
    public function __construct()
    {
        add_action('activated_plugin', [$this, 'reorder_plugins']);
    }

    public static function register($pluginFilePath, $plugin)
    {
        global $thisSdkVersion;

        

        register_deactivation_hook($pluginFilePath, function () use ($plugin) {
            global $lb_plugins;

            $updated = array_filter($lb_plugins, function ($item) use ($plugin) {
                return $item['plugin-slug'] != $plugin['plugin-slug'];
            });

            update_option(
                'lb_registered_plugins',
                $updated
            );

            $latest = get_option('lb_latest_sdk_plugin', null);

            if (isset($latest['plugin-slug']) && $latest['plugin-slug'] == $plugin['plugin-slug']) {
                update_option('lb_latest_sdk_plugin', null);
            }
        });

        $saved = get_option('lb_registered_plugins', []);

        $pluginInfo = $plugin + [
            'sdk_version' => $thisSdkVersion,
            'sdk_path' => self::get_sdk_path(plugin_dir_path($pluginFilePath)),
        ];

        $found = false;
        $updated = array_filter(array_map(function ($item) use ($pluginInfo) {
            
            if ($item['plugin-slug'] === $pluginInfo['plugin-slug']) {
                $found = true;
                // To update version dinamically we return new version
                return $pluginInfo;
            }

            return $item;
        }, $saved));

        if (!$found) {
            $updated = array_unique(array_merge($updated, [$pluginInfo]), SORT_REGULAR);
        }

        $updated = array_filter($updated, function ($plugin) {
            return is_plugin_active($plugin['plugin-slug']);
        });

        update_option(
            'lb_registered_plugins',
            !empty($updated) ? ($updated) : [$pluginInfo]
        );
        self::reorder_plugins(is_plugin_active($plugin['plugin-slug']));
        self::load_latest_sdk();
        
        if (class_exists(LicenseBridgeSDK::class)) {

            $sdk = LicenseBridgeSDK::instance();
            BridgeConfig::setConfig($plugin['plugin-slug'], $plugin);
            return $sdk;
        }
    }

    private static function get_sdk_path($rootPath)
    {
        global $sdkName;

        return $rootPath . "vendor/license-bridge/wordpress-sdk/src/Boot/{$sdkName}.php";
    }

    private static function reorder_plugins($only_active = true)
    {
        global $lb_plugins;

        $newest_version = '0.0.0';
        $latestSdk = null;

        if (empty($lb_plugins)) {
            return;
        }
        foreach ($lb_plugins as $plugin) {
            if ($only_active && !is_plugin_active($plugin['plugin-slug'])) {
                continue;
            }
            if ($latestSdk === null) {
                $latestSdk = $plugin;
            }
            if (isset($plugin['sdk_version']) && version_compare($plugin['sdk_version'], $newest_version) >= 0) {
                $newest_version = $plugin['sdk_version'];
                $latestSdk = $plugin;
            }
        }
        if ($latestSdk === null) {
            return;
        }

        update_option('lb_latest_sdk_plugin', $latestSdk);
        $active_plugins = array_unique(array_filter(get_option('active_plugins')));

        if (lb_array_empty($active_plugins)) {
            // If this is first plugin no need to reorder
            return;
        }

        
        $plugin_key = array_search($latestSdk['plugin-slug'], $active_plugins);
        array_splice($active_plugins, $plugin_key, 1);
        array_unshift($active_plugins, $latestSdk['plugin-slug']);
        update_option('active_plugins', array_unique(array_filter($active_plugins)));
    }

    private static function load_latest_sdk()
    {
        global $thisSdkVersion;

        $plugin = get_option('lb_latest_sdk_plugin');
        $version = isset($plugin['sdk_version']) ? $plugin['sdk_version'] : $thisSdkVersion;

        
        if ($plugin && isset($plugin['sdk_version']) && isset($plugin['sdk_path'])) {
            include_once $plugin['sdk_path'];

            return;
        }
    }
}
