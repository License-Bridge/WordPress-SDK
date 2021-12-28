<?php

namespace LicenseBridge\WordPressUpdater\Boot;

use LicenseBridge\WordPressUpdater\Credentials;
use LicenseBridge\WordPressUpdater\PurchaseLink;

class LicenseBridgeSDK
{
    private $sdkPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

    private static $_instances = [
        'sdk' => null,
        'link' => null,
    ];

    public function __construct()
    {
        require_once $this->sdkPath . 'BridgeConfig.php';
        require_once $this->sdkPath . 'Credentials.php';
        require_once $this->sdkPath . 'LicenseServer.php';
        require_once $this->sdkPath . 'PremiumBuy.php';
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
        if (self::$_instances['link'] === null) {
            self::$_instances['link'] = new PurchaseLink();
        }

        return new self::$_instances['link']->get($slug);
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
