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
	 * Plots handler constructor.
	 *
	 * @return void
	 */
	public function __construct( $with_meter_ID_assigned = null ) {

		// Get all published plots:
		$args = array(
			'post_type'      => 'kleingarten_plot',
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
		);

		// If we only want plots with a certain meter assigned:
		if ( $with_meter_ID_assigned != null
		     && is_int( $with_meter_ID_assigned ) ) {
			$args['meta_key']   = 'kleingarten_meter_assignment';
			$args['meta_value'] = strval( $with_meter_ID_assigned );
		}

		$this->plots = get_posts( $args );

	}

	/**
	 * Returns number of published posts.
	 *
	 * @return int|null
	 */
	public function get_plots_num() {

		if ( is_countable( $this->plots ) ) {
			return count( $this->plots );
		}

		return 0;

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
		foreach ( $this->plots as $plot ) {
			$plot_IDs[] = $plot->ID;
		}

		// Finally return the list we built:
		return $plot_IDs;

	}

}