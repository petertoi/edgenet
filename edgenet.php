<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://petertoi.com
 * @since             1.0.0
 * @package           Edgenet
 *
 * @wordpress-plugin
 * Plugin Name:       Edgenet
 * Plugin URI:        https://petertoi.com
 * Description:       Sync product content on Edgenet with WordPress and Woocommerce
 * Version:           1.0.2
 * Author:            Peter Toi <peter@petertoi.com>
 * Author URI:        http://petertoi.com
 * Text Domain:       edgenet
 * Domain Path:       /languages
 */

require 'autoloader.php';

/**
 * Provide access to Edgenet without adding to $GLOBALS
 *
 * @return \Edgenet\Edgenet The Edgenet singleton
 */
function edgenet() {
	$edgenet = \Edgenet\Edgenet::get_instance();

	return $edgenet;
}

// Go!
edgenet();
