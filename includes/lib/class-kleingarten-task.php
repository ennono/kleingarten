<?php
/**
 * Task file.
 *
 * @package Kleingarten/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomy functions class.
 */
class Kleingarten_Task {

	private $post_ID;

	/**
	 * Task constructor.
	 *
	 * @return void
	 */
	public function __construct( $post_ID ) {

		if ( $post_ID > 0 ) {
			$this->post_ID = $post_ID;
		} else {
			$this->post_ID = 0;
		}

	}

	/**
	 * Creates a new task.
	 *
	 * @return void
	 */
	public static function create_new() {

	}

	/**
	 * Sets the task status.
	 *
	 * @return void
	 */
	public function set_status( $status ) {

		$all_available_status = Kleingarten_Tasks::get_all_available_status();
		$all_available_status_slugs = array();
		foreach ( $all_available_status as $available_status ) {
			$all_available_status_slugs[] = $available_status->slug;
		}

		if ( in_array( $status, $all_available_status_slugs ) ) {
			return wp_set_post_terms( $this->post_ID, $status, 'kleingarten_status' );
		}

		return false;

	}

	/**
	 * Returns the task status slug.
	 *
	 * @return void
	 */
	public function get_status() {

		$all_available_status = Kleingarten_Tasks::get_all_available_status();
		$all_available_status_slugs = array();
		foreach ( $all_available_status as $status ) {
			$all_available_status_slugs[] = $status->slug;
		}

		$args = array(
			//'slug'      => array( 'todo', 'next', 'done'),
			'slug'  => $all_available_status_slugs,
		);
		$terms = wp_get_post_terms( $this->post_ID, 'kleingarten_status', $args );

		if ( ! is_wp_error( $terms ) && ! $terms == false ) {
			return $terms[0];
		}

	}



}