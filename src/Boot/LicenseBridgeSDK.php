<?php

namespace LicenseBridge\WordPressSDK\Boot;

use LicenseBridge\WordPressSDK\Credentials;
use LicenseBridge\WordPressSDK\LicenseServer;
use LicenseBridge\WordPressSDK\PurchaseLink;

class LicenseBridgeSDK
{
    private $sdkPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

    private static $_instances = [
        'sdk' => null,
        'link' => null,
        'api' => null,
    ];

    public function __construct()
    {
        require_once $this->sdkPath . 'SlugInstance.php';
        require_once $this->sdkPath . 'BridgeConfig.php';
        require_once $this->sdkPath . 'Credentials.php';
        require_once $this->sdkPath . 'AdminNotice.php';
        require_once $this->sdkPath . 'LicenseServer.php';
        require_once $this->sdkPath . 'PremiumUpgrade.php';
        require_once $this->sdkPath . 'PremiumUpdate.php';
        require_once $this->sdkPath . 'PurchaseLink.php';
        require_once $this->sdkPath . 'Remote.php';
        require_once $this->sdkPath . 'Token.php';
    }

    public static function instance()
    {
        if (self::$_instances['sdk'] === null) {
            self::$_instances['sdk'] = new self();
        }

        return self::$_instances['sdk'];
    }

    public function getPurchaseLink($slug)
    {
        return PurchaseLink::get($slug);
    }

    public function server()
    {
        return LicenseServer::instance();
    }

    public function checkCredentials($slug)
    {
        return Credentials::checkCredentials($slug);
    }

    public function getCredentials($slug)
    {
        return Credentials::get($slug);
    }
}
