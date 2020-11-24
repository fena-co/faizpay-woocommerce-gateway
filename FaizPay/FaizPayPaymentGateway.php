<?php

namespace FaizPay;

use \WC_Payment_Gateway;

final class FaizPayPaymentGateway extends WC_Payment_Gateway
{
    private $terminal_id;
    private $terminal_secret;

    public function __construct()
    {
        $this->id = 'faizpay_payment';
        $this->method_title = 'FaizPay';
        $this->title = 'FaizPay';
        $this->method_description = "Fast instant bank to bank payments";  // to backend
        $this->description = "Pay via your bank account";                  // to the front end portal
        $this->has_fields = true;

        // only support products
        $this->supports = array(
            'products'
        );

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled = $this->get_option('enabled');
        $this->terminal_id = $this->get_option('terminal_id');
        $this->terminal_secret = $this->get_option('terminal_secret');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_faizpay', array($this, 'webhook'));

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

    public function payment_fields()
    {
        CheckOutUI::get();
    }

    public function webhook()
    {
        PaymentNotification::process($this->terminal_id, $this->terminal_secret);
    }




}