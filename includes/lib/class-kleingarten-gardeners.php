<?php
/**
 * A class to handle gardeners.
 *
 * @package Kleingarten/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plots handler class.
 */
class Kleingarten_Gardeners {

	/**
	 * Gardeners handler constructor.
	 *
	 * @return void
	 */
	public function __construct() {

	}

	/**
	 * Returns users who are recipients of new post notification mails.
	 *
	 * @return array
	 */
	public static function get_new_post_notification_recipients() {

		// Build a list of recipients
		$recipients = get_users( [
			'role__in'   => array(
				'administrator',
				'author',
				'subscriber',
				'kleingarten_allotment_gardener',
				'kleingarten_pending'
			),
			'meta_key'   => 'send_email_notifications',
			'meta_value' => '1',
		] );

		return $recipients;

	}

	public static function get_users_with_plot_assigned( $plots_id ) {

		// Build a list of users:
		$users = get_users( [
			'role__in'   => array(
				'administrator',
				'author',
				'subscriber',
				'kleingarten_allotment_gardener',
				'kleingarten_pending'
			),
			'meta_key'   => 'plot',
			'meta_value' => $plots_id,
		] );

		return $users;

	}

	public static function get_available_membership_status() {

		$available_membership = explode( "\r\n",
			get_option( 'kleingarten_available_membership_status' ) );

		if ( count( $available_membership ) == 1 && $available_membership[0] == '' ) {
			return array();
		}

		return $available_membership;

	}

}