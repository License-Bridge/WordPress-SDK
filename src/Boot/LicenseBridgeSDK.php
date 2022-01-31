<?php

namespace LicenseBridge\WordPressSDK\Boot;

use LicenseBridge\WordPressSDK\Credentials;
use LicenseBridge\WordPressSDK\LicenseServer;
use LicenseBridge\WordPressSDK\PurchaseLink;

class LicenseBridgeSDK
{
    private $sdkPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

    private static $_instance = null;

    private function __construct()
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

    /**
     * Singleton instance.
     *
     * @return LicenseBridgeSDK
     */
    public static function instance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Get link to purchase a license.
     *
     * @param string $slug
     * @return string
     */
    public function purchase_link($slug)
    {
        return PurchaseLink::get($slug);
    }

    /**
     * Get license details from the API.
     *
     * @param string $slug
     * @return array
     */
    public function license($slug)
    {
        return LicenseServer::instance()->getLicense($slug)['license'] ?? null;
    }

    /**
     * Is license active
     *
     * @param string $slug
     * @return array
     */
    public function is_license_active($slug)
    {
        $license = LicenseServer::instance()->getLicense($slug);
        return $license['license']['active'] ?? false;
    }

    /**
     * Cancel a license via API.
     *
     * @param string $slug
     * @return bool
     */
    public function cancel_license($slug)
    {
        return LicenseServer::instance()->cancelLicense($slug)['success'] ?? false;
    }

    /**
     * Are license credentials exist.
     *
     * @param string $slug
     * @return bool
     */
    public function license_exists($slug)
    {
        return Credentials::checkCredentials($slug);
    }
}
