<?php
/**
 * Payment Gateway methods related to the "thankyou" page that
 * shows after user proceeds with payment in the checkout page.
 */

namespace Decred\Payments\WooCommerce;

defined( 'ABSPATH' ) || exit;  // prevent direct URL execution.

/**
 * Decred Payments
 *
 * @class       Decred\Payments\WooCommerce\GW_Thankyou
 * @extends     GW_Checkout
 * @version     0.1
 * @author      xifrat
 */
class GW_Thankyou extends GW_Checkout {

	/**
	 * DCR paymnent address to show in thankyou page.
	 *
	 * @var string dcr_payment_address
	 */
	public $dcr_payment_address;

	/**
	 * @var string
	 */
	public $dcr_code;

	public $dcr_order_status;

	static public function getDecredOrderStatus( $order_id )
	{
		if ( ! ( $confirmations = get_post_meta( $order_id, 'decred_confirmations' ) ) ) {
			return 1; // Pending
		}

		$settings = get_option( 'woocommerce_decred_settings', [] );
		$confirmations_to_wait = (int) $settings['confirmations_to_wait'] ?: 6;

		if ( $confirmations < $confirmations_to_wait) {
            return 2; // Processing
		}

        return 3; // Success
	}

	/**
	 * Add a note to the "order received" text on top of the thankyou page
	 *
	 * @param string $text default text WooCommerce shows.
	 */
	function thankyou_order_received_text( $text ) {
		// use .= here if you want to keep the default (not much useful) message.
		$text = __( 'PLEASE SEE INSTRUCCIONS BELOW FOR PAYMENT WITH DECRED.', 'decred' );
		return $text;
	}

	/**
	 * Output for the order received page.
	 *
	 * @param int $order_id .
	 */
	public function thankyou_page( $order_id ) {

		$this->recover_decred_data( $order_id );

		require __DIR__ . '/html-thankyou.php';
	}

	/**
	 * Recover Decred data saved to DB at checkout time
	 *
	 * @param int $order_id .
	 */
	public function recover_decred_data( $order_id ) {
		$this->dcr_amount          = get_post_meta( $order_id, 'decred_amount', true );
		$this->dcr_payment_address = get_post_meta( $order_id, 'decred_payment_address', true );
		$query = http_build_query(['amount' => $this->dcr_amount]);
		$this->dcr_code = sprintf('decred:%s?%s', $this->dcr_payment_address, $query);
		$this->dcr_order_status = static::getDecredOrderStatus( $order_id );
	}
}
