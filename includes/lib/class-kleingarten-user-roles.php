<?php
/**
 * User Roles.
 *
 * @package Kleingarten/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomy functions class.
 */
class Kleingarten_User_Roles {

	/**
	 * User Roles constructor.
	 *
	 */
	public function __construct() {

		// Add user roles:
		add_action( 'init',
			array( $this, 'add_user_role_allotment_gardener' ) );
		add_action( 'init', array( $this, 'add_user_role_pending' ) );
		add_action( 'set_user_role',
			array( $this, 'send_activation_notification' ), 10, 3 );
		//add_action ('register_new_user', array ($this, 'send_registration_notification'), 10, 3 );

		// Hijack default role for new users:
		add_filter( 'pre_option_default_role',
			array( $this, 'set_default_role' ) );

		// Globally disble password reset 
		add_filter( 'allow_password_reset', '__return_false' );

	}

	/**
	 * Add member user role
	 *
	 * @return void
	 */
	public function add_user_role_allotment_gardener() {

		$capabilities = array(
			'read'               => true,
			'read_private_posts' => true,
		);

		if ( get_option( 'kleingarten_allotment_gardener' ) < 1 ) {
			add_role( 'kleingarten_allotment_gardener',
				__( 'Allotment Gardener', 'kleingarten' ), $capabilities );
			update_option( 'kleingarten_allotment_gardener', 1 );
		}

	}

	/**
	 * Add pending user role
	 *
	 * @return void
	 */
	public function add_user_role_pending() {

		// No capabilities for pending users:
		$capabilities = array(
			''
		);

		if ( get_option( 'kleingarten_pending' ) < 1 ) {
			add_role( 'kleingarten_pending',
				__( 'Pending Allotment Gardener', 'kleingarten' ),
				$capabilities );
			update_option( 'kleingarten_pending', 1 );
		}

	}

	/**
	 * Set default role
	 *
	 * @return string 'kleingarten_pending'
	 */
	public function set_default_role( $default_role ) {

		// $default_role is what admin sets in admin dashboard.
		// We ignore this and return pending role to make sure 
		// that new users allway get pending role first.

		return 'kleingarten_pending';

	}

	/**
	 * Send email on activation
	 *
	 * @return void
	 */
	public function send_activation_notification( $user_id, $new_role ) {

		if ( get_option( 'kleingarten_send_account_activation_notification' ) ) {

			if ( $new_role == 'kleingarten_allotment_gardener' ) {

				$site_name   = get_bloginfo( 'name' );
				$admin_email = get_bloginfo( 'admin_email' );
				$user_info   = get_userdata( $user_id );

				$to = $user_info->user_email;

				$headers[] = 'From: ' . $site_name . ' <' . $admin_email . '>';
				$headers[] = 'Content-Type: text/html';
				$headers[] = 'charset=UTF-8';

				$subject
					= sprintf( get_option( 'kleingarten_account_activation_notification_subject' ),
					$site_name );
				$message
					= sprintf( get_option( 'kleingarten_account_activation_notification_message' ),
					$site_name );

				wp_mail( $to, $subject, $message, $headers );

			}

		}

	}

}
