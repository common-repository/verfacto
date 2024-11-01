<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link  https://verfacto.com
 * @since 1.0.0
 *
 * @package    Verfacto_Plugin
 * @subpackage Verfacto_Plugin/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Verfacto_Plugin
 * @subpackage Verfacto_Plugin/admin
 * @author     Verfacto <support@verfacto.com>
 */
class Verfacto_Plugin_Admin {


	/**
	 * The ID of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/verfacto-plugin-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/verfacto-plugin-admin.js', array( 'jquery' ), $this->version, true );

		//localize script
		wp_localize_script(
			$this->plugin_name,
			'verfacto_ajax',
			array(
				'admin_ajax' => admin_url( 'admin-ajax.php' ),
				'vf_nonce'   => wp_create_nonce( 'verfacto_nonce' ),
			)
		);
	}


	public function add_last_modified_support( array $args, \WP_REST_Request $request ) {
		$modified_after = $request->get_param( 'modified_after' );

		if ( ! $modified_after ) {
			return $args;
		}

		$args['date_query'][0]['column'] = 'post_modified';
		$args['date_query'][0]['after']  = $modified_after;

		return $args;
	}

	public function add_settings_link( $links ) {
		$url = esc_url( add_query_arg(
			'page',
			'verfacto-plugin',
			get_admin_url() . 'admin.php'
		) );

		$settings_link = "<a href='$url'> Go to analytics </a>";

		array_push(
			$links,
			$settings_link
		);
		return $links;
	}

	public function verfacto_activation_redirect () {
		if ( ! get_transient( '_verfacto_activation_redirect' ) || isset( $_GET['verfacto-redirect'] ) ) {
			return;
		}

		delete_transient( '_verfacto_activation_redirect' );

		// Bail if activating from network, or bulk.
		if ( isset( $_GET['activate-multi'] ) ) {
			return;
		}

		$redirect = admin_url( 'admin.php?page=verfacto-plugin&verfacto-redirect=1' );
		wp_safe_redirect( $redirect );
		exit;
	}
}
