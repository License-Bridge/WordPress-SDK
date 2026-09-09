<?php

namespace LicenseBridge\WordPressSDK\Checkout;

class CheckoutAjax
{
    public static function register(): void
    {
        add_action('wp_ajax_lb_checkout_plans', [self::class, 'plans']);
        add_action('wp_ajax_lb_checkout_init', [self::class, 'init']);
    }

    public static function plans(): void
    {
        self::verify();

        $product = sanitize_text_field(wp_unslash($_POST['product'] ?? ''));
        $apiUrl = untrailingslashit(sanitize_text_field(wp_unslash($_POST['apiUrl'] ?? lbCheckoutApiUrl())));

        if ($product === '' || $apiUrl === '') {
            wp_send_json_error(['message' => __('Missing product configuration.', 'license-bridge')], 400);
        }

        $response = wp_remote_get($apiUrl . '/product-price-info/' . rawurlencode($product), [
            'timeout' => 20,
            'headers' => ['Accept' => 'application/json'],
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()], 500);
        }

        $status = wp_remote_retrieve_response_code($response);
        $rawBody = wp_remote_retrieve_body($response);
        $body = json_decode($rawBody, true);

        if (! is_array($body)) {
            wp_send_json_error([
                'message' => __('Unable to load plan information.', 'license-bridge'),
                'debug'   => [
                    'httpStatus' => $status,
                    'requestUrl' => $apiUrl . '/product-price-info/' . rawurlencode($product),
                    'response'   => wp_strip_all_tags(substr(trim($rawBody), 0, 300)),
                ],
            ], 500);
        }

        wp_send_json_success($body);
    }

    public static function init(): void
    {
        self::verify();

        $product = sanitize_text_field(wp_unslash($_POST['product'] ?? ''));
        $gateway = sanitize_text_field(wp_unslash($_POST['gateway'] ?? 'paddle'));
        $planSlug = sanitize_text_field(wp_unslash($_POST['planSlug'] ?? ''));
        $apiUrl = untrailingslashit(sanitize_text_field(wp_unslash($_POST['apiUrl'] ?? lbCheckoutApiUrl())));

        if ($product === '' || $planSlug === '' || $apiUrl === '') {
            wp_send_json_error(['message' => __('Missing checkout configuration.', 'license-bridge')], 400);
        }

        $payload = [
            'firstName'    => sanitize_text_field(wp_unslash($_POST['firstName'] ?? '')),
            'lastName'     => sanitize_text_field(wp_unslash($_POST['lastName'] ?? '')),
            'email'        => sanitize_email(wp_unslash($_POST['email'] ?? '')),
            'address'      => sanitize_text_field(wp_unslash($_POST['address'] ?? '')),
            'city'         => sanitize_text_field(wp_unslash($_POST['city'] ?? '')),
            'province'     => sanitize_text_field(wp_unslash($_POST['province'] ?? '')),
            'zip'          => sanitize_text_field(wp_unslash($_POST['zip'] ?? '')),
            'country'      => strtoupper(sanitize_text_field(wp_unslash($_POST['country'] ?? ''))),
            'planType'     => sanitize_text_field(wp_unslash($_POST['planType'] ?? '')),
            'callback_url' => sanitize_text_field(wp_unslash($_POST['callbackUrl'] ?? '')),
        ];

        if ($gateway === 'stripe') {
            $payload['stripeToken'] = sanitize_text_field(wp_unslash($_POST['stripeToken'] ?? ''));
        }

        $endpoint = sprintf(
            '%s/charge/%s/%s/%s',
            $apiUrl,
            rawurlencode($product),
            rawurlencode($gateway),
            rawurlencode($planSlug)
        );

        $response = wp_remote_post($endpoint, [
            'timeout' => 30,
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body'    => $payload,
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()], 500);
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status >= 400) {
            $message = is_array($body) && isset($body['message']) ? $body['message'] : __('Payment could not be started.', 'license-bridge');
            wp_send_json_error(['message' => $message], $status);
        }

        if (! is_array($body)) {
            wp_send_json_error(['message' => __('Unexpected response from payment server.', 'license-bridge')], 500);
        }

        wp_send_json_success($body);
    }

    private static function verify(): void
    {
        check_ajax_referer('lb_checkout', 'nonce');
    }
}

function lbCheckoutApiUrl(): string
{
    return is_array($GLOBALS['lbCheckout'] ?? null) ? (string) ($GLOBALS['lbCheckout']['apiUrl'] ?? '') : '';
}
