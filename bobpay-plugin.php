<?php

/**
 * Plugin Name:       Bob Pay
 * Plugin URI:        https://wordpress.org/plugins/bobpay-plugin/
 * Description:       Bob Pay is a payment processing framework developed by Bob Group
 * Author:            Bob Group
 * Requires at least: 5.0
 * Requires PHP:      7.0
 * Version:           2.0.8
 * License:           GPLv2 or later
 * Tested up to:      6.5.2
 */

// Keep public users out of .php files, no funny business allowed
defined('ABSPATH') || exit;

define('WC_BOBPAY_PLUGIN_VERSION', '2.0.8');
define('WC_BOBPAY_PLUGIN_URL', untrailingslashit(plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__))));
define('WC_BOBPAY_PLUGIN_PATH', untrailingslashit(plugin_dir_path(__FILE__)));

function woocommerce_bobpay_init()
{
	if (!class_exists('WC_Payment_Gateway')) {
		return;
	}

	require_once(plugin_basename('includes/class-wc-bobpay-plugin-credit-card.php'));
	require_once(plugin_basename('includes/class-wc-bobpay-plugin-instant-eft.php'));
	require_once(plugin_basename('includes/class-wc-bobpay-plugin-manual-eft.php'));
	require_once(plugin_basename('includes/class-wc-bobpay-plugin-capitec-pay.php'));
	require_once(plugin_basename('includes/class-wc-bobpay-plugin-scan-to-pay.php'));
	require_once(plugin_basename('includes/class-wc-bobpay-plugin-pay-shap.php'));
	require_once(plugin_basename('includes/class-wc-bobpay-plugin-privacy.php'));
	load_plugin_textdomain('bob-pay', false, trailingslashit(dirname(plugin_basename(__FILE__))));
	add_filter('woocommerce_payment_gateways', 'woocommerce_bobpay_add_plugin');
}
add_action('plugins_loaded', 'woocommerce_bobpay_init', 0);

function woocommerce_bobpay_add_plugin($methods)
{
	$methods[] = 'WC_BobPay_Plugin_Credit_Card';
	$methods[] = 'WC_BobPay_Plugin_Instant_EFT';
	$methods[] = 'WC_BobPay_Plugin_Manual_EFT';
	$methods[] = 'WC_BobPay_Plugin_Capitec_Pay';
	$methods[] = 'WC_BobPay_Plugin_Scan_To_Pay';
	$methods[] = 'WC_BobPay_Plugin_Pay_Shap';
	return $methods;
}

function woocommerce_bobpay_plugin_links($links)
{
	$settings_url = add_query_arg(
		array(
			'page' => 'wc-settings',
			'tab' => 'checkout',
		),
		admin_url('admin.php')
	);

	$plugin_links = array(
		'<a href="' . esc_url($settings_url) . '">' . __('Settings', 'bob-pay') . '</a>',
		'<a href="https://www.woocommerce.com/my-account/tickets/">' . __('Support', 'bob-pay') . '</a>',
	);

	return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'woocommerce_bobpay_plugin_links');


function woocommerce_bobpay_woocommerce_blocks_support()
{
	if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
		require_once dirname(__FILE__) . '/includes/blocks-support/class-wc-bobpay-credit-card-blocks-support.php';
		require_once dirname(__FILE__) . '/includes/blocks-support/class-wc-bobpay-scan-to-pay-blocks-support.php';
		require_once dirname(__FILE__) . '/includes/blocks-support/class-wc-bobpay-pay-shap-blocks-support.php';
		require_once dirname(__FILE__) . '/includes/blocks-support/class-wc-bobpay-capitec-pay-blocks-support.php';
		require_once dirname(__FILE__) . '/includes/blocks-support/class-wc-bobpay-instant-eft-blocks-support.php';
		require_once dirname(__FILE__) . '/includes/blocks-support/class-wc-bobpay-manual-eft-blocks-support.php';
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
				$payment_method_registry->register(new WC_Bobpay_Credit_Card_Blocks_Support);
				$payment_method_registry->register(new WC_Bobpay_Scan_To_Pay_Blocks_Support);
				$payment_method_registry->register(new WC_Bobpay_Capitec_Pay_Blocks_Support);
				$payment_method_registry->register(new WC_Bobpay_Instant_EFT_Blocks_Support);
				$payment_method_registry->register(new WC_Bobpay_Manual_EFT_Blocks_Support);
				$payment_method_registry->register(new WC_Bobpay_Pay_Shap_Blocks_Support);
			}
		);
	}
}
add_action('woocommerce_blocks_loaded', 'woocommerce_bobpay_woocommerce_blocks_support');

add_filter('init', 'ReorderPaymentGateways', 99);
function ReorderPaymentGateways()
{

	$gateway_order = get_option('woocommerce_gateway_order');

	if (!is_array($gateway_order)) {
		return;
	}

	if (isset($gateway_order['bobpay_credit_card'], $gateway_order['bobpay_instant_eft'], $gateway_order['bobpay_manual_eft'], $gateway_order['bobpay_capitec_pay'], $gateway_order['bobpay_scan_to_pay'], $gateway_order['bobpay_pay_shap'])) {
		unset($gateway_order['bobpay_credit_card'], $gateway_order['bobpay_instant_eft'], $gateway_order['bobpay_manual_eft'], $gateway_order['bobpay_capitec_pay'], $gateway_order['bobpay_scan_to_pay'], $gateway_order['bobpay_pay_shap']);
	}

	$gateway_order = array_merge(['bobpay_credit_card' => 0, 'bobpay_instant_eft' => 1, 'bobpay_manual_eft' => 2, 'bobpay_capitec_pay' => 3, 'bobpay_scan_to_pay' => 4, 'bobpay_pay_shap' => 5], $gateway_order);

	$i = 0;
	foreach ($gateway_order as $gateway => $order) {
		$gateway_order[$gateway] = $i;
		$i++;
	}

	update_option('woocommerce_gateway_order', $gateway_order);

	if (!function_exists("WC") || empty(WC()->session)) {
		return;
	}

	// Set bobpay as default payment method
	WC()->session->set('chosen_payment_method', 'bobpay_credit_card');
}

// Check if each payment method has settings in wp_options
// If not, check if woocommerce_bobpay_settings has been set and use that to populate the settings
function populate_empty_settings()
{
	$payment_methods = array(
		'bobpay_credit_card',
		'bobpay_instant_eft',
		'bobpay_manual_eft',
		'bobpay_capitec_pay',
		'bobpay_scan_to_pay',
		'bobpay_pay_shap',
	);

	foreach ($payment_methods as $payment_method) {
		$settings = get_option('woocommerce_' . $payment_method . '_settings');
		if (!empty($settings)) {
			continue;
		}

		$settings = get_option('woocommerce_bobpay_settings');
		if (!empty($settings)) {
			update_option('woocommerce_' . $payment_method . '_settings', $settings);
		}
	}
}
add_action('init', 'populate_empty_settings');