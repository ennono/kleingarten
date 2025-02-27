<?php
/**
 * A class to represent a single plot.
 *
 * @package Kleingarten/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plot handler class.
 */
class Kleingarten_Plot {

	/**
	 * Post ID
	 *
	 * @var int
	 */
	private $post_ID;

	/**
	 * Post
	 *
	 * @obj WP_Post
	 */
	private $post;

	/**
	 * Meters
	 *
	 * @array
	 */
	//private $meters;

	/**
	 * Plot handler constructor.
	 *
	 * @return void
	 */
	public function __construct( $plot_ID ) {

		// Save at least the plot's post ID:
		$this->post_ID = $plot_ID;

		// Try get the plot's post:
		$this->post = get_post( $this->post_ID );

		// If getting the post succeeded initialize more:
		if ( $this->post != null ) {



		}

	}

	/**
	 * Little helper that returns true if given plot is assigned to given
	 * member and false if not.
	 *
	 * @return bool
	 */
	public function is_assigned_to_user( $user_ID ) {

		// If user ID is invalid, stop right here:
		if ( ! is_int( $user_ID ) || $user_ID <= 0 ) {
			return false;
		}

		$gardener = new Kleingarten_Gardener( $user_ID );

		// If plot ID is invalid, stop right here:
		if ( ! is_int( $this->post_ID ) ||  $this->post_ID <= 0 ) {
			return false;
		}

		// Try to get the assigned plot:
		//$plot = get_the_author_meta( 'plot', absint( $user_ID ) );
		$assigned_plot = $gardener->plot;

		// If user hast not plot assigned, stop here:
		if ( empty( $assigned_plot ) ) {
			return false;
		}

		// If assigned plot matches the given one, return true...
		if ( $assigned_plot == $this->post_ID ) {
			return true;
			// ... or false they don't match:
		} else {
			return false;
		}

	}

	/**
	 * Returns a list of assigned meters.
	 *
	 * @param   int   $plot_ID
	 * @param   bool  $return_meta_IDs
	 *
	 * @return array|false
	 */
	public function get_assigned_meters( bool $return_meta_IDs = false ) {

		require_once ABSPATH . 'wp-admin/includes/post.php';

		$assigned_meters = array();

		// If plot ID is 0 or less, stop right here:
		if ( $this->post_ID <= 0 ) {
			return false;
		}

		// If plot ID is no number, stop right here:
		if ( ! is_int( $this->post_ID ) ) {
			return false;
		}

		// Get all post meta for the given plot:
		$post_meta = has_meta( $this->post_ID );

		// Extract meter assignments:
		if ( is_array( $post_meta ) && $post_meta ) {
			foreach ( $post_meta as $j => $single_post_meta ) {

				if ( $single_post_meta['meta_key'] == 'kleingarten_meter_assignment' ) {
					if ( ! $return_meta_IDs ) {
						$assigned_meters[] = $single_post_meta['meta_value'];
					} else {
						$assigned_meters[] = $single_post_meta['meta_id'];
					}
				}

			}
		}

		// Finally return the list meters:
		return $assigned_meters;

	}

	/**
	 * Returns the plot's title.
	 *
	 * @return mixed
	 */
	public function get_title() {
		return get_the_title( $this->post_ID );
	}

}