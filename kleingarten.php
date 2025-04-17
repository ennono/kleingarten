<?php
/**
 * Plugin Name: Kleingarten
 * Version: 1.2.0
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
require_once 'includes/class-kleingarten-admin-pages.php';

// Load plugin libraries.
require_once 'includes/lib/class-kleingarten-admin-api.php';
require_once 'includes/lib/class-kleingarten-post-types.php';
require_once 'includes/lib/class-kleingarten-userfields.php';
require_once 'includes/lib/class-kleingarten-user-roles.php';
require_once 'includes/lib/class-kleingarten-shortcodes.php';
require_once 'includes/lib/class-kleingarten-post-meta.php';
require_once 'includes/lib/class-kleingarten-plots.php';
require_once 'includes/lib/class-kleingarten-meters.php';
require_once 'includes/lib/class-kleingarten-plot.php';
require_once 'includes/lib/class-kleingarten-meter.php';
require_once 'includes/lib/class-kleingarten-gardener.php';
require_once 'includes/lib/class-kleingarten-gardeners.php';
require_once 'includes/lib/class-kleingarten-task.php';
require_once 'includes/lib/class-kleingarten-tasks.php';
require_once 'includes/lib/class-kleingarten-project.php';

/**
 * Returns the main instance of Kleingarten to prevent the need to use globals.
 *
 * @return object Kleingarten
 * @since  1.0.0
 */
function kleingarten() {

	$instance = Kleingarten::instance( __FILE__, '1.1.8' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Kleingarten_Settings::instance( $instance );
	}

	if ( is_null( $instance->tools ) ) {
		$instance->tools = Kleingarten_Tools::instance( $instance );
	}

	if ( is_null( $instance->admin_pages ) ) {
		$instance->admin_pages = Kleingarten_Admin_Pages::instance( $instance );
	}

	return $instance;
}

kleingarten();
kleingarten()->add_userfields();
kleingarten()->add_user_roles();
kleingarten()->add_post_types();
kleingarten()->add_shortcodes();
kleingarten()->add_post_meta();



