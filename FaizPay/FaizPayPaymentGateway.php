<?php

namespace FaizPay;

use WC_Payment_Gateway;

final class FaizPayPaymentGateway extends WC_Payment_Gateway
{
    private $terminal_id;
    private $terminal_secret;

    public function __construct()
    {
        $this->id = 'faizpay_payment';
        $this->method_title = 'FaizPay';

        $this->method_description = "Fast instant bank to bank payments";  // to backend
        $this->order_button_text = 'Proceed to FaizPay';

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        $this->has_fields = false;

        // only support products
        $this->supports = array(
            'products'
        );

        $this->countries = ['GB'];

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled = $this->get_option('enabled');
        $this->terminal_id = $this->get_option('terminal_id');
        $this->terminal_secret = $this->get_option('terminal_secret');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_faizpay', array($this, 'webhook'));

        // https://rudrastyh.com/woocommerce/payment-gateway-plugin.html#gateway_options
        // https://rudrastyh.com/woocommerce/thank-you-page.html
        //add_filter('woocommerce_endpoint_order-received_title', [$this, 'thank_you_title']);
        //add_filter('woocommerce_thankyou_order_received_text', [$this, 'thank_you_text']);
    }

    public function init_form_fields()
    {
        $this->form_fields = AdminPortalOptions::get();
    }

    function admin_options()
    {
        AdminPortalUI::get($this->generate_settings_html([], false));
    }

    public function process_admin_options()
    {
        parent::process_admin_options();
        return AdminPortalOptions::validate($this->terminal_secret, $this->terminal_id);
    }

    public function process_payment($order_id)
    {
        return PaymentProcess::process($order_id, $this->terminal_id, $this->terminal_secret);
    }

    public function get_icon()
    {
        $icon_html = '';
        $providers = array('barclays', 'hsbc', 'lloyds', 'starling', 'natwest', 'santander');
        foreach ($providers as $provider) {
            $url = \WC_HTTPS::force_https_url(plugin_dir_url(dirname(__FILE__, 1)) . 'assets/' . $provider . '.svg');
            $icon_html .= '<img width="26" src="' . esc_attr($url) . '" alt="' . esc_attr($provider) . '" />';
        }
        return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }

    public function webhook()
    {
        PaymentNotification::process($this->terminal_id, $this->terminal_secret);
    }
}