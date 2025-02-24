<?php
/**
 * This file runs when the plugin in uninstalled (deleted).
 * This will not run when the plugin is deactivated.
 * Ideally you will add all your clean-up scripts here
 * that will clean up unused meta, options, etc. in the database.
 *
 * @package Kleingarten/Uninstall
 */

// If plugin is not being uninstalled, exit (do nothing).
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Do something here if plugin is being uninstalled:

// Delete options:
$options = array(
	'kleingarten_available_positions',
	'kleingarten_login_page',
	'kleingarten_post_types_with_auto_likes_shortcode',
	'kleingarten_auto_likes_shortcode_position',
	'kleingarten_send_account_registration_notification',
	'kleingarten_account_registration_notification_subject',
	'kleingarten_account_registration_notification_message',
	'kleingarten_send_account_activation_notification',
	'kleingarten_account_activation_notification_subject',
	'kleingarten_account_activation_notification_message',
	'kleingarten_send_new_post_notification',
	'kleingarten_send_new_post_notification_post_type_selection',
	'kleingarten_new_post_notification_subject',
	'kleingarten_new_post_notification_message',
	'kleingarten_version',
	'kleingarten_meter_reading_submission_token_time_to_live',
	'kleingarten_units_available_for_meters',
	'kleingarten_show_footer_credits'
);
foreach ( $options as $option ) {
	delete_option( $option );
}
