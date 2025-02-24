<?php
/**
 * A class to handle plots.
 *
 * @package Kleingarten/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plots handler class.
 */
class Kleingarten_Plots {

	/**
	 * Published plot posts
	 *
	 * @var
	 */
	private $plots;

	/**
	 * Number of published posts
	 *
	 * @var int|null
	 */
	private $plots_num;

	/**
	 * Plots handler constructor.
	 *
	 * @return void
	 */
	public function __construct() {

		// Get all published plots:
		$args  = array(
			'post_type'      => 'kleingarten_plot',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);
		$this->plots = get_posts( $args );

		// Count plots:
		$this->plots_num = count( $this->plots );

	}

	/**
	 * Returns number of publihed posts.
	 *
	 * @return int|null
	 */
	public function get_plots_num() {
		return $this->plots_num;
	}

	/**
	 * Returns a list of meters assigned to a given plot.
	 *
	 * @param   int   $plot_ID
	 * @param   bool  $return_meta_IDs
	 *
	 * @return array|false
	 */
	public function get_assigned_meters( int $plot_ID, bool $return_meta_IDs = false ) {

		require_once ABSPATH . 'wp-admin/includes/post.php';

		$assigned_meters = array();

		// If plot ID is 0 or less, stop right here:
		if ( $plot_ID <= 0 ) {
			return false;
		}

		// If plot ID is no number, stop right here:
		if ( ! is_int( $plot_ID ) ) {
			return false;
		}

		// Get all post meta for the given plot:
		$post_meta = has_meta( $plot_ID );

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
	 * Returns a list of IDs of all published plots.
	 *
	 * @return array|false
	 */
	public function get_plot_IDs() {

		// Initialize as empty array to return an empty list if there are no plots:
		$plot_IDs = array();

		// Put every available plot on the list:
		foreach( $this->plots as $plot ) {
			$plot_IDs[] = $plot->ID;
		}

		// Finally return the list we built:
		return $plot_IDs;

	}

	/**
	 * Little helper that returns true if given plot is assigned to given
	 * member and false if not.
	 *
	 * @return bool
	 */
	public function plot_is_assigned_to_user( $plot_ID, $user_ID ) {

		// If user ID is invalid, stop right here:
		if ( ! is_int( $user_ID ) || $user_ID <= 0 ) {
			return false;
		}

		// If plot ID is invalid, stop right here:
		if ( ! is_int( $plot_ID ) ||  $plot_ID <= 0 ) {
			return false;
		}

		// Try to get the the assigned plot:
		$plot = get_the_author_meta( 'plot', absint( $user_ID ) );

		// If user hast not plot assigned, stop here:
		if ( empty( $plot ) ) {
			return false;
		}

		// If assigned plot matches the given one, return true...
		if ( $plot == $plot_ID ) {
			return true;
		// ... or false they don't match:
		} else {
			return false;
		}

	}

}