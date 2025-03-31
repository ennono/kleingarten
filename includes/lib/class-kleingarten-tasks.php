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

	/**
	 * Returns all available projects.
	 *
	 * @return array
	 */
	public static function get_all_available_projects() {

		return get_terms( array(
			'order' => 'DESC',
			'taxonomy' => 'kleingarten_project',
			'hide_empty' => false,
		) );

	}

	public static function get_tasks_with_status( $status_slug ) {

		$args = array(
			'post_type' => 'kleingarten_task',
			'tax_query' => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'kleingarten_status',
					'field' => 'slug',
					'terms' => array( $status_slug ),
				),
			)
		);

		return get_posts( $args );

	}

}