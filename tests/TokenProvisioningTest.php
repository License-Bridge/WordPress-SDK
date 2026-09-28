<?php

declare(strict_types=1);

namespace LicenseBridge\WordPressSDK\Tests;

use LicenseBridge\WordPressSDK\Library\BridgeConfig;
use LicenseBridge\WordPressSDK\Library\Credentials;
use LicenseBridge\WordPressSDK\Library\OAuthProvisioner;
use LicenseBridge\WordPressSDK\Library\Token;
use PHPUnit\Framework\TestCase;

class TokenProvisioningTest extends TestCase
{
    private string $slug = 'test-plugin/test-plugin.php';

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['lb_sdk_options'] = [];
        $GLOBALS['lb_sdk_http'] = [];
        $GLOBALS['lb_sdk_http_responses'] = [];

        BridgeConfig::setConfig($this->slug, [
            'license-product-slug'   => 'my-product',
            'license-bridge-api-url' => 'https://app.example.test',
            'provisioning-key'       => 'product-provisioning-key',
        ]);
    }

    public function test_existing_credentials_skip_provisioning(): void
    {
        $prefix = BridgeConfig::getConfig($this->slug, 'option-prefix');
        update_option($prefix . 'my_license_key', 'sk_existing');
        update_option($prefix . 'my_client_id', 'client-id');
        update_option($prefix . 'my_client_secret', 'client-secret');
        update_option($prefix . 'my_access_token', serialize([
            'access_token' => 'cached-token',
            'expires'      => time() + 3600,
        ]));

        $token = Token::instance()->getLicenceOauthToken($this->slug);

        $this->assertSame('cached-token', $token['access_token']);
        $this->assertSame([], $GLOBALS['lb_sdk_http']);
    }

    public function test_license_key_only_triggers_provisioning_and_stores_credentials(): void
    {
        $prefix = BridgeConfig::getConfig($this->slug, 'option-prefix');
        update_option($prefix . 'my_license_key', 'sk_imported');

        $GLOBALS['lb_sdk_http_responses'] = [[
            'response' => ['code' => 200],
            'body'     => json_encode([
                'success'       => true,
                'client_id'     => 'new-client-id',
                'client_secret' => 'new-client-secret',
                'license_key'   => 'sk_imported',
            ]),
        ]];

        $this->assertTrue(OAuthProvisioner::provision($this->slug));
        $this->assertSame('new-client-id', get_option($prefix . 'my_client_id'));
        $this->assertSame('new-client-secret', get_option($prefix . 'my_client_secret'));
        $this->assertSame('sk_imported', get_option($prefix . 'my_license_key'));
        $this->assertStringContainsString('/api/product/my-product/license/provision', $GLOBALS['lb_sdk_http'][0]['url']);
    }

    public function test_subsequent_calls_do_not_reprovision(): void
    {
        $prefix = BridgeConfig::getConfig($this->slug, 'option-prefix');
        update_option($prefix . 'my_license_key', 'sk_imported');
        update_option($prefix . 'my_client_id', 'client-id');
        update_option($prefix . 'my_client_secret', 'client-secret');

        $this->assertFalse(OAuthProvisioner::provision($this->slug));
        $this->assertFalse(Credentials::needsOAuthProvisioning($this->slug));
        $this->assertSame([], $GLOBALS['lb_sdk_http']);
    }

    public function test_token_flow_provisions_once_then_uses_oauth_token_endpoint(): void
    {
        $prefix = BridgeConfig::getConfig($this->slug, 'option-prefix');
        update_option($prefix . 'my_license_key', 'sk_imported');

        $GLOBALS['lb_sdk_http_responses'] = [
            [
                'response' => ['code' => 200],
                'body'     => json_encode([
                    'success'       => true,
                    'client_id'     => 'new-client-id',
                    'client_secret' => 'new-client-secret',
                    'license_key'   => 'sk_imported',
                ]),
            ],
            [
                'response' => ['code' => 200],
                'body'     => json_encode([
                    'access_token' => 'fresh-token',
                    'expires_in'   => 3600,
                    'token_type'   => 'Bearer',
                ]),
            ],
        ];

        $token = Token::instance()->getLicenceOauthToken($this->slug);

        $this->assertSame('fresh-token', $token['access_token']);
        $this->assertCount(2, $GLOBALS['lb_sdk_http']);
        $this->assertStringContainsString('/license/provision', $GLOBALS['lb_sdk_http'][0]['url']);
        $this->assertStringContainsString('/oauth/token', $GLOBALS['lb_sdk_http'][1]['url']);

        $GLOBALS['lb_sdk_http'] = [];
        $GLOBALS['lb_sdk_http_responses'] = [[
            'response' => ['code' => 200],
            'body'     => json_encode([
                'access_token' => 'cached-next',
                'expires_in'   => 3600,
                'token_type'   => 'Bearer',
            ]),
        ]];
        update_option($prefix . 'my_access_token', false);

        $token = Token::instance()->getLicenceOauthToken($this->slug);

        $this->assertSame('cached-next', $token['access_token']);
        $this->assertCount(1, $GLOBALS['lb_sdk_http']);
        $this->assertStringContainsString('/oauth/token', $GLOBALS['lb_sdk_http'][0]['url']);
    }
}
