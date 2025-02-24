<?php
/**
 * A class to represent all gardeners.
 *
 * @package Kleingarten/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gardener handler class.
 */
class Kleingarten_Gardeners {

	/**
	 * Construct gardeners class
	 */
	public function __construct() {

	}

	/**
	 * Adds a new gardener as WordPress user.
	 *
	 * @return void
	 */
	public function add_gardener( $user_data ) {

		$user_id = wp_insert_user( array(
				'user_login'      => $user_data["login"],
				'user_pass'       => $user_data["pw"],
				'user_email'      => $user_data["email"],
				'first_name'      => $user_data["firstname"],
				'last_name'       => $user_data["lastname"],
				'user_registered' => gmdate( 'Y-m-d H:i:s' ),
				'role'            => 'kleingarten_pending'
			)
		);

		$gardener = new Kleingarten_Gardener( $user_id );

		if ( ! is_wp_error( $user_id ) ) {
			//add_user_meta( $user_id, 'plot', $user_data["plot"] );
			$gardener->assign_plot( $user_data['plot'] );
			//add_user_meta( $user_id, 'send_email_notifications',
			//	$user_data["user_notifications"] );
			if ( isset( $user_data['user_notifications'] && $user_data['user_notifications'] == 1 ) ) {
				$gardener->set_notification_mail_receival();
			}
		}

		$this->send_welcome_email( $user_id );

		return $user_id;

	}

	/**
	 * Send welcome email
	 *
	 * @param   int  $user_id  User ID
	 *
	 * @return void
	 */
	private function send_welcome_email( $user_id ) {

		if ( get_option( 'kleingarten_send_account_registration_notification' ) ) {

			$site_name   = get_bloginfo( 'name' );
			$admin_email = get_bloginfo( 'admin_email' );
			$user_info   = get_userdata( $user_id );

			$to = $user_info->user_email;

			$headers[] = 'From: ' . $site_name . ' <' . $admin_email . '>';
			$headers[] = 'Content-Type: text/html';
			$headers[] = 'charset=UTF-8';

			$subject
				= sprintf( get_option( 'kleingarten_account_registration_notification_subject' ),
				$site_name );
			$message
				= sprintf( get_option( 'kleingarten_account_registration_notification_message' ),
				$site_name );

			wp_mail( $to, $subject, $message, $headers );

		}

	}

}