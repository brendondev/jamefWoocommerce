<?php
/**
 * Plugin Name: Brendon Jamef para Woocommerce
 * Plugin URI: https://brendon.com.br
 * Description: Add Shipping Method Jamef for WooCommerce
 * Version: 1.0.1
 * Author: brendon
 * Author URI: http://brendon.com.br
 * License: GPLv2 or later
 *
 * @package brendon/Shipping.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'brendon_Jamef' ) ) {
	/**
	 * Class definition
	 */
	class brendon_Jamef {
		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;

		/**
		 * Singleton Init Jamef plugin.
		 */
		private function __construct() {
			if ( class_exists( 'Woocommerce' ) ) {
				add_action( 'woocommerce_shipping_init', array( $this, 'jamef_shipping_method' ) );
				add_filter( 'woocommerce_shipping_methods', array( $this, 'add_jamef_shipping_method' ) );
				add_action( 'woocommerce_after_shipping_rate', array( $this, 'shipping_delivery_forecast' ), 100 );
			} else {
				add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			}
		}

		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Load jameft shipping method.
		 */
		public function jamef_shipping_method() {
			require_once 'includes/class-wc-jamef-shipping.php';
		}

		/**
		 * Register jamef shipping method.
		 *
		 * @param array $methods the current methods.
		 * @return array
		 */
		public function add_jamef_shipping_method( $methods ) {
			$methods['brendon_jamef'] = 'WC_Jamef_Shipping';
			return $methods;
		}

		/**
		 * WooCommerce fallback notice.
		 */
		public function woocommerce_missing_notice() {
			?>
			<div class="error">
				<p>
					<strong>A integração com Jamef</strong> depende da última versão do woocommerce para funcionar!
				</p>
			</div>
			<?php
		}

		/**
		 * Adds delivery forecast after method name.
		 *
		 * @param WC_Shipping_Rate $shipping_method Shipping method data.
		 */
		public function shipping_delivery_forecast( $shipping_method ) {
			$meta_data = $shipping_method->get_meta_data();

			if ( isset( $meta_data['_estimate_delivery'] ) ) {
				echo '<p><small>' . esc_html( sprintf( __( 'Previsão de entrega: %s', 'brendon-jamef' ), $meta_data['_estimate_delivery'] ) ) . '</small></p>';
			}
		}
	}

	add_action( 'plugins_loaded', array( 'brendon_Jamef', 'get_instance' ) );
}
