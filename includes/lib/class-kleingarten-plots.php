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

}