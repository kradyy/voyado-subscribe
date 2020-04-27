<?php
/**
 * Plugin Name: Voyado Subscribe - Jägerso
 * Version: 1.0.0
 * Plugin URI: 		  https://www.mildmedia.se
 * Description: 	  Integrates Voyado with Wordpress
 * Author:            Mild Media
 * Author URI:        https://www.mildmedia.se
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: voyado-subscribe
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Chris Johansson
 * @since 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

// Load plugin class files.
require_once 'includes/class-voyado-subscribe.php';
require_once 'includes/class-voyado-subscribe-settings.php';

// Load plugin libraries.
require_once 'includes/lib/class-voyado-subscribe-fields.php';
require_once 'includes/lib/class-voyado-api.php';
require_once 'includes/lib/class-voyado-helper.php';

/**
 * Returns the main instance to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Voyado_Subscribe
 */
function voyado_subscribe() {
	$instance = Voyado_Subscribe::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Voyado_Subscribe_Settings::instance( $instance );
	}
	
	return $instance;
}

voyado_subscribe();
