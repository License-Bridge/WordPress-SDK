<?php

namespace LicenseBridge\WordPressSDK\Checkout;

class BillingModal
{
    public static function register(): void
    {
        add_action('admin_footer', [self::class, 'render']);
    }

    public static function render(): void
    {
        $user = wp_get_current_user();
        ?>
        <div id="lb-checkout-modal" class="lb-checkout-modal" aria-hidden="true">
            <div class="lb-checkout-modal__backdrop" data-lb-checkout-close></div>
            <div class="lb-checkout-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="lb-checkout-modal-title">
                <div class="lb-checkout-modal__header">
                    <h2 id="lb-checkout-modal-title"><?php esc_html_e('Complete your purchase', 'license-bridge'); ?></h2>
                    <button type="button" class="lb-checkout-modal__close" data-lb-checkout-close aria-label="<?php esc_attr_e('Close', 'license-bridge'); ?>">&times;</button>
                </div>
                <div class="lb-checkout-modal__body">
                    <div id="lb-checkout-summary" class="lb-checkout-summary" hidden>
                        <div class="lb-checkout-summary__title"><?php esc_html_e('Order summary', 'license-bridge'); ?></div>
                        <dl class="lb-checkout-summary__list">
                            <div class="lb-checkout-summary__row">
                                <dt><?php esc_html_e('Plan', 'license-bridge'); ?></dt>
                                <dd id="lb-checkout-summary-plan"></dd>
                            </div>
                            <div class="lb-checkout-summary__row">
                                <dt><?php esc_html_e('Billing', 'license-bridge'); ?></dt>
                                <dd id="lb-checkout-summary-cycle"></dd>
                            </div>
                            <div class="lb-checkout-summary__row lb-checkout-summary__row--price">
                                <dt><?php esc_html_e('Total', 'license-bridge'); ?></dt>
                                <dd id="lb-checkout-summary-price"></dd>
                            </div>
                        </dl>
                    </div>
                    <form id="lb-checkout-form" class="lb-checkout-form">
                        <div class="lb-checkout-field lb-checkout-field--plan" hidden>
                            <label for="lb-checkout-plan"><?php esc_html_e('Plan', 'license-bridge'); ?></label>
                            <select id="lb-checkout-plan" name="planSlug"></select>
                        </div>
                        <div class="lb-checkout-field lb-checkout-field--plan-type" hidden>
                            <label for="lb-checkout-plan-type"><?php esc_html_e('Billing cycle', 'license-bridge'); ?></label>
                            <select id="lb-checkout-plan-type" name="planType"></select>
                        </div>
                        <div class="lb-checkout-grid">
                            <div class="lb-checkout-field">
                                <label for="lb-checkout-first-name"><?php esc_html_e('First name', 'license-bridge'); ?></label>
                                <input id="lb-checkout-first-name" name="firstName" type="text" required value="<?php echo esc_attr($user->first_name); ?>" />
                            </div>
                            <div class="lb-checkout-field">
                                <label for="lb-checkout-last-name"><?php esc_html_e('Last name', 'license-bridge'); ?></label>
                                <input id="lb-checkout-last-name" name="lastName" type="text" required value="<?php echo esc_attr($user->last_name); ?>" />
                            </div>
                        </div>
                        <div class="lb-checkout-field">
                            <label for="lb-checkout-email"><?php esc_html_e('Email', 'license-bridge'); ?></label>
                            <input id="lb-checkout-email" name="email" type="email" required value="<?php echo esc_attr($user->user_email); ?>" />
                        </div>
                        <div class="lb-checkout-field">
                            <label for="lb-checkout-address"><?php esc_html_e('Address', 'license-bridge'); ?></label>
                            <input id="lb-checkout-address" name="address" type="text" required />
                        </div>
                        <div class="lb-checkout-grid">
                            <div class="lb-checkout-field">
                                <label for="lb-checkout-city"><?php esc_html_e('City', 'license-bridge'); ?></label>
                                <input id="lb-checkout-city" name="city" type="text" required />
                            </div>
                            <div class="lb-checkout-field">
                                <label for="lb-checkout-province"><?php esc_html_e('State / Province', 'license-bridge'); ?></label>
                                <input id="lb-checkout-province" name="province" type="text" />
                            </div>
                        </div>
                        <div class="lb-checkout-grid">
                            <div class="lb-checkout-field">
                                <label for="lb-checkout-zip"><?php esc_html_e('Postal code', 'license-bridge'); ?></label>
                                <input id="lb-checkout-zip" name="zip" type="text" required />
                            </div>
                            <div class="lb-checkout-field">
                                <label for="lb-checkout-country"><?php esc_html_e('Country (2-letter code)', 'license-bridge'); ?></label>
                                <input id="lb-checkout-country" name="country" type="text" maxlength="2" required placeholder="US" />
                            </div>
                        </div>
                        <div id="lb-checkout-stripe-panel" class="lb-checkout-stripe" hidden>
                            <h3><?php esc_html_e('Card details', 'license-bridge'); ?></h3>
                            <div id="lb-checkout-stripe-card"></div>
                            <div id="lb-checkout-stripe-errors" class="lb-checkout-error" role="alert"></div>
                        </div>
                        <div id="lb-checkout-feedback" class="lb-checkout-error" role="alert" hidden></div>
                    </form>
                </div>
                <div class="lb-checkout-modal__footer">
                    <button type="button" class="button" data-lb-checkout-close><?php esc_html_e('Cancel', 'license-bridge'); ?></button>
                    <button type="button" class="button button-primary" id="lb-checkout-submit"><?php esc_html_e('Continue to payment', 'license-bridge'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }
}
