<?php
require_once('abstract-payment-method.php');

class WC_BobPay_Plugin_Credit_Card extends AbstractPaymentMethod
{
    public function __construct()
    {
        $this->id = 'bobpay_credit_card';
        $this->method_title = 'Bob Pay Credit Card';
        $this->method_description = sprintf(__('Bob Pay Credit Card redirects customers to %1$sBob Pay%2$s to pay with their credit card.', 'bob-pay'), '<a href="' . $this->url . '">', '</a>');
        $this->response_url = add_query_arg('wc-api', 'WC_BobPay_Plugin_Credit_Card', home_url('/'));
        $this->available_currencies = (array) apply_filters('woocommerce_bobpay_credit_card_plugin_available_currencies', array('ZAR'));

        if (defined('BOBPAY_LOCAL_DEV') && defined('NGROK_TUNNEL_URL') && 'true' === BOBPAY_LOCAL_DEV) {
            $this->response_url = add_query_arg('wc-api', 'WC_BobPay_Plugin_Credit_Card', NGROK_TUNNEL_URL . '/');
        }

        parent::__construct();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_bobpay_credit_card', array($this, 'receipt_page'));
        add_action('woocommerce_api_wc_bobpay_plugin_credit_card', array($this, 'process_payment_response'));
    }

    public function init_form_fields()
    {
        parent::init_form_fields();
        $this->form_fields = array_merge(
            [
                'title' => array(
                    'title' => __('Title', 'bob-pay'),
                    'type' => 'text',
                    'description' => __('This is the name of the payment method which the user sees during checkout.', 'bob-pay'),
                    'default' => __('Credit Card', 'bob-pay'),
                    'desc_tip' => true,
                )
            ],
            $this->form_fields
        );
    }

    public function receipt_page($order)
    {
        echo '<p>' . __('Thank you for your order, please click the button below to pay with Bob Pay.', 'bob-pay') . '</p>';
        echo $this->redirect_to_bobpay($order, 'credit-card');
    }
}