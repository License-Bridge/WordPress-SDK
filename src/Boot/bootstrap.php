<?php

    include_once ABSPATH . 'wp-admin/includes/plugin.php';
    include_once 'utils.php';

    global $sdkName, $thisSdkVersion, $lb_plugins;

    $thisSdkVersion = '1.0.1';
    $sdkName = 'LicenseBridgeSDK';
    $active_plugins = get_option('active_plugins');
    $lb_plugins = get_option('lb_registered_plugins');

    include_once 'Loader.php';
