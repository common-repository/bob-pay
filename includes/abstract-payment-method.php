<?php
abstract class AbstractPaymentMethod extends WC_Payment_Gateway
{
    public $version;
    public $url;

    protected $data_to_send;
    protected $send_debug_email;
    protected $debug_email;
    protected $available_currencies;
    protected $available_countries;
    protected $account_code;
    protected $response_url;
    protected $passphrase;
    protected $logger;
    protected $validate_url;
    protected $dev;

    abstract public function receipt_page($order);

    public function __construct()
    {
        $this->version = WC_BOBPAY_PLUGIN_VERSION;
        $this->icon = WP_PLUGIN_URL . '/' . plugin_basename(dirname(dirname(__FILE__))) . '/assets/images/icon-small.png';
        $this->has_fields = false;
        $this->url = 'https://my.bobpay.co.za';
        $this->validate_url = 'https://api.bobpay.co.za/payments/intents/validate';
        $this->send_debug_email = false;
        $this->available_countries = array('ZA');

        $this->init_form_fields();
        $this->init_settings();
        $this->setup_constants();

        $this->debug_email = $this->get_option('debug_email');
        $this->account_code = $this->get_option('account_code');
        $this->passphrase = $this->get_option('passphrase');
        $this->enabled = 'yes' === $this->get_option('enabled') ? 'yes' : 'no';
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->dev = $this->get_option('dev');

        // Point to the dev environment if in dev mode
        if ('yes' === $this->dev) {
            $this->url = 'https://dev.bobpay.co.za';
            $this->validate_url = 'https://api.dev.bobpay.co.za/payments/intents/validate';
        }

        if ('yes' === $this->dev && !empty($this->debug_email)) {
            $this->send_debug_email = true;
        }

        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('woocommerce_admin_order_totals_after_total', array($this, 'display_order_amount'));
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'description' => array(
                'title' => __('Description', 'bob-pay'),
                'type' => 'text',
                'description' => __('This is the description of the payment method which the user sees during checkout.', 'bob-pay'),
                'default' => '',
                'desc_tip' => true,
            ),
            'account_code' => array(
                'title' => __('Account code', 'bob-pay'),
                'type' => 'text',
                'description' => sprintf(__('* Required. Your Bob Pay account code. Get it from %1$sBob Pay%2$s.', 'bob-pay'), '<a href="' . $this->url . '/account?tab=accountDetails" target="_blank">', '</a>'),
                'default' => '',
            ),
            'passphrase' => array(
                'title' => __('Passphrase', 'bob-pay'),
                'type' => 'text',
                'description' => sprintf(__('* Required. Your Bob Pay passphrase. Get if from %1$sBob Pay%2$s.', 'bob-pay'), '<a href="' . $this->url . '/settings" target="_blank">', '</a>'),
                'default' => '',
            ),
            'enabled' => array(
                'title' => __('Enable/Disable', 'bob-pay'),
                'label' => __('Enable Bob Pay', 'bob-pay'),
                'type' => 'checkbox',
                'description' => __('This controls whether or not this gateway is enabled within WooCommerce.', 'bob-pay'),
                'default' => 'no',
                'desc_tip' => true,
            ),
            'dev' => array(
                'title' => __('Enable development mode', 'bob-pay'),
                'type' => 'checkbox',
                'description' => __('Place the payment gateway in development mode. Development REST API endpoints will be used. <i>Note: Funds will still be deducted, so use small amounts.</i>', 'bob-pay'),
                'default' => 'yes',
            ),
            'debug_email' => array(
                'title' => __('Who receives debug emails (development mode only)?', 'bob-pay'),
                'type' => 'text',
                'description' => __('The email address to which debug e-mails are sent when in development mode.', 'bob-pay'),
                'default' => get_option('admin_email'),
            ),
        );
    }

    public function admin_notices()
    {
        $errors_to_show = $this->check_requirements();

        if (!count($errors_to_show)) {
            return;
        }

        if ("no" === $this->enabled) {
            return;
        }

        if (!get_transient('wc-bobpay-plugin-admin-notice-transient')) {
            set_transient('wc-bobpay-plugin-admin-notice-transient', 1, 1);
            echo '<div class="notice notice-error is-dismissible"><p>'
                . __('To use Bob Pay as a payment provider, you need to address the following problems:', 'bob-pay') . '</p>'
                . '<ul style="list-style-type: disc; list-style-position: inside; padding-left: 2em;">'
                . array_reduce($errors_to_show, function ($errors_list, $error_item) {
                    return $errors_list . PHP_EOL . ('<li>' . $this->get_error_message($error_item) . '</li>');
                }, '')
                . '</ul></p></div>';
        }
    }

    public function check_requirements()
    {
        $errors = [
            !in_array(get_woocommerce_currency(), $this->available_currencies) ? 'wc-bobpay-plugin-error-invalid-currency' : null,
            'yes' !== $this->get_option('dev') && empty($this->get_option('account_code')) ? 'wc-bobpay-plugin-error-missing-account-code' : null,
            'yes' !== $this->get_option('dev') && empty($this->get_option('passphrase')) ? 'wc-bobpay-plugin-error-missing-passphrase' : null
        ];

        return array_filter($errors);
    }

    public function get_error_message($key)
    {
        switch ($key) {
            case 'wc-bobpay-plugin-error-invalid-currency':
                return __('Your store uses a currency that Bob Pay doesn\'t support yet.', 'bob-pay');
            case 'wc-bobpay-plugin-error-missing-account-code':
                return __('Missing Bob Pay account code.', 'bob-pay');
            case 'wc-bobpay-plugin-error-missing-passphrase':
                return __('Missing Bob Pay passphrase.', 'bob-pay');
            default:
                return '';
        }
    }

    public function display_order_amount($order_id)
    {
        $order = wc_get_order($order_id);
        $amount = get_post_meta(self::get_order_prop($order, 'id'), 'bobpay_amount', TRUE);

        if (!$amount) {
            return;
        }
        ?>

        <tr>
            <td class="label bobpay-amount">
                <?php echo wc_help_tip(__('This represents the amount that was credited to your Bob Pay account.', 'bob-pay')); ?>
                <?php esc_html_e('Amount:', 'bob-pay'); ?>
            </td>
            <td width="1%"></td>
            <td class="total">
                <?php echo wc_price($amount, array('decimals' => 2)); ?>
            </td>
        </tr>

        <?php
    }

    public static function get_order_prop($order, $prop)
    {
        switch ($prop) {
            case 'order_total':
                $getter = array($order, 'get_total');
                break;
            default:
                $getter = array($order, 'get_' . $prop);
                break;
        }

        return is_callable($getter) ? call_user_func($getter) : $order->{$prop};
    }

    public function admin_options()
    {
        if (in_array(get_woocommerce_currency(), $this->available_currencies)) {
            parent::admin_options();
        } else {
            ?>
            <h3>
                <?php _e('Bob Pay', 'bob-pay'); ?>
            </h3>
            <div class="inline error">
                <p><strong>
                        <?php _e('Gateway Disabled', 'bob-pay'); ?>
                    </strong>
                    <?php /* translators: 1: a href link 2: closing href */ echo sprintf(__('Choose South African Rands as your store currency in %1$sGeneral Settings%2$s to enable the Bob Pay Gateway.', 'bob-pay'), '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=general')) . '">', '</a>'); ?>
                </p>
            </div>
            <?php
        }
    }

    public function is_available()
    {
        if ('yes' === $this->enabled) {
            $errors = $this->check_requirements();

            return 0 === count($errors);
        }

        return parent::is_available();
    }

    public function needs_setup()
    {
        return !$this->get_option('account_code') && !$this->get_option('passphrase');
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        );
    }

    public function process_payment_response()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        try {
            $this->log(PHP_EOL .
                '====================' .
                PHP_EOL .
                'Bob Pay webhook received' .
                PHP_EOL .
                '====================');
            $this->log('Webhook data: ' . json_encode($data));

            $error = null;
            $done = false;

            
            $customPaymentID = $data['custom_payment_id'];

            // Remove the appended site_url from custom payment ID
            $site_url = parse_url(site_url(), PHP_URL_HOST);
            $string_to_remove = $site_url . "_";
 
            $orderID = $customPaymentID;
            // Check if the string starts with the site_url 
            if (strpos($customPaymentID, $string_to_remove) === 0) {
             // If yes, remove the prefix
                 $orderID = substr($customPaymentID, strlen($string_to_remove));
                } 

            // Remove non numeric characters
            $order_id = preg_replace("/[^0-9]/", "", $data['custom_payment_id']);
            
            $order = wc_get_order($orderID);

            if (false === $data) {
                $error = 'Invalid data for webhook';
            }

            if (empty($error) && empty($order)) {
                $error = 'Order not found with order ID: ' . $order_id;
            }

            if (empty($error) && $this->get_option('dev') != 'yes') {
                $this->log('Verify source IP');

                if (!$this->is_valid_ip(sanitize_text_field($_SERVER['REMOTE_ADDR']))) {
                    $error = 'Webhook source forbidden';
                }
            }

            if (empty($error)) {
                $this->log('Verifying payment data against Bob Pay');
                $has_valid_response_data = $this->validate_payment_data($data);

                if (!$has_valid_response_data) {
                    $error = 'Payment data validation failed';
                }
            }

            if (empty($error)) {
                $this->log('Check data against internal order');

                if (!$this->amounts_equal($data['amount'], self::get_order_prop($order, 'order_total'))) {
                    $error = 'Order gross amount mismatch';
                }
            }

            if (!$error && !$done) {
                $this->log_order_details($order);

                if ('completed' === self::get_order_prop($order, 'status')) {
                    $this->log('Order has already been processed');
                    $done = true;
                }
            }

            if (!empty($error)) {
                $this->log('Error occurred: ' . $error);

                if ($this->send_debug_email) {
                    $this->log('Sending email notification');

                    $forwardHeaderString = '';
                    if (!empty(sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']))) {
                        $forwardHeaderString .= "\n" .
                            'Forwarding header: ' .
                            sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
                    }

                    $subject = 'An error occurred while handling a payment: ' . $error;
                    $body = "Hi,\n" .
                        "Something went south while processing an incoming payment response:\n" .
                        "============================================================\n" .
                        'Site: ' .
                        esc_html(get_bloginfo('name', 'display')) .
                        ' (' .
                        esc_url(home_url('/')) .
                        ")\n" .
                        'Remote IP Address: ' .
                        sanitize_text_field($_SERVER['REMOTE_ADDR']) .
                        "\n" .
                        'Remote host name: ' .
                        sanitize_text_field(gethostbyaddr($_SERVER['REMOTE_ADDR'])) .
                        $forwardHeaderString .
                        "\n" .
                        'Bob Pay Transaction ID: ' .
                        esc_html($data['custom_payment_id']) .
                        "\n" .
                        'Error: ' .
                        $error .
                        "\n" .
                        "------------------------------------------------------------\n" .
                        'Payment payload:' .
                        "\n" .
                        json_encode($data);
                    wp_mail($this->debug_email, $subject, $body);
                }
            } elseif (!$done) {
                $this->log('Check status and update order');

                $status = strtolower($data['status']);
                if ('paid' === $status) {
                    $this->handle_payment_complete($data, $order);
                } elseif ('unpaid' === $status) {
                    $this->handle_payment_pending($data, $order);
                }
            }

            $this->log(PHP_EOL .
                '====================' .
                PHP_EOL .
                'Webhook processing complete' .
                PHP_EOL .
                '====================');
        } catch (\Throwable $e) {
            $this->log('An error occurred: ' . $e->getMessage());
        }

        header('HTTP/1.0 200 OK');
        flush();
    }

    public function is_valid_ip($source_ip)
    {
        $valid_ips = array(
            '13.245.84.126', // Bob Pay dev
            '13.246.100.25', // Bob Pay prod
        );

        if (!empty(sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']))) {
            $source_ip = (string) rest_is_ip_address(trim(current(preg_split('/[,:]/', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])))))) ?: $source_ip;
        }

        $this->log("Valid IPs:\n" . print_r($valid_ips, true));
        $is_valid_ip = in_array($source_ip, $valid_ips);

        return apply_filters('woocommerce_bobpay_plugin_is_valid_ip', $is_valid_ip, $source_ip);
    }

    public function validate_payment_data($data)
    {
        $this->log('Host = ' . $this->validate_url);
        $this->log('Params = ' . json_encode($data));

        if (empty($data) || !is_array($data)) {
            return false;
        }

        $response = wp_remote_post(
            $this->validate_url,
            array(
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 60,
                'body' => wp_json_encode($data),
                'user-agent' => BOBPAY_USER_AGENT,
            )
        );

        if (is_wp_error($response)) {
            $this->log("Validation request error: " . PHP_EOL . print_r($response, true));
            return false;
        }

        if (!empty($response['response']['code']) && $response['response']['code'] == 200) {
            return true;
        } else {
            return false;
        }
    }

    public function amounts_equal($amount1, $amount2)
    {
        // Deviations greater than 1c in difference aren't seen as equal
        return !(abs(floatval($amount1) - floatval($amount2)) > 0.01);
    }

    public function log_order_details($order)
    {
        if (version_compare(WC_VERSION, '3.0.0', '<')) {
            $customer_id = get_post_meta($order->get_id(), '_customer_user', true);
        } else {
            $customer_id = $order->get_user_id();
        }

        $details = "Order Details:"
            . PHP_EOL . 'customer id:' . $customer_id
            . PHP_EOL . 'order id:   ' . $order->get_id()
            . PHP_EOL . 'parent id:  ' . $order->get_parent_id()
            . PHP_EOL . 'status:     ' . $order->get_status()
            . PHP_EOL . 'total:      ' . $order->get_total()
            . PHP_EOL . 'currency:   ' . $order->get_currency()
            . PHP_EOL . 'key:        ' . $order->get_order_key();

        $this->log($details);
    }

    public function handle_payment_complete($data, $order)
    {
        $this->log('- Complete');
        $order->add_order_note(__('Payment completed', 'bob-pay'));
        $order->update_meta_data('bobpay_amount', $data['amount']);
        $order->payment_complete($data['custom_payment_id']);

        if ($this->send_debug_email) {
            $subject = 'Bob Pay payment on your site';
            $body =
                "Hi,\n" .
                "A Bob Pay transaction has been completed on your website:\n" .
                "============================================================\n" .
                'Site: ' . esc_html(get_bloginfo('name', 'display')) . ' (' . esc_url(home_url('/')) . ")\n" .
                'Remote IP Address: ' . sanitize_text_field($_SERVER['REMOTE_ADDR']) . "\n" .
                'Remote host name: ' . sanitize_text_field(gethostbyaddr($_SERVER['REMOTE_ADDR'])) . "\n" .
                'Bob Pay Transaction ID: ' . esc_html($data['custom_payment_id']) . "\n" .
                'Order Status Code: ' . self::get_order_prop($order, 'status');
            wp_mail($this->debug_email, $subject, $body);
            die;
        }

        do_action('woocommerce_bobpay_handle_payment_complete', $data, $order);
    }

    public function handle_payment_pending($data, $order)
    {
        $this->log('- Pending');
        $order->update_status('on-hold', __('Payment processing on Bob Pay.', 'bob-pay'));
    }

    public function redirect_to_bobpay($order_id, string $payment_method = '')
    {
        $order = wc_get_order($order_id);

        // Append the site url with the order number to maintain distinct custom_payment_id
        $site_url = parse_url(site_url(), PHP_URL_HOST);

        $customPaymentID = $site_url . '_' . $order_id;

        $this->data_to_send = [
            'recipient_account_code' => $this->account_code,
            'custom_payment_id' => $customPaymentID,
            'email' => $order->get_billing_email(),
            'mobile_number' => $order->get_billing_phone(),
            'amount' => $order->get_total(),
            'item_name' => $order->get_order_number(),
            'item_description' => "",
            'notify_url' => $this->response_url,
            'success_url' => esc_url_raw(add_query_arg('utm_nooverride', '1', $this->get_return_url($order))),
            'pending_url' => esc_url_raw(add_query_arg('utm_nooverride', '1', $this->get_return_url($order))),
            'cancel_url' => $order->get_cancel_order_url()
        ];

        //has to be added separately from other data to send since its value is a hash of the other data
        $this->data_to_send['signature'] = $this->generate_signature($this->data_to_send);

        if (!empty($payment_method)) {
            $this->data_to_send['payment_method'] = $payment_method;
        }

        $hidden_inputs = '';
        foreach ($this->data_to_send as $key => $value) {
            $hidden_inputs .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
        }

        $cancel_url = $this->data_to_send['cancel_url'];

        $logo_src = WC_BOBPAY_PLUGIN_URL . '/assets/images/bob-pay-logo-vertical.png';

        echo <<<HTML
            <form method="get" action="{$this->url}/pay">
            <!-- buttons are necessary incase the client blocks js -->
            <input id="bobpay_perform_redirect" type="submit" value="Pay via Bob Pay" />
            <a href="$cancel_url">Cancel order & restore cart</a>
            $hidden_inputs
            <script type="text/javascript">
                document.addEventListener("DOMContentLoaded", function () {
                    var overlay = document.createElement("div");
                    overlay.style.position = "fixed";
                    overlay.style.top = "0";
                    overlay.style.left = "0";
                    overlay.style.width = "100%";
                    overlay.style.height = "100%";
                    overlay.style.background = "rgb(255, 255, 255)";
                    overlay.style.display = "flex";
                    overlay.style.flexDirection = "column"
                    overlay.style.alignItems = "center";
                    overlay.style.justifyContent = "center";
                    overlay.style.zIndex = "9999";
                    overlay.style.cursor = "wait";
                    var logo = document.createElement("img");
                    logo.src = "$logo_src";
                    logo.style.height = "15%";
                    var message = document.createElement("div");
                    message.textContent = "You are being redirected to Bob Pay to complete your payment...";
                    message.style.textAlign = "center";
                    message.style.color = "#000";
                    message.style.backgroundColor = "#fff";                    
                    overlay.appendChild(logo);
                    overlay.appendChild(message);
                    document.body.appendChild(overlay);
                    var bobPayButton = document.getElementById("bobpay_perform_redirect");
                    if (bobPayButton) {
                        bobPayButton.click();
                    }
                });
            </script>
            </form>
        HTML;
    }

    protected function generate_signature($data)
    {
        $signString = '';
        foreach ($this->data_to_send as $key => $value) {
            $signString .= $key . '=' . urlencode(htmlspecialchars_decode($value)) . '&';
        }
        return md5($signString . 'passphrase=' . $this->passphrase);
    }

    public function log($message)
    {
        if ('yes' === $this->get_option('dev')) {
            if (empty($this->logger)) {
                $this->logger = new WC_Logger();
            }
            $this->logger->add('bobpay', $message);
        }
    }

    public function setup_constants()
    {
        if (defined('BOBPAY_USER_AGENT')) {
            return;
        }

        define('BOBPAY_SOFTWARE_NAME', 'WooCommerce');
        define('BOBPAY_SOFTWARE_VER', WC_VERSION);
        define('BOBPAY_MODULE_NAME', 'BobPay-Woocommerce-Plugin');
        define('BOBPAY_MODULE_VER', $this->version);

        $features = 'PHP ' . phpversion() . ';';
        if (in_array('curl', get_loaded_extensions())) {
            define('BOBPAY_CURL', '');
            $version = curl_version();
            $features .= ' curl ' . $version['version'] . ';';
        } else {
            $features .= ' nocurl;';
        }
        define('BOBPAY_USER_AGENT', BOBPAY_SOFTWARE_NAME . '/' . BOBPAY_SOFTWARE_VER . ' (' . trim($features) . ') ' . BOBPAY_MODULE_NAME . '/' . BOBPAY_MODULE_VER);

        do_action('woocommerce_bobpay_plugin_setup_constants');
    }
}