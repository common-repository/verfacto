<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link    https://verfacto.com
 * @since   1.0.0
 * @package Verfacto_Plugin
 *
 * @wordpress-plugin
 * Plugin Name:       Verfacto
 * Plugin URI:        https://verfacto.com
 * Description:       Verfacto WordPress Plugin automatically synchronizes data from your eShop with Verfacto analytics.
 * Version:           1.0.18
 * Author:            Verfacto
 * Author URI:        https://verfacto.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       verfacto-plugin
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'VERFACTO_PLUGIN_VERSION', '1.0.18' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-verfacto-plugin-activator.php
 */
function activate_verfacto_plugin() {
	include_once plugin_dir_path( __FILE__ ) . 'includes/class-verfacto-plugin-activator.php';
	Verfacto_Plugin_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-verfacto-plugin-deactivator.php
 */
function deactivate_verfacto_plugin() {
	include_once plugin_dir_path( __FILE__ ) . 'includes/class-verfacto-plugin-deactivator.php';

	$deactivator = new Verfacto_Plugin_Deactivator();
	$deactivator->deactivate();
}

register_activation_hook( __FILE__, 'activate_verfacto_plugin' );
register_deactivation_hook( __FILE__, 'deactivate_verfacto_plugin' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-verfacto-plugin.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0.0
 */
function run_verfacto_plugin() {
	$plugin = new Verfacto_Plugin();
	$plugin->run();

}
run_verfacto_plugin();
