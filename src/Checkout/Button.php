<?php

namespace LicenseBridge\WordPressSDK\Checkout;

class Button
{
    /**
     * Render a checkout button with billing modal (wp-admin only).
     *
     * @param array<string, mixed> $config
     */
    public static function render(array $config): string
    {
        if (! is_admin()) {
            return '';
        }

        $config = CheckoutConfig::merge($config);

        if (empty($config['plugin-file'])) {
            $config['plugin-file'] = \LicenseBridge\WordPressSDK\Library\BridgeConfig::getConfig(
                $config['plugin-slug'],
                'plugin-file'
            );
        }

        foreach (['plugin-slug', 'license-product-slug'] as $required) {
            if (empty($config[$required])) {
                return '';
            }
        }

        CheckoutAssets::register($config);

        $buttonId = 'lb-checkout-' . md5(wp_json_encode($config));
        $allowPlanSelection = ! empty($config['allow-plan-selection']);
        $gateway = in_array($config['gateway'], ['paddle', 'stripe'], true) ? $config['gateway'] : 'paddle';

        $data = [
            'pluginSlug'         => $config['plugin-slug'],
            'productSlug'        => $config['license-product-slug'],
            'gateway'            => $gateway,
            'planSlug'           => $config['plan-slug'] ?? '',
            'planType'           => $config['plan-type'] ?? '',
            'allowPlanSelection' => $allowPlanSelection ? '1' : '0',
            'showOrderSummary'   => ($config['show-order-summary'] ?? true) ? '1' : '0',
            'callbackUrl'        => CheckoutConfig::callbackUrl($config),
        ];

        $attrs = '';

        foreach ($data as $key => $value) {
            $attrs .= sprintf(' data-%s="%s"', esc_attr(self::kebab($key)), esc_attr((string) $value));
        }

        return sprintf(
            '<button type="button" class="button button-primary lb-checkout-button" id="%1$s"%2$s>%3$s</button>',
            esc_attr($buttonId),
            $attrs,
            esc_html($config['label'])
        );
    }

    private static function kebab(string $key): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $key));
    }
}
