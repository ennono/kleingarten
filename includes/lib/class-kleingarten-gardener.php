<?php
/**
 * A class to represent a single gardener.
 *
 * @package Kleingarten/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gardener handler class.
 */
class Kleingarten_Gardener {


	/**
	 * User login name
	 *
	 * @var
	 */
	public $user_login;
	/**
	 * User email
	 *
	 * @var
	 */
	public $email;
	/**
	 * First name
	 *
	 * @var
	 */
	public $first_name;
	/**
	 * Last name
	 *
	 * @var
	 */
	public $last_name;
	/**
	 * Display name
	 *
	 * @var
	 */
	public $disply_name;
	/**
	 * ID of assigned plot
	 *
	 * @var
	 */
	public $plot;
	/**
	 * List of positions
	 *
	 * @var
	 */
	public $positions;
	/**
	 * User ID
	 *
	 * @var
	 */
	private $user_ID;
	/**
	 * Flag indicating if gardener wants to receive notification mails
	 *
	 * @var
	 */
	private $receives_notification_mails;

	/**
	 * Gardener handler constructor.
	 *
	 * @return void
	 */
	public function __construct( $user_ID ) {

		$this->user_ID = $user_ID;

		// Try to get the user with the given ID:
		$user = get_user_by( 'ID', $user_ID );

		// If we found a user with the given ID...
		if ( $user != false ) {

			$this->user_login  = $user->user_login;
			$this->email       = $user->user_email;
			$this->first_name  = $user->first_name;
			$this->last_name   = $user->last_name;
			$this->disply_name = $user->display_name;

			// Try to get the  assigned plot:
			$this->plot = absint( get_the_author_meta( 'plot',
				absint( $user_ID ) ) );

			// If user hast no plot assigned, set the property to 0:
			if ( empty( $this->plot ) ) {
				$this->plot = 0;
			}

			// Get the positions or save an empty list if there are none:
			$this->positions = get_the_author_meta( 'positions', $user_ID );
			if ( empty( $this->positions ) ) {
				$this->positions = array();
			}

			if ( get_the_author_meta( 'send_email_notifications', $user_ID )
			     == 1 ) {
				$this->receives_notification_mails = true;
			} else {
				$this->receives_notification_mails = false;
			}

		}

	}

	/**
	 * Adds a new gardener as WordPress user.
	 *
	 * @param $user_data
	 *
	 * @return void
	 */
	public static function add_gardener( $user_data ) {

		// Create a new WordPress user:
		$user_id = wp_insert_user( array(
				'user_login'      => sanitize_user( wp_unslash( $user_data["login"] ) ),
				'user_pass'       => $user_data["pw"],
				'user_email'      => sanitize_email( wp_unslash( $user_data["email"] ) ),
				'first_name'      => sanitize_text_field( wp_unslash( $user_data["firstname"] ) ),
				'last_name'       => sanitize_text_field( wp_unslash( $user_data["lastname"] ) ),
				'user_registered' => gmdate( 'Y-m-d H:i:s' ),
				'role'            => 'kleingarten_pending'
			)
		);

		// If we successfully created a user,
		// assign a plot and set notification mails:
		$gardener = new Kleingarten_Gardener( $user_id );
		if ( ! is_wp_error( $user_id ) ) {
			$gardener->assign_plot( absint( $user_data['plot'] ) );
			if ( isset( $user_data['user_notifications'] )
			     && $user_data['user_notifications'] == 1 ) {
				$gardener->set_notification_mail_receival();
			}

			// But if there were errors on creating the user,
			// stop here and return the error object:
		} else {
			return $user_id;
		}

		// Send a welcome mail:
		$new_gardener = new static( $user_id );     // Instantiate us
		$new_gardener->send_welcome_email( $user_id );

		return $user_id;

	}

	/**
	 * Assigns a plot to the gardener.
	 *
	 * @param $positions
	 *
	 * @return mixed
	 */
	public function assign_plot( $plot_ID ) {

		if ( $plot_ID < 0 ) {
			$plot_ID = 0;
		}

		return update_user_meta( $this->user_ID, 'plot', absint( $plot_ID ) );

	}

	/**
	 * Makes the gardener receive notification mails
	 *
	 * @return mixed
	 */
	public function set_notification_mail_receival() {
		return update_user_meta( $this->user_ID, 'send_email_notifications',
			1 );
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

	/**
	 * Sets the passed positions for the gardener.
	 *
	 * @param $positions
	 *
	 * @return mixed
	 */
	public function set_positions( $positions ) {

		$positions = array_unique( array_map( 'sanitize_text_field',
			wp_unslash( $positions ) ) );

		return update_user_meta( $this->user_ID, 'positions', $positions );
	}

	/**
	 * Purges all positions from the gardener.
	 *
	 * @return mixed
	 */
	public function remove_all_positions() {
		return delete_user_meta( $this->user_ID, 'positions' );
	}

	/**
	 * Returns true if gardener has a plot and false if not.
	 *
	 * @return bool
	 */
	public function has_assigned_plot() {
		if ( $this->plot != 0 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns true if gardener wants to receive notification mail and false if
	 * not.
	 *
	 * @return bool
	 */
	public function receives_notification_mails() {
		return $this->receives_notification_mails;
	}

	/**
	 * Makes the gardener NOT receive notification mails
	 *
	 * @return mixed
	 */
	public function unset_notification_mail_receival() {
		return update_user_meta( $this->user_ID, 'send_email_notifications',
			0 );
	}

	/**
	 * Returns true if gardeners is allowed to like stuff, and false if not.
	 *
	 * @return bool
	 */
	public function is_allowed_to_like() {

		// For now every logged in user is allowed to like:
		return is_user_logged_in();

	}

	/**
	 * Returns the gardener's user ID.
	 *
	 * @return mixed
	 */
	public function get_user_id() {
		return $this->user_ID;
	}

}