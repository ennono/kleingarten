<?php
/**
 * Tasks file.
 *
 * @package Kleingarten/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomy functions class.
 */
class Kleingarten_Tasks {

	/**
	 * Tasks constructor.
	 *
	 * @return void
	 */
	public function __construct( $post_ID ) {

	}

	/**
	 * Returns all available task status.
	 *
	 * @return array
	 */
	public static function get_all_available_status() {

		return get_terms( array(
			'order' => 'DESC',
			'taxonomy' => 'kleingarten_status',
			'hide_empty' => false,
		) );

	}

}