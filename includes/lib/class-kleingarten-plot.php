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
	//private $post;

	/**
	 * Plot handler constructor.
	 *
	 * @return void
	 */
	public function __construct( $plot_ID ) {

		// Save at least the plot's post ID:
		$this->post_ID = $plot_ID;

	}

	/**
	 * Remove gardener assignment. To used with hook that fires on deleting
	 * or trashing a plot.
	 *
	 * @return true|false
	 */
	public static function remove_gardener_assignments( $post_ID ) {

		// Remove user assignments:
		$users_with_plot_assigned
			= Kleingarten_Gardeners::get_users_with_plot_assigned( $post_ID );
		foreach ( $users_with_plot_assigned as $user ) {
			$gardener = new Kleingarten_Gardener( $user->ID );
			if ( $gardener ) {
				$gardener->reset_plot();
			}
		}

		return true;

	}

	/**
	 * Creates a new plot and returns is as an instance of Kleingarten_Plot.
	 *
	 * @param $title
	 * @param $author_id
	 *
	 * @return WP_Error|self
	 */
	public static function create_new( $title, $author_id = 0 ) {

		if ( $author_id == 0 ) {
			$author_id = get_current_user_id();
		}

		// Prepare plot:
		$postarr = array(
			'post_type'   => 'kleingarten_plot',
			'post_title'  => sanitize_text_field( $title ),
			'post_status' => 'publish',
			'post_author' => absint( $author_id ),
		);

		// Create plot or add error message on failure:
		$new_plot_id = wp_insert_post( $postarr );
		if ( is_wp_error( $new_plot_id ) || $new_plot_id === 0 ) {
			return new WP_Error();
		}

		return new self( $new_plot_id );

	}

	/**
	 * Returns the post ID.
	 *
	 * @return int
	 */
	public function get_ID() {
		return $this->post_ID;
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
		if ( ! is_int( $this->post_ID ) || $this->post_ID <= 0 ) {
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
				if ( $single_post_meta['meta_key']
				     == 'kleingarten_meter_assignment' ) {
					if ( ! $return_meta_IDs ) {
						if ( ! in_array( $single_post_meta['meta_value'], $assigned_meters ) ) {
							$assigned_meters[] = $single_post_meta['meta_value'];
						}
					} else {
						if ( ! in_array( $single_post_meta['meta_value'], $assigned_meters ) ) {
							$assigned_meters[] = $single_post_meta['meta_value'];
						}
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
