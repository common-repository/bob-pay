<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Bobpay_Pay_Shap_Blocks_Support extends AbstractPaymentMethodType
{
	protected $name = 'bobpay_pay_shap';

	public function initialize()
	{
		$this->settings = get_option('woocommerce_bobpay_pay_shap_settings', []);
	}

	public function is_active()
	{
		$payment_gateways_class = WC()->payment_gateways();
		$payment_gateways = $payment_gateways_class->payment_gateways();

		return $payment_gateways['bobpay_pay_shap']->is_available();
	}

	public function get_payment_method_data()
	{
		return [
			'title' => $this->get_setting('title'),
			'description' => $this->get_setting('description'),
			'supports' => $this->get_supported_features(),
			'pay_shap_url' => WC_BOBPAY_PLUGIN_URL . '/assets/images/pay-shap.png',
		];
	}

	public function get_payment_method_script_handles()
	{
		$asset_path = WC_BOBPAY_PLUGIN_PATH . '/build/js/front-end/index.asset.php';
		$version = WC_BOBPAY_PLUGIN_VERSION;
		$dependencies = [];
		if (file_exists($asset_path)) {
			$asset = require $asset_path;
			$version = is_array($asset) && isset($asset['version']) ? $asset['version'] : $version;
			$dependencies = is_array($asset) && isset($asset['dependencies']) ? $asset['dependencies'] : $dependencies;
		}
		wp_register_script(
			'wc-bobpay-pay-shap-blocks-integration',
			WC_BOBPAY_PLUGIN_URL . '/build/js/frontend/index.js',
			$dependencies,
			$version,
			true
		);
		wp_set_script_translations(
			'wc-bobpay-pay-shap-blocks-integration',
			'bob-pay'
		);
		return ['wc-bobpay-pay-shap-blocks-integration'];
	}

	public function get_supported_features()
	{
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		return $payment_gateways['bobpay_pay_shap']->supports;
	}
}
