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
	 * User ID
	 *
	 * @var
	 */
	private $user_ID;

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

		$user = get_user_by( 'ID', $user_ID );

		$this->user_login = $user->user_login;
		$this->email = $user->user_email;
		$this->first_name = $user->first_name;
		$this->last_name = $user->last_name;

		// Try to get the  assigned plot:
		$this->plot = absint( get_the_author_meta( 'plot', absint( $user_ID ) ) );

		// If user hast no plot assigned, set the property to 0:
		if ( empty( $this->plot ) ) {
			$this->plot = 0;
		}

		// Get the positions or save an empty list if there are none:
		$this->positions = get_the_author_meta( 'positions', $user_ID );
		if ( empty( $this->positions ) ) {
			$this->positions = array();
		}

		if ( get_the_author_meta( 'send_email_notifications', $user_ID ) == 1 ) {
			$this->receives_notification_mails = true;
		} else {
			$this->receives_notification_mails = false;
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
	 * Makes the gardener receive notification mails
	 *
	 * @return mixed
	 */
	public function set_notification_mail_receival() {
		return update_user_meta( $this->user_ID, 'send_email_notifications', 1 );
	}

	/**
	 * Makes the gardener NOT receive notification mails
	 *
	 * @return mixed
	 */
	public function unset_notification_mail_receival() {
		return update_user_meta( $this->user_ID, 'send_email_notifications', 0 );
	}

}