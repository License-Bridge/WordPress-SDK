<?php

declare(strict_types=1);

$GLOBALS['lb_sdk_options'] = [];
$GLOBALS['lb_sdk_http'] = [];
$GLOBALS['lb_sdk_http_responses'] = [];

function get_option($key, $default = false)
{
    return $GLOBALS['lb_sdk_options'][$key] ?? $default;
}

function update_option($key, $value)
{
    $GLOBALS['lb_sdk_options'][$key] = $value;

    return true;
}

function wp_remote_post($url, $args = [])
{
    $GLOBALS['lb_sdk_http'][] = compact('url', 'args');

    if ($GLOBALS['lb_sdk_http_responses'] !== []) {
        return array_shift($GLOBALS['lb_sdk_http_responses']);
    }

    return [
        'response' => ['code' => 500],
        'body'     => '',
    ];
}

function is_wp_error($value)
{
    return false;
}

require_once dirname(__DIR__) . '/src/Library/BridgeConfig.php';
require_once dirname(__DIR__) . '/src/Library/Credentials.php';
require_once dirname(__DIR__) . '/src/Library/OAuthProvisioner.php';
require_once dirname(__DIR__) . '/src/Library/Token.php';
