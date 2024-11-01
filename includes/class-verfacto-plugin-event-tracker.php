<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/trait-verfacto-plugin-helper.php';

/**
 * Class responsible for initializing Verfacto tracker and controlls all track events
 */
class Verfacto_Plugin_Event_Tracker {

	use Verfacto_Plugin_Helper;

	/**
	 * The Verfacto tracker id
	 *
	 * @since  1.0.0
	 * @access private
	 * @var $tracker_id Keeps tracker id.
	 */
	private $tracker_id;

	/**
	 * Transient prefix
	 *
	 * @since  1.0.13
	 * @access private
	 * @var $transient_prefix Keeps transient prefix.
	 */
	private $transient_prefix = 'verfacto_';

	/**
	 * Events tracker constructor register necessary hooks, return void if tracker is not enabled.
	 *
	 * @return void
	 */
	public function __construct() {
		if ( ! $this->is_tracker_enabled() ) {
			return;
		}

		$this->add_hooks();
	}

	/**
	 * Register nessesary hooks, to track variuos events
	 *
	 * @return void
	 */
	private function add_hooks() {

		// Inject Verfacto tracker.
		add_action( 'wp_head', array( $this, 'inject_verfacto_tracker' ) );

		// ViewItem events.
		add_action("wp_footer", array( $this, "track_view_item" ) );

		// AddToBasket events.
		add_action( 'woocommerce_add_to_cart', array( $this, 'track_add_to_basket' ), 40, 4 );

		// AddToBasket events then AJAX is enabled
		add_action( 'woocommerce_ajax_added_to_cart', array( $this, 'track_add_to_basket_ajax' ) );
		
		// GoToCheckoutEvent events.
		add_action( 'woocommerce_after_checkout_form', array( $this, 'track_go_to_checkout' ) );

		// IdentifyUserOnLogin events.
		add_action( 'wp_login', array( $this, 'track_user_login' ) );

		// IdentifyUserOnRegistration events.
		add_action( 'user_register', array( $this, 'track_user_register' ) );
		add_filter( 'woocommerce_process_registration_errors', array( $this, 'track_user_register_validation' ) );

		// IdentifyUserOnOrder.
		add_action( 'woocommerce_thankyou', array( $this, 'track_identify' ), 40 );

		add_action( 'wp_footer', array( $this, 'execute_verfacto_tracker' ) );
	}

	/**
	 * Track user log-in event
	 *
	 * @param object $user_login WP user object.
	 * @return void
	 */
	public function track_user_login( $user_login ) {
		set_transient( $this->transient_prefix . $user_login, '1', 0 );
	}

	/**
	 * Track user on created account and redirect to account page
	 *
	 * @param int $user_id WP user ID.
	 * @return void
	 */
	public function track_user_register( $user_id ) {
		$email = sanitize_email( $_POST['email'] );
		$user  = get_userdata( $user_id )->user_login;

		if ( isset( $email ) && ! empty( $email ) && ! empty( $user ) ) {
			set_transient( $this->transient_prefix . $user, '1', 0 );
		}
	}

	/**
	 * Track user on validating account create form
	 *
	 * @param object $validation_error WC create account validation errors.
	 * @return validation_error
	 */
	public function track_user_register_validation( $validation_error ) {
		$email = sanitize_email( $_POST['email'] );
		if ( isset( $email ) && ! empty( $email ) ) {
			echo '<script> 
			window.onload = function() {
				if (document.readyState !== "loading") { 
					window.VerfactoTracker.identify("' . esc_js( $this->tracker_id ) . '", "' . esc_js( $email ) . '");
				}; 
			};
			</script>';
		}
		return $validation_error;
	}

	/**
	 * Fire verfacto tracker on user log-in
	 *
	 * @return void
	 */
	public function execute_verfacto_tracker() {
		global $current_user;

		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! get_transient( $this->transient_prefix . $current_user->user_login ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<script> 
		window.onload = function() {
			if (document.readyState !== "loading") {
				window.VerfactoTracker.identify("' . esc_js( $this->tracker_id ) . '", "' . esc_js( sanitize_email( $current_user->user_email ) ) . '");
			}; 
		};
		</script>';

		delete_transient( $this->transient_prefix . $current_user->user_login );
	}

	/**
	 * Prints Verfacto tracker code
	 *
	 * @return void
	 */
	public function inject_verfacto_tracker() {
		echo wp_kses(
			$this->get_verfacto_tracker( $this->tracker_id ),
			array(
				'script' => array(
					'src'    => array(),
					'onload' => array(),
					'defer'  => array(),
				),
				'div'    => array(
					'class' => array(),
				),
			)
		);
	}

	/**
	 * Gets the Verfacto tracker snippet.
	 *
	 * @param string $tracker_id the tracker id.
	 * @return string HTML scripts
	 */
	public function get_verfacto_tracker( $tracker_id ) {

		if ( empty( $tracker_id ) ) {
			return '';
		}

		$attributes = array(
			'defer' => '',
			'src'   => esc_html( Verfacto_Plugin::TRACKER_URL ) . '?trackerID=' . esc_js( $tracker_id ) . '&platform=WooCommerce',
		);

		ob_start();

		?>
		<!-- WooCommerce Verfacto Tracker Begin -->
		<script <?php echo self::get_script_attributes( $attributes ); ?> ></script>

		<script>
			document.addEventListener( 'DOMContentLoaded', function() {
					jQuery( function( $ ) {
						$( document.body ).append( '<div class=\"verfacto-tracker-event-placeholder\"></div>' );
					} );
				}, false );
		</script>
		<!-- Verfacto Tracker Integration End -->
		<?php

		return ob_get_clean();
	}

	/**
	 * Track view_item event
	 *
	 * @param $product
	 * @return void
	 */
	public function track_view_item() {
		global $product;

		if ( is_product() && !wp_doing_ajax() ) {
			$product_id = $product->get_id();
			$product_data = $this->get_product( $product_id );
			$total_price = $product_data['0']['price'];

			$event_data = $this->get_event_data( $total_price, $product_data );

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->track_event( 'view_item', $event_data );
		}
	}

	/**
	 * Track add_to_basket event
	 *
	 * @param string $cart_item_key keep cart item key.
	 * @param int    $product_id keep product id.
	 * @param int    $quantity keep item quantity.
	 * @param int    $variation_id keep item variation id.
	 * @return void
	 */
	public function track_add_to_basket( $cart_item_key, $product_id, $quantity, $variation_id ) {
		if ( ! wp_doing_ajax() ) {
			$product_data = $this->get_product( $product_id, $quantity );

			$total_price = $product_data['0']['price'];

			$event_data = $this->get_event_data( $total_price, $product_data );

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->track_event( 'add_to_basket', $event_data );
		}
	}

	/**
	 * Track add_to_basket event on ajax
	 *
	 * @return void
	 */
	public function track_add_to_basket_ajax() {
		if ( 'no' === get_option( 'woocommerce_cart_redirect_after_add' ) && wp_doing_ajax() ) {
			add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'add_to_basket_event_fragment' ) );
		}
	}

	/**
	 * Insert fragment to Woocommerce add to basket response
	 *
	 * @param string $fragments fragments of response.
	 * @return array of fragments
	 */
	public function add_to_basket_event_fragment( $fragments ) {
		$product_id  = isset( $_POST['product_id'] ) ? sanitize_text_field( wp_unslash( $_POST['product_id'] ) ) : null;
		$product_qty = isset( $_POST['quantity'] ) ? sanitize_text_field( wp_unslash( $_POST['quantity'] ) ) : null;
		$product_data = $this->get_product( $product_id, $product_qty );
		$total_price = $product_data['0']['price'];

		$event_data = $this->get_event_data( $total_price, $product_data );

		$script = $this->track_event( 'add_to_basket', $event_data, true );

		$fragments['div.verfacto-tracker-event-placeholder'] = '<div class="verfacto-tracker-event-placeholder">' . $script . '</div>';

		return $fragments;
	}

	/**
	 * Track go_to_checkout event
	 *
	 * @return void
	 */
	public function track_go_to_checkout() {
		global $woocommerce;
		$cart = $woocommerce->cart;

		$total_price = $cart->get_cart_contents_total();
		$products = $cart->get_cart();
		$product_data = array();

		foreach ($products as $key => $product) {
			$product_id = $product['product_id'];
			$product_quantity = $product['quantity'];

			$product_data[] = $this->get_product( $product_id, $product_quantity)[0];
		}

		$coupon = '';

		if ( $cart->applied_coupons ) {
			$coupon = $cart->get_applied_coupons()[0];
		}

		$event_data = $this->get_event_data( $total_price, $product_data, $coupon );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->track_event( 'go_to_checkout', $event_data);
	}

	/**
	 * Track identify and place order event on successful order
	 *
	 * @return void
	 */
	public function track_identify() {
		$order_id = absint( get_query_var( 'order-received' ) );

		if ( get_post_type( $order_id ) === 'shop_order' ) {
			$order = wc_get_order( $order_id );
			
			$coupon = $order->get_used_coupons()[0];
			$tax = $order->get_total_tax() ?: 0;
			$shipping = $order->get_total_shipping() ?: 0;
			$total_price = $order->get_total() - $tax - $shipping;

			$order_data = $order->get_data();

			$transaction_id = $order_data['id'];
			$tax = $order_data['total_tax'] ?: 0;
			$shipping = $order_data['shipping_total'] ?: 0;

			$products_data = array();
			$order_items = $order->get_items();

			foreach ( $order_items as $item_id => $item ) {
				$product_id = $item->get_product_id();
				$product_quantity = $item->get_quantity();

				$product_data[] = $this->get_product( $product_id, $product_quantity)[0];
			}

			$event_data = $this->get_event_data(
				$total_price,
				$product_data,
				$coupon,
				$transaction_id,
				$tax,
				$shipping
			);

			$customer_email = strtolower( $order->get_billing_email() );

			if ( ! empty( $customer_email ) ) {
				echo '<script> 
					document.addEventListener("DOMContentLoaded", function() {
						window.VerfactoTracker.identify("' . esc_js( $this->tracker_id ) . '", "' . esc_js( sanitize_email( $customer_email ) ) . '");
							' . $this->get_place_order_script( $this->tracker_id, $event_data ) . '
					});
				</script>';
			} else {
				echo '<script> 
					document.addEventListener("DOMContentLoaded", function() {
						' . $this->get_place_order_script( $this->tracker_id, $event_data ) . '
					});
				</script>';
			}
		}
	}

	/**
	 * Get place_order script
	 *
	 * @param int     $tracker_id tracker id.
	 * @param object  $event_data event data.
	 * @return string of place_order script
	 */
	private function get_place_order_script( $tracker_id, $event_data ) {
		$script = sprintf(
			'window.VerfactoTracker.track("%s", "%s", %s);',
			esc_js( $this->tracker_id ),
			esc_js( 'place_order' ),
			wp_json_encode($event_data)
		);

		return $script;
	}

	/**
	 * General events tracking method
	 *
	 * @param string $event_name event to track.
	 * @param array  $data event data.
	 * @param bool   $track_ajax is event ajax or not.
	 *
	 * @return string of HTML
	 */
	public function track_event( $event_name, $data = null, $track_ajax = false ) {
		$script = '<script> %s
			window.VerfactoTracker.track("%s", "%s", %s);
        %s</script>';

		if ( ! empty( $data ) ) {
			if ( is_array( $data ) ) {
				$data = wp_json_encode( $data );
			} else {
				$data = "'" . esc_js( wp_unslash( $data ) ) . "'";
			}
		} else {
			$data = '{}';
		}	

		return sprintf(
			$script,
			( ! $track_ajax ) ? 'document.addEventListener("DOMContentLoaded", function() { ' : '',
			esc_js( $this->tracker_id ),
			esc_js( $event_name ),
			$data,
			( ! $track_ajax ) ? '});' : ''
		);

	}
	
	/**
	 * Get event data
	 *
	 * @param string       $currency currency.
	 * @param int          total_price total price of all products.
	 * @param object       $products All products.
	 * @return void|object of product data
	 */
	private function get_event_data(
		$total_price,
		$products,
		$coupon = null,
		$transaction_id = null,
		$tax = null,
		$shipping = null
	) {	
		$event_data_items = array();

		$item_index = 0;

		foreach ( $products as $product ) {
			$event_data_items[$item_index] = array(
				'item_id' => strval($product['id']),
				'item_name' => $product['name'],
				'item_brand' => $product['brand']
			);

			$category_index = 0;

			foreach ( $product['categories'] as $product_category ) {
				if ( $category_index === 0 ) {
					$event_data_items[$item_index]['category'] = $product_category;
				} else {
					$event_data_items[$item_index]['category' . $category_index] = $product_category;
				}

				$category_index++;
			}

			$event_data_items[$item_index]['item_variant'] = $product['variant'];
			$event_data_items[$item_index]['price'] = $product['price'];
			$event_data_items[$item_index]['quantity'] = strval( $product['quantity'] );

			$item_index++;
		}

		$data = array();
		$currency = get_woocommerce_currency();

		if ( $transaction_id !== null ) $data['transaction_id'] = strval( $transaction_id );

		$data['currency'] = $currency;
		$data['value'] = $total_price;
	
		if ( $tax !== null ) $data['tax'] = $tax;
		if ( $shipping !== null ) $data['shipping'] = $shipping;
		if ( $coupon !== null ) $data['coupon'] = $coupon;

		$data['items'] = $event_data_items;

		return $data;
	}

	/**
	 * Get product
	 *
	 * @param int          $product_id product id.
	 * @param int          $quantity product quantity.
	 * @return void|object of categories
	 */
	private function get_product( $product_id, $quantity = 1 ) {
		$product = wc_get_product( $product_id );

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$product_name = $product->get_name();
		$product_price = $product->get_price();
		$product_sale_price = $product->get_sale_price();
		$product_categories = $this->get_category_names( $product_id );

		$product_brand = '';
		$brand_terms = get_the_terms($product_id, 'brand_taxonomy');

		if ($brand_terms && !is_wp_error($brand_terms) && !is_wp_error($brand_terms)) {
			$product_brand = $brand_terms[0]->name;
		}

		$product_variant = array();

		if ( $product->is_type( 'variable' ) ) {
			$variation_id = isset( $_REQUEST['variation_id'] ) ? absint( $_REQUEST['variation_id'] ) : 0;
			$variation = wc_get_product( $variation_id );

			if ( $variation ) {
				$variation_attributes = $variation->get_variation_attributes();

				foreach ( $variation_attributes as $attribute_name => $attribute_options ) {
					$product_variant[] = $attribute_options;
				}
			}
		}

		$product_data = [
			[
				'id' => $product_id,
				'name' => $product_name,
				'brand' => $product_brand,
				'categories' => $product_categories,
				'variant' => implode(" / ", $product_variant),
				'price' => !empty($product_sale_price) && $product_sale_price > 0 ? $product_sale_price : $product_price,
				'quantity' => $quantity
			]
		];

		return $product_data;
	}

	/**
	 * Get product category names
	 *
	 * @param int          $product_id product id.
	 * @return void|object of categories
	 */
	private function get_category_names( $product_id ) {
		$product_categories = get_the_terms( $product_id, 'product_cat' );
		$category_names = array();

		if ( !is_wp_error( $product_categories ) && !empty( $product_categories ) ) {
			foreach ( $product_categories as $category ) {
				$category_names[] = $category->name;
			}
		}

		return $category_names;
	}

	/**
	 * Check if tracker can be enabled
	 *
	 * @return bool
	 */
	private function is_tracker_enabled() {
		$tracker_id = $this->get_setting_by_name( 'verfacto_tracker_id' );

		if ( ! empty( $tracker_id ) ) {
			$this->tracker_id = $tracker_id;
			return true;
		}

		return false;
	}

	/**
	 * Construct HTML attributes line
	 *
	 * @param array   $attributes attributes array of attributes.
	 * @return string of attributes
	 */
	private static function get_script_attributes( $attributes ) {
		$script_attributes = '';

		foreach ( $attributes as $tag => $value ) {
			$script_attributes .= ' ' . $tag . '="' . esc_attr( $value ) . '"';
		}

		return $script_attributes;
	}
}
