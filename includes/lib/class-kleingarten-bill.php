<?php
/**
 * A class to represent a single bill.
 *
 * @package Kleingarten/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bill handler class.
 */
class Kleingarten_Bill {

	/**
	 * User ID
	 *
	 * @var
	 */
	private $user_ID;

	/**
	 * Plots
	 *
	 * @array
	 */
	private $plots;

	public function __construct( $user_ID ) {

		$this->user_ID = $user_ID;

	}

}