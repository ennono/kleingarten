<?php
/**
 * A class to handle meters.
 *
 * @package Kleingarten/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meters handler class.
 */
class Kleingarten_Meters {

	/**
	 * Published meter posts
	 *
	 * @var
	 */
	private $meters;

	/**
	 * Meters handler constructor.
	 *
	 * @return void
	 */
	public function __construct( /*$assigned_to_meter = null*/ ) {

		// Get all published meters:
		$args = array(
			'post_type'      => 'kleingarten_meter',
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
		);

		// If we only want meters with a certain meter assigned:
		/*
		if ( $assigned_to_meter != null && is_int( $assigned_to_meter ) ) {
			$args['meta_key'] = 'kleingarten_meter_assignment';
			$args['meta_value'] = strval ( $with_meter_ID );
		}
		*/

		$this->meters = get_posts( $args );

	}

	/**
	 * Returns number of published posts.
	 *
	 * @return int|null
	 */
	public function get_meters_num() {

		if ( is_countable( $this->meters ) ) {
			return count( $this->meters );
		}

		return 0;

	}

	/**
	 * Returns a list of IDs of all published meters.
	 *
	 * @return array|false
	 */
	public function get_meter_IDs() {

		// Initialize as empty array to return an empty list if there are no meters:
		$meter_IDs = array();

		// Put every available meter on the list:
		foreach ( $this->meters as $meter ) {
			$meter_IDs[] = $meter->ID;
		}

		// Finally return the list we built:
		return $meter_IDs;

	}

}