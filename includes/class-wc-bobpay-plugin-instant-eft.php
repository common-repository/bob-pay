<?php
require_once('abstract-payment-method.php');

class WC_BobPay_Plugin_Instant_EFT extends AbstractPaymentMethod
{
    public function __construct()
    {
        $this->id = 'bobpay_instant_eft';
        $this->method_title = 'Bob Pay Instant EFT';
        $this->method_description = sprintf(__('Bob Pay Instant EFT redirects customers to %1$sBob Pay%2$s to pay with our secure Instant EFT technology.', 'bob-pay'), '<a href="' . $this->url . '">', '</a>');
        $this->response_url = add_query_arg('wc-api', 'WC_BobPay_Plugin_Instant_EFT', home_url('/'));
        $this->available_currencies = (array) apply_filters('woocommerce_bobpay_instant_eft_plugin_available_currencies', array('ZAR'));

        if (defined('BOBPAY_LOCAL_DEV') && defined('NGROK_TUNNEL_URL') && 'true' === BOBPAY_LOCAL_DEV) {
            $this->response_url = add_query_arg('wc-api', 'WC_BobPay_Plugin_Instant_EFT', NGROK_TUNNEL_URL . '/');
        }

        parent::__construct();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_bobpay_instant_eft', array($this, 'receipt_page'));
        add_action('woocommerce_api_wc_bobpay_plugin_instant_eft', array($this, 'process_payment_response'));
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
                    'default' => __('Instant EFT', 'bob-pay'),
                    'desc_tip' => true,
                )
            ],
            $this->form_fields
        );
    }

    public function receipt_page($order)
    {
        echo '<p>' . __('Thank you for your order, please click the button below to pay with Bob Pay.', 'bob-pay') . '</p>';
        echo $this->redirect_to_bobpay($order, 'instant-eft');
    }
}