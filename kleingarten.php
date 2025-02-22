<?php
/**
 * Plugin Name: Kleingarten
 * Version: 1.1.4
 * Plugin URI: https://www.wp-kleingarten.de/
 * Description: Make your website the digital home for your allotment garden association.
 * Author: Timo Fricke
 * Requires at least: 4.0
 * Tested up to: 6.7
 * License: GPLv2
 *
 * Text Domain: kleingarten
 * Domain Path: /lang/
 *
 * @package Kleingarte
 * @author  Timo Frixke
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load plugin class files.
require_once 'includes/class-kleingarten.php';
require_once 'includes/class-kleingarten-settings.php';
require_once 'includes/class-kleingarten-tools.php';

// Load plugin libraries.
require_once 'includes/lib/class-kleingarten-admin-api.php';
require_once 'includes/lib/class-kleingarten-post-types.php';
require_once 'includes/lib/class-kleingarten-userfields.php';
require_once 'includes/lib/class-kleingarten-user-roles.php';
require_once 'includes/lib/class-kleingarten-shortcodes.php';
require_once 'includes/lib/class-kleingarten-post-meta.php';

/**
 * Returns the main instance of Kleingarten to prevent the need to use globals.
 *
 * @return object Kleingarten
 * @since  1.0.0
 */
function kleingarten() {
	$instance = Kleingarten::instance( __FILE__, '1.1.4' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Kleingarten_Settings::instance( $instance );
	}

	//if ( is_null( $instance->tools ) ) {
	$instance->tools = Kleingarten_Tools::instance( $instance );

	//}

	return $instance;
}

kleingarten();
kleingarten()->add_userfields();
kleingarten()->add_user_roles();
kleingarten()->add_post_types();
kleingarten()->add_shortcodes();
kleingarten()->add_post_meta();



