<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://usstove.com
 * @since             1.0.0
 * @package           USSC_Edgenet
 *
 * @wordpress-plugin
 * Plugin Name:       USSC Edgenet Integration
 * Plugin URI:        https://usstove.com
 * Description:       Sync product content on Edgenet with WordPress and Woocommerce
 * Version:           1.0.0-alpha
 * Author:            Peter Toi <peter@petertoi.com>
 * Author URI:        http://petertoi.com
 * Text Domain:       ussc
 * Domain Path:       /languages
 */

require 'autoloader.php';

/**
 * Provide access to the WP_Vote without adding to $GLOBALS
 *
 * @return \USSC_Edgenet\Edgenet The USSC_Edgenet singleton
 */
function edgenet() {
	$edgenet = \USSC_Edgenet\Edgenet::get_instance();

	return $edgenet;
}

// Go!
edgenet();
