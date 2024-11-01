<?php

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/trait-verfacto-plugin-helper.php';

/**
 * Handle plugin instalation steps.
 */
class Verfacto_Plugin_Handle_Installation {

	use Verfacto_Plugin_Helper;

	/**
	 * Initializing plugin installation process
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function activate_verfacto() {
		if ( wp_doing_ajax() ) {

			if ( empty( wp_unslash( $_POST['vf_nonce'] ) ) || ! wp_verify_nonce( wp_unslash( $_POST['vf_nonce'] ), 'verfacto_nonce' ) ) {
				wp_send_json_error( 'Invalid nonce.', 403 );
			}

			$email       = isset( $_POST['user_email'] ) ? strtolower( trim( sanitize_email( $_POST['user_email'] ) ) ) : '';
			$password    = isset( $_POST['user_password'] ) ? trim( sanitize_text_field( $_POST['user_password'] ) ) : '';

			if ( ! is_email( $email ) ) {
				wp_send_json_error( 'Looks like your email is invalid.', 403 );
			}

			if ( strlen( $password ) < 8 || strlen( $password ) > 128 ) {
				wp_send_json_error( 'Looks like your password is incorrect length. Password must be between 8 and 128 character', 403 );
			}

			if ( ! get_option( 'permalink_structure' ) ) {
				set_transient( 'verfacto_error_message', 'You need to have different permalink structure, than plain <a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">Go to settings</a>', 60 );
				$data = array(
					'url'     => esc_url( admin_url( '/admin.php?page=verfacto-plugin' ) ),
					'open_in' => '_self',
				);
				wp_send_json( $data );
			}

			$response = $this->authorize_backoffice_access( $email, $password );

			if ( 200 !== $response['response_code'] ) {
				wp_send_json_error( 'Looks like your credentials are invalid.', $response['response_code'] );
			}

			$verfacto_options                         = get_option( 'verfacto_options' );
			$verfacto_options['verfacto_user_email']  = $email;
			$verfacto_options['verfacto_login_token'] = $password;
			update_option( 'verfacto_options', $verfacto_options );

			$store_url    = preg_replace( '(^http?://)', 'https://', get_bloginfo( 'url' ) );
			$remote_check = wp_remote_get( $store_url );

			if ( is_wp_error( $remote_check ) || empty( $remote_check['response'] ) || 200 !== $remote_check['response']['code'] ) {
				wp_send_json_error( 'SSL validation problem occured, your website cannot be reached via "https://" protocol. You need to enable SSL or check firewall configuration as it might be preventing request. Please whitelist the server IP(s) AND our IPs (34.246.234.225, 54.73.206.223, 54.78.120.70) in your application firewall.', 403 );
			}

			$endpoint = '/wc-auth/v1/authorize';

			$params = array(
				'app_name'     => 'Verfacto',
				'scope'        => 'read',
				'user_id'      => get_current_user_id(),
				'return_url'   => esc_url( admin_url( '/admin.php?page=verfacto-plugin' ) ),
				'callback_url' => $store_url . '/wc-api/authorize_wc',
			);

			$data = array(
				'url'     => $store_url . $endpoint . '?' . http_build_query( $params ),
				'open_in' => '_self',
			);
			wp_send_json( $data );

		}
	}

	/**
	 * Callback function for Woocommerce authentication
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function authorize_wc_callback() {
		$post_data = file_get_contents( 'php://input' );

		if ( empty( $post_data ) ) {
			http_response_code( 400 );
			wp_die();
		}

		$decoded = json_decode( $post_data );

		$platform_info = $this->get_platform_info();
		$account_name  = $this->verfacto_account_name();

		$integration_payload = array(
			'access_key'                  => $decoded->consumer_key,
			'access_key_secret'           => $decoded->consumer_secret,
			'name'                        => $account_name,
			'owner_email'                 => $this->get_setting_by_name( 'verfacto_user_email' ),
			'password'                    => $this->get_setting_by_name( 'verfacto_login_token' ),
			'shop_api_version'            => $platform_info['shop_api_version'],
			'shop_url'                    => $platform_info['shop_url'],
			'shop_currency'               => get_woocommerce_currency(),
		);

		$response = $this->call_api_endpoint( 'POST', $integration_payload, '/plugins/' . $platform_info['integrate_version'] );

		switch ( $response['response_code'] ) :
			case 200:
				$verfacto_options                             = get_option( 'verfacto_options' );
				$verfacto_options['verfacto_plugin_active']   = true;
				$verfacto_options['verfacto_account_name']    = $account_name;
				$verfacto_options['verfacto_account_id']      = $response['response_body']['account_id'];
				$verfacto_options['verfacto_tracker_id']      = $response['response_body']['tracking_id'];
                $verfacto_options['verfacto_consumer_key']    = wc_api_hash( $decoded->consumer_key );
                $verfacto_options['verfacto_consumer_secret'] = $decoded->consumer_secret;
				update_option( 'verfacto_options', $verfacto_options );
				break;
			default:
				global $wpdb;
				$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_api_keys WHERE description LIKE '%Verfacto - API%'" );
				set_transient( 'verfacto_error_message', 'Looks like your credentials are invalid or user does not exist in account, please try again', 60 );
				break;
		endswitch;

	}

    /**
     * Check verfacto integration
     */
    public function check_integration_notice() {
        if ( !$this->check_auth_keys('verfacto_consumer_key', 'verfacto_consumer_secret') ) {
            echo "<div class='wrap'><div class='notice inline notice-warning notice-alt'><p>Verfacto plugin is not active. Please <a href=" . admin_url( 'admin.php?page=verfacto-plugin' ) . ">re-integrate</a> it.</p></div></div>";
        }
    }

	/**
	 * The necessary data about the E-shop
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_platform_info() {
		$site_url = get_bloginfo( 'url' );

		$platform = array(
			'shop_api_version'  => 'wc/v3',
			'shop_url'          => $site_url,
			'integrate_version' => 'woocommerce_above_v3.5',
		);

		if ( function_exists( 'is_woocommerce_active' ) && is_woocommerce_active() ) {
			global $woocommerce;
			if ( version_compare( $woocommerce->version, 3.5, '<' ) ) {
				$platform = array(
					'shop_api_version'  => 'wc/v2',
					'shop_url'          => $site_url,
					'integrate_version' => 'woocommerce_below_v3.5',
				);
			}
		}

		return $platform;
	}

	/**
	 * Add 'Verfacto' sub-menu item to Woocommerce menu
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_menu_items() {
		add_submenu_page( 'woocommerce', 'Verfacto', 'Verfacto', 'manage_options', 'verfacto-plugin', array( $this, 'verfacto_instalation_page' ) );
	}

	/**
	 * Add Verfacto installation page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function verfacto_instalation_page() {
		$account_name         = $this->verfacto_account_name();
		$plugin_active        = $this->get_setting_by_name( 'verfacto_plugin_active' );
        $keys_exist           = $this->check_auth_keys('verfacto_consumer_key', 'verfacto_consumer_secret');
		$is_page_refreshed    = $this->is_page_refreshed();
		$forgot_password_url  = Verfacto_Plugin::BACKOFFICE_URL . '/forgot-password?accountName=' . $account_name . '&platform=WooCommerce';
		$open_verfacto        = Verfacto_Plugin::BACKOFFICE_URL . '/?accountName=' . $account_name;
		$go_to_create_account = Verfacto_Plugin::BACKOFFICE_URL . '/signup?pluginPath=' . esc_url( admin_url( '/admin.php?page=verfacto-plugin' ) ) . '&accountName=' . $account_name . '&platform=WooCommerce';

		ob_start();
		include_once plugin_dir_path( dirname( __FILE__ ) ) . '/admin/pages/verfacto-plugin-instalation-page.php';
		echo ob_get_clean();
	}

}
