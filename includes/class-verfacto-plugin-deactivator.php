<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Verfacto_Plugin
 * @subpackage Verfacto_Plugin/includes
 * @author     Verfacto <support@verfacto.com>
 */

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/trait-verfacto-plugin-helper.php';
class Verfacto_Plugin_Deactivator {

	use Verfacto_Plugin_Helper;

	/**
	 * Runs on plugin deactivation hook.
	 */
	public function deactivate() {
		if ( (bool) $this->get_setting_by_name( 'verfacto_plugin_active' ) ) {
			$authorize_token = $this->authorize_backoffice_access();

			$payload = array(
				'to_delete_at'      => gmdate( 'Y-m-d H:i:s', strtotime( '+ 14 DAY' ) ),
				'send_notification' => true,
			);

			$this->call_api_endpoint( 'PUT', $payload, '/accounts/' . $this->get_setting_by_name( 'verfacto_account_id' ) . '/schedule-account-deletion', $authorize_token['response_body']['auth_token'] );

			global $wpdb;
			$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_api_keys WHERE description LIKE '%Verfacto - API%'" );
		}

		$verfacto_options                             = get_option( 'verfacto_options' );
		$verfacto_options['verfacto_plugin_active']   = false;
		$verfacto_options['verfacto_account_id']      = null;
		$verfacto_options['verfacto_account_name']    = null;
		$verfacto_options['verfacto_user_email']      = null;
		$verfacto_options['verfacto_login_token']     = null;
		$verfacto_options['verfacto_tracker_id']      = null;
		$verfacto_options['verfacto_consumer_key']    = null;
		$verfacto_options['verfacto_consumer_secret'] = null;
		update_option( 'verfacto_options', $verfacto_options );

	}
}
