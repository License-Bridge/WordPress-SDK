<?php

    include_once ABSPATH . 'wp-admin/includes/plugin.php';
    include_once 'utils.php';

    global $sdkName, $thisSdkVersion, $lb_plugins;

    $composer = file_get_contents(__DIR__."/../../composer.json");
    $jsonComposer = json_decode($composer, true);
    $thisSdkVersion = $jsonComposer['version'];
    
    $sdkName = 'LicenseBridgeSDK';
    $active_plugins = get_option('active_plugins');
    $lb_plugins = get_option('lb_registered_plugins');

    include_once 'Loader.php';
