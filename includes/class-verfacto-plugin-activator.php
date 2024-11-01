<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Verfacto_Plugin
 * @subpackage Verfacto_Plugin/includes
 * @author     Verfacto <support@verfacto.com>
 */
class Verfacto_Plugin_Activator {


	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		self::check_system_requirements();

		if ( ! self::option_exists( 'verfacto_options' ) ) {
			add_option( 'verfacto_options', self::default_options() );
		}

		// Add transient to trigger redirect.
		set_transient( '_verfacto_activation_redirect', 1, 30 );
	}

	private static function check_system_requirements() {
		if ( current_user_can( 'activate_plugins' ) && ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			$error_message = '<p>' . esc_html__( 'This plugin requires ', 'verfacto' ) . '<a target="_blank" href="' . esc_url( 'https://wordpress.org/plugins/woocommerce/' ) . '">WooCommerce</a>' . esc_html__( ' plugin to be active.', 'verfacto' ) . '</p>';
			die( $error_message );
		}
	}

	private static function default_options() {
		$options = array(
			'verfacto_plugin_active'   => false,
			'verfacto_account_id'      => null,
			'verfacto_user_email'      => null,
			'verfacto_login_token'     => null,
			'verfacto_tracker_id'      => null,
			'verfacto_consumer_key'    => null,
			'verfacto_consumer_secret' => null,
		);

		return $options;
	}

	private static function option_exists( $name ) {
		global $wpdb;
		return $wpdb->query( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}options WHERE option_name = %s", $name ) );
	}

}
