<?php
if (!class_exists('WC_Abstract_Privacy')) {
	return;
}

class WC_BobPay_Plugin_Privacy extends WC_Abstract_Privacy {

    public function __construct() {
		parent::__construct(__('Bob Pay', 'bob-pay'));

		$this->add_exporter('bobpay-woocommerce-plugin-order-data', __('WooCommerce Bob Pay Order Data', 'bob-pay'), array($this, 'order_data_exporter'));
	}

	protected function get_bobpay_orders($email_address, $page) {
		$user = get_user_by('email', $email_address);

		$order_query = array(
			'payment_method' => 'bobpay',
			'limit' => 10,
			'page' => $page,
		);

		if ($user instanceof WP_User) {
			$order_query['customer_id'] = (int) $user->ID;
		} else {
			$order_query['billing_email'] = $email_address;
		}

		return wc_get_orders($order_query);
	}

	public function get_privacy_message() {
		return wpautop(sprintf(__('By using this extension, you may be storing personal data or sharing data with an external service. <a href="%s" target="_blank">Learn more about how this works, including what you may want to include in your privacy policy.</a>', 'bob-pay'), 'https://docs.woocommerce.com/document/privacy-payments/#bob-pay'));
	}

	public function order_data_exporter($email_address, $page = 1) {
		$done = false;
		$data_to_export = array();

		$orders = $this->get_bobpay_orders($email_address, (int) $page);

		$done = true;

		if (0 < count($orders)) {
			foreach ($orders as $order) {
				$data_to_export[] = array(
					'group_id' => 'woocommerce_orders',
					'group_label' => __('Orders', 'bob-pay'),
					'item_id' => 'order-' . $order->get_id(),
				);
			}

			$done = 10 > count($orders);
		}

		return array(
			'data' => $data_to_export,
			'done' => $done,
		);
	}
}

new WC_BobPay_Plugin_Privacy();
