<?php
/**
 * Payment Gateway class as required by WooCommerce
 */

namespace Decred\Payments\WooCommerce;

defined( 'ABSPATH' ) || exit;  // prevent direct URL execution.

include 'class-constant.php';

/**
 * Decred Payments
 *
 * @class       Decred\Payments\WooCommerce\Gateway
 * @extends     WC_Payment_Gateway
 * @version     0.1
 * @author      xifrat
 */
class Gateway extends \WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = strtolower(Constant::CURRENCY_NAME);
		$this->icon               = plugins_url( Constant::ICON_PATH, dirname(__FILE__) );
		$this->has_fields         = false;
		$this->method_title       = Constant::CURRENCY_NAME;
		$this->method_description = __( 'Allows direct payments with the Decred cryptocurrency.', Constant::TEXT_DOMAIN );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions' );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_decred', array( $this, 'thankyou_page' ) );

		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled'      => array(
				'title'   => __( 'Enable/Disable', Constant::TEXT_DOMAIN ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Decred payments ', Constant::TEXT_DOMAIN ),
				'default' => 'no',
			),
			'title'        => array(
				'title'       => __( 'Title', Constant::TEXT_DOMAIN ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', Constant::TEXT_DOMAIN ),
				'default'     => _x( 'Decred payments', 'Pay with Decred', Constant::TEXT_DOMAIN ),
				'desc_tip'    => true,
			),
			'description'  => array(
				'title'       => __( 'Description', Constant::TEXT_DOMAIN ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', Constant::TEXT_DOMAIN ),
				'default'     => __( 'Please send some specific Decred amount to the address we provide here.', Constant::TEXT_DOMAIN ),
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', Constant::TEXT_DOMAIN ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', Constant::TEXT_DOMAIN ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Output for the order received page.
	 */
	public function thankyou_page() {
		if ( $this->instructions ) {
			echo wpautop( wptexturize( $this->instructions ) );
		}
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @access public
	 * @param WC_Order $order .
	 * @param bool     $sent_to_admin .
	 * @param bool     $plain_text .
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && 'decred' === $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
			echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
		}
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id .
	 * @return array
	 */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( $order->get_total() > 0 ) {
			// Mark as on-hold (we're awaiting the cheque).
			$order->update_status( 'on-hold', _x( 'Awaiting Decred payment', 'Pay with Decred', Constant::TEXT_DOMAIN ) );
		} else {
			$order->payment_complete();
		}

		// Reduce stock levels.
		wc_reduce_stock_levels( $order_id );

		// Remove cart.
		WC()->cart->empty_cart();

		// Return thankyou redirect.
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}
}
