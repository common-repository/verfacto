<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Verfacto_Plugin
 * @subpackage Verfacto_Plugin/includes
 * @author     Verfacto <support@verfacto.com>
 */
class Verfacto_Plugin {

	const API_BASE_URL   = 'https://api.verfacto.com/auth/v1';
	const BACKOFFICE_URL = 'https://backoffice.verfacto.com';
	const TRACKER_URL    = 'https://analytics.verfacto.com/distributor.js';

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Verfacto_Plugin_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( defined( 'VERFACTO_PLUGIN_VERSION' ) ) {
			$this->version = VERFACTO_PLUGIN_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'verfacto-plugin';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Verfacto_Plugin_Loader. Orchestrates the hooks of the plugin.
	 * - Verfacto_Plugin_i18n. Defines internationalization functionality.
	 * - Verfacto_Plugin_Admin. Defines all hooks for the admin area.
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-verfacto-plugin-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-verfacto-plugin-i18n.php';

		/**
		 * The class responsible for plugin installation logic.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-verfacto-plugin-handle-installation.php';

		/**
		 * The class responsible for Verfacto custom events tracking.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-verfacto-plugin-event-tracker.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-verfacto-plugin-admin.php';

		$this->loader = new Verfacto_Plugin_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Verfacto_Plugin_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function set_locale() {

		$plugin_i18n = new Verfacto_Plugin_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {
		$plugin_admin               = new Verfacto_Plugin_Admin( $this->get_plugin_name(), $this->get_version() );
		$plugin_handle_installation = new Verfacto_Plugin_Handle_Installation( $this->get_plugin_name(), $this->get_version() );

		// Add plugin assets.
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Redirect to plugin page on activating plugin.
		$this->loader->add_action( 'admin_init', $plugin_admin, 'verfacto_activation_redirect', 9999 );

		// Add last_modified support to WC REST API.
		$this->loader->add_filter( 'woocommerce_rest_orders_prepare_object_query', $plugin_admin, 'add_last_modified_support', 10, 2 );
		$this->loader->add_filter( 'woocommerce_rest_product_object_query', $plugin_admin, 'add_last_modified_support', 10, 2 );

		// Add "Go to analytics" link in plugin.
		$this->loader->add_filter( 'plugin_action_links_' . basename( plugin_dir_path( dirname( __FILE__, 1 ) ) ) . '/verfacto-plugin.php', $plugin_admin, 'add_settings_link' );

		// Hooks responsible for installation handeling.
		$this->loader->add_action( 'admin_menu', $plugin_handle_installation, 'add_menu_items' );
		$this->loader->add_action( 'woocommerce_api_authorize_wc', $plugin_handle_installation, 'authorize_wc_callback' );
		$this->loader->add_action( 'wp_ajax_activate_verfacto', $plugin_handle_installation, 'activate_verfacto' );
		$this->loader->add_action( 'admin_notices', $plugin_handle_installation, 'check_integration_notice' );
		new Verfacto_Plugin_Event_Tracker();
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since  1.0.0
	 * @return string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since  1.0.0
	 * @return Verfacto_Plugin_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since  1.0.0
	 * @return string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
