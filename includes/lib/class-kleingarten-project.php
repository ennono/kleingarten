<?php
/**
 * Project file.
 *
 * @package Kleingarten/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Project handler class.
 */
class Kleingarten_Project {

	private $term_ID;

	/**
	 * Project constructor.
	 *
	 * @return void
	 */
	public function __construct( $term_ID ) {

		if ( $term_ID > 0 ) {
			$this->term_ID = $term_ID;
		} else {
			$this->term_ID = 0;
		}

	}

	/**
	 * Returns project color.
	 *
	 * @return string
	 */
	public function get_color() {

		return get_term_meta( $this->term_ID, 'kleingarten_project_color', true );

	}

	/**
	 * Sets project color.
	 *
	 * @param $color
	 *
	 * @return void
	 */
	public function set_color( $color ) {

		$errors = new WP_Error();

		// Check if color matches a hex color pattern:
		$regex = '/#(?:[A-Fa-f0-9]{3}){1,2}\\b/i';
		if ( ! preg_match( $regex, $color ) ) {

			// ... add an error:
			$errors->add( 'kleingarten-could-not-set-color-invalid-color' ,
				__( 'Color not valid.', 'kleingarten' ) );

		}

		// Check if term ID is valid:
		if ( $this->term_ID <= 0 ) {

			// ... add an error:
			$errors->add( 'kleingarten-could-not-set-color-invalid-term-id',
				__( 'Invalid project. Term ID not set.', 'kleingarten' ) );

		}

		// If no errors so far...
		if ( ! $errors->has_errors() ) {

			// ... try update the project color and return the result:
			return update_term_meta(
				$this->term_ID,
				'kleingarten_project_color',
				$color,
			);

		} else {
			return $errors;
		}

	}



}