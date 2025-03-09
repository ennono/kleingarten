<?php
/**
 * A class to represent a single meter.
 *
 * @package Kleingarten/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meter handler class.
 */
class Kleingarten_Meter {

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
	private $post;

	/**
	 * Readings
	 *
	 * @obj array
	 */
	private $readings;

	/**
	 * Meter handler constructor.
	 *
	 * @return void
	 */
	public function __construct( $post_ID ) {

		// Save at least the meter's post ID:
		$this->post_ID = $post_ID;

		// Try get the meter's post:
		$this->post = get_post( $this->post_ID );

		// If getting the post succeeded initialize more:
		if ( $this->post != null ) {

			// Get readings:
			// (Initially $readings contains all meta data. We will remove any
			// other entries.)
			$readings = has_meta( $this->post_ID, 'kleingarten_meter_reading' );
			foreach ( $readings as $j => $reading ) {

				if ( $reading['meta_key'] == 'kleingarten_meter_reading' ) {

					$reading_data_set = unserialize( $reading['meta_value'] );

					if ( isset( $reading_data_set['meter-no'] ) ) {
						$this->readings[] = array(
							'value'    => $reading_data_set['value'],
							'date'     => $reading_data_set['date'],
							'by'       => $reading_data_set['by'],
							'meter-no' => $reading_data_set['meter-no'],
							'meta_id'  => $reading['meta_id'],
						);
					} else {
						$this->readings[] = array(
							'value'   => $reading_data_set['value'],
							'date'    => $reading_data_set['date'],
							'by'      => $reading_data_set['by'],
							'meta_id' => $reading['meta_id'],
						);
					}

				}

			}

		}

	}

	/**
	 * Saves a new meter reading by token.
	 *
	 * @return bool|WP_Error
	 */
	public static function save_meter_reading_by_token(
		$token, $value_read, $date = '', $meter_no = ''
	) {

		$errors = new WP_Error();

		// If no timestamp was set assume now:
		$timestamp = 0;
		if ( $date === '' ) {
			$timestamp = strtotime( 'now' );

			// If a timestamp was set convert it:
		} else {
			$timestamp = strtotime( sanitize_text_field( $date ) );
		}

		// Validate the token and get the token's meta ID on the way.
		// Stop right here on failure.
		$token_id
			= Kleingarten_Meter::token_is_usable( $token );       // "token_is_usable" will return meta ID or WP_Error
		if ( is_wp_error( $token_id ) ) {
			$errors->merge_from( $token_id );

			return $errors;
		}

		// Validate the reading.
		// Stop right here on failure.
		$reading_validation_data
			= Kleingarten_Meter::reading_is_valid( $value_read );
		if ( is_wp_error( $reading_validation_data ) ) {
			$errors->merge_from( $reading_validation_data );

			return $errors;
		}

		// Validate the reading date.
		// Stop right here on failure.
		$reading_date_validation_data
			= Kleingarten_Meter::reading_date_is_valid( $timestamp, $token_id );
		if ( is_wp_error( $reading_date_validation_data ) ) {
			$errors->merge_from( $reading_date_validation_data );

			return $errors;
		}

		// Get the associated meter's post ID from the token ID or stop here in failure:
		$meter_id        = 0;
		$token_meta_data = get_metadata_by_mid( 'post', $token_id );
		if ( is_object( $token_meta_data ) ) {
			$meter_id = $token_meta_data->post_id;
		}
		if ( $meter_id == 0 || $meter_id == '' || $meter_id == null ) {
			$errors->add( 'kleingarten-save-meter-no-meter-id',
				__( 'Could not find meter', 'kleingarten' ) );

			return $errors;
		}

		// Sanitize new meter reading and save it:
		$sanitized_reading['date']     = absint( $timestamp );
		$sanitized_reading['value']    = absint( $value_read );
		$sanitized_reading['by']       = 'token_' . absint( $token );
		$sanitized_reading['meter-no'] = sanitize_text_field( $meter_no );
		$save_reading_result           = add_post_meta( $meter_id,
			'kleingarten_meter_reading', $sanitized_reading );

		// Void token if reading was saved successfully:
		if ( $save_reading_result ) {

			$token_meta_data->meta_value['token_status'] = 'used';
			update_metadata_by_mid( 'post', $token_id,
				$token_meta_data->meta_value );

		}

		if ( $errors->has_errors() ) {
			return $errors;
		} else {
			return true;
		}

	}

	/**
	 * Returns the given token's meta ID if it is usable and an WP_Error object if it is not.
	 *
	 * @return object|integer
	 */
	private static function token_is_usable( $token ) {

		$errors = new WP_Error();

		$token_data = array();

		// Read all the tokens from the database:
		global $wpdb;
		$available_tokens_meta_ids
			= $wpdb->get_col( "SELECT meta_id FROM $wpdb->postmeta WHERE meta_key = 'kleingarten_meter_reading_submission_token'" );

		// If we found tokens in the database...
		if ( is_array( $available_tokens_meta_ids )
		     && $available_tokens_meta_ids ) {

			// ... find those that match the token we are supposed to check:
			foreach ( $available_tokens_meta_ids as $meta_id ) {

				$temp_token_data = get_metadata_by_mid( 'post', $meta_id );

				if ( $temp_token_data->meta_value['token'] == $token ) {
					$token_data[]
						= $temp_token_data;   // We build an array here to enable us to check for duplicates later.
				}

			}

		}

		// If the token data we finally filtered from database is not an array stop right here. Something went wrong.
		if ( ! is_array( $token_data ) ) {
			$errors->add( 'kleingarten-submit-meter-reading-not-an-array',
				__( 'Something is wrong with your token.', 'kleingarten' ) );
		}

		// TOKEN CHECK: Check if token is unique. Return false in case it is not:
		if ( is_array( $token_data ) && count( $token_data ) > 1 ) {
			$errors->add( 'kleingarten-submit-meter-reading-token-not-unique',
				__( 'Something is wrong with your token.', 'kleingarten' ) );
		}

		// TOKEN CHECK: Check if found token matches the token we are suppose to check. Again. Just to be sure.
		if ( ! isset( $token_data[0]->meta_value['token'] )
		     || $token_data[0]->meta_value['token'] != $token ) {
			$errors->add( 'kleingarten-submit-meter-reading-invalid-token',
				__( 'Invalid token.', 'kleingarten' ) );
		}

		// TOKEN CHECK: Check if token has a usable status. If it has any other status than active then stop here.
		if ( isset( $token_data[0]->meta_value['token_status'] )
		     && $token_data[0]->meta_value['token_status'] != 'active' ) {
			$errors->add( 'kleingarten-submit-meter-reading-token-no-usable-status',
				__( 'Token not usable.', 'kleingarten' ) );
		}

		// TOKEN CHECK: Check if token is expired.
		if ( ! isset( $token_data[0]->meta_value['token_expiry_date'] )
		     || $token_data[0]->meta_value['token_expiry_date']
		        <= strtotime( 'now' ) ) {
			$errors->add( 'kleingarten-submit-meter-reading-token-no-usable-status',
				__( 'Token expired.', 'kleingarten' ) );
		}

		if ( $errors->has_errors() ) {
			return $errors;
		} else {
			return $token_data[0]->meta_id;
		}

	}

	/**
	 * Returns true if a reading is valid and an WP_Error object if it is not.
	 *
	 * @return object|bool
	 */
	private static function reading_is_valid( $reading ) {

		$errors = new WP_Error();

		// READING CHECK: Check if it is a number.
		if ( ! is_int( $reading ) ) {
			$errors->add( 'kleingarten-submit-meter-reading-not-an-integer',
				__( 'Reading is not a number.', 'kleingarten' ) );
		}

		// READING CHECK: Check if it is empty.
		if ( $reading == null || $reading == '' ) {
			$errors->add( 'kleingarten-submit-meter-reading-not-an-integer',
				__( 'Reading is empty.', 'kleingarten' ) );
		}

		if ( $errors->has_errors() ) {
			return $errors;
		} else {
			return true;
		}

	}

	/**
	 * Returns true if a reading date is valid and an WP_Error object if it is not.
	 *
	 * @return object|bool
	 */
	private static function reading_date_is_valid( $timestamp, $token_id = 0 ) {

		$errors = new WP_Error();

		// READING DATE CHECK: Check if date is future
		if ( $timestamp > time() ) {
			$errors->add( 'kleingarten-submit-meter-reading-date-in-future',
				__( 'Date cannot be in the future.', 'kleingarten' ) );
		}

		// READING DATE CHECK: Check for existing readings on this date
		if ( $token_id == 0 ) {

			$errors->add( 'kleingarten-submit-meter-reading-cannot-check-fot-existing-readings',
				__( 'Checking for existing readings on this date failed due to missing token.',
					'kleingarten' ) );

		} else {

			$meter_id        = 0;
			$token_meta_data = get_metadata_by_mid( 'post', $token_id );
			if ( is_object( $token_meta_data ) ) {
				$meter_id = $token_meta_data->post_id;
			}

			$existing_readings = get_post_meta( $meter_id,
				'kleingarten_meter_reading' );
			if ( $existing_readings ) {

				// Check if we already have a reading for this date:
				foreach ( $existing_readings as $existing_reading ) {
					if ( $existing_reading['date'] === $timestamp ) {

						$errors->add( 'kleingarten-submit-meter-reading-found-existing-reading-on-date',
							__( 'There already is a meter reading for this date.',
								'kleingarten' ) );

						break;

					}
				}
			}

		}

		if ( $errors->has_errors() ) {
			return $errors;
		} else {
			return true;
		}

	}

	/**
	 * Remove meter assignments. To be used to clean up assignments when a meter is deleted.
	 *
	 * @return void
	 * @since 1.1.0
	 */
	public static function purge_meter( $deleted_meter_ID ) {

		// List all plots which the deleted meter is assigned to:
		$args  = array(
			'post_type'      => 'kleingarten_plot',
			'meta_key'       => 'kleingarten_meter_assignment',
			'meta_value'     => $deleted_meter_ID,
			'posts_per_page' => - 1,
		);
		$plots = get_posts( $args );

		// If we found post which the deleted meter is assigned to...
		if ( $plots ) {

			// ... delete them all:
			foreach ( (array) $plots as $plot ) {
				delete_post_meta( $plot->ID, 'kleingarten_meter_assignment',
					$deleted_meter_ID );
			}

		}

	}

	/**
	 * Returns the number of readings.
	 *
	 * @return int
	 */
	public function count_readings() {
		return count( $this->readings );
	}

	/**
	 * Returns a list of readings.
	 *
	 * @return array
	 */
	public function get_readings( $sort_by_date = true ) {

		if ( $sort_by_date ) {

			if ( is_array( $this->readings ) ) {
				uasort( $this->readings, function ( $x, $y ) {
					if ( $x['date'] == $y['date'] ) {
						return 0;
					} else if ( $x['date'] > $y['date'] ) {
						return - 1;
					} else {
						return 1;
					}
				} );
			}


		}

		return $this->readings;

	}

	/**
	 * Returns the most recent reading and its date.
	 *
	 * @return array
	 */
	public function get_most_recent_reading() {

		$most_recent               = 0;                       // Helper for comparing
		$most_recent_reading_value = null;      // Latest value
		$most_recent_reading_date  = '';        // Latest date
		if ( is_array( $this->readings ) ) {
			foreach ( $this->readings as $reading ) {

				$current_date = $reading['date'];
				if ( $current_date > $most_recent ) {
					$most_recent               = $current_date;
					$most_recent_reading_value = $reading['value'];
					$most_recent_reading_date  = $reading['date'];
				}

			}
		}


		return array(
			'reading' => $most_recent_reading_value,
			'date'    => $most_recent_reading_date,
		);

	}

	/**
	 * Return the meter's title.
	 *
	 * @return string
	 */
	public function get_title() {
		return get_the_title( $this->post_ID );
	}

	/**
	 * Return the meter's title.
	 *
	 * @return string
	 */
	public function get_unit() {
		return get_post_meta( $this->post_ID, 'kleingarten_meter_unit', true );
	}

	/**
	 * Return true if the given user if allowed to add new meter readings
	 * and false if not.
	 *
	 * @return string
	 */
	public function may_be_updated_by_user( $user_id ) {

		$gardener = new Kleingarten_Gardener( $user_id );
		$plot     = new Kleingarten_Plot( $gardener->plot );

		$assigned_meters = $plot->get_assigned_meters();

		if ( in_array( $this->post_ID, $assigned_meters ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Adds a new meter reading.
	 *
	 * @return string
	 */
	public function add_reading( $value, $date, $by = null, $meter_no = '' ) {

		$errors = new WP_Error();

		//$sanitized_data['date'] = strtotime( sanitize_text_field( wp_unslash( $date ) ) );
		$sanitized_data['date']  = absint( wp_unslash( $date ) );
		$sanitized_data['value'] = absint( wp_unslash( $value ) );
		if ( $by != null ) {
			$sanitized_data['by'] = absint( wp_unslash( $by ) );
		}
		$sanitized_data['meter-no']
			= sanitize_text_field( wp_unslash( $meter_no ) );

		// Validate data:
		if ( $this->readings ) {

			// Check if we already have a reading for this date:
			foreach ( $this->readings as $existing_reading ) {
				if ( $existing_reading['date'] === $sanitized_data['date'] ) {
					$errors->add( 'kleingarten_meter_reading_not_unique',
						__( 'A meter reading already exists for this date.',
							'kleingarten' ), $this->post_ID );
					break;
				}
			}

			// Determine if date is in the future:
			$reading_date = $sanitized_data['date'];
			$today        = strtotime( gmdate( 'Y-m-d' ) );
			if ( $reading_date > $today ) {
				$errors->add( 'kleingarten_meter_reading_date_in_future',
					__( 'Cannot save a reading for a date in the future.',
						'kleingarten' ), $this->post_ID );
			}

		}

		$meta_id = add_post_meta( $this->post_ID, 'kleingarten_meter_reading',
			$sanitized_data );
		if ( ! $meta_id ) {
			$errors->add( 'kleingarten_meter_reading_could_not_create',
				__( 'Could not create new reading.',
					'kleingarten' ), $this->post_ID );
		}

		if ( ! $errors->has_errors() && $meta_id != false ) {
			return $meta_id;
		} else {
			return $errors;
		}

	}

	/**
	 * Removes a meter reading by meta_id.
	 *
	 * @param $meta_id
	 *
	 * @return true|WP_Error
	 */
	public function remove_reading( $meta_id ) {

		if ( ! delete_metadata_by_mid( 'post',
			absint( wp_unslash( $meta_id ) ) ) ) {
			$errors = new WP_Error();
			$errors->add( 'kleingarten_meter_reading_could_not_delete',
				__( 'Something went wrong. Could could not remove reading.',
					'kleingarten' ), $meta_id );

			return $errors;
		}

		return true;

	}

	/**
	 * Returns true if a meters is assign to a plot and false if not.
	 *
	 * @param $meter_ID
	 *
	 * @return bool
	 */
	public function meter_is_assigned() {

		// Get assigned meter:
		$assignments = $this->get_meter_assignments();

		// If there are multiple meters assigned...
		if ( is_array( $assignments ) && $assignments ) {
			return true;
		}

		// If there is a single meter assigned...
		if ( ! is_array( $assignments ) && $assignments != null
		     && $assignments != '' ) {
			return true;
		}

		// If nothing is assigned...
		return false;

	}

	/**
	 * Returns a list of plots a meter is assigned to.
	 *
	 * @param $meter_ID
	 *
	 * @return array
	 */
	public function get_meter_assignments() {

		// List all plots which the given meter is assigned to:
		$args                      = array(
			'post_type'      => 'kleingarten_plot',
			'meta_key'       => 'kleingarten_meter_assignment',
			'meta_value'     => strval( $this->post_ID ),
			'posts_per_page' => - 1,
		);
		$plots_with_meter_assigned = get_posts( $args );

		if ( is_array( $plots_with_meter_assigned ) ) {
			$plot_IDs = array();
			foreach ( $plots_with_meter_assigned as $plot ) {
				$plot_IDs[] = $plot->ID;
			}

			return $plot_IDs;
		} else {
			$plot_IDs   = array();
			$plot_IDs[] = $plots_with_meter_assigned->ID;

			return $plot_IDs;
		}

	}

	/**
	 * Returns a list of tokens.
	 *
	 * @return array
	 */
	public function get_tokens() {

		// Build a list of existing tokens:
		$existing_tokens = has_meta( $this->post_ID );
		foreach ( $existing_tokens as $j => $existing_token ) {

			if ( $existing_token['meta_key']
			     != 'kleingarten_meter_reading_submission_token' ) {
				unset( $existing_tokens[ $j ] );
			} else {
				$existing_token_data
					= unserialize( $existing_token['meta_value'] );      // Date, value and author are saved as serialized string. So we have to unserialize it first.
				$existing_tokens[ $j ]['token_data']['token']
					= $existing_token_data['token'];
				$existing_tokens[ $j ]['token_data']['token_status']
					= $existing_token_data['token_status'];
				$existing_tokens[ $j ]['token_data']['token_expiry_date']
					= $existing_token_data['token_expiry_date'];
			}

		}

		return $existing_tokens;

	}

	/**
	 * Returns a details of a single token.
	 *
	 * @return array
	 */
	public function get_token_details( $meta_id ) {

		$meta_data = get_metadata_by_mid( 'post', $meta_id );

		$token_details = array(
			'token'             => $meta_data->meta_value['token'],
			'token_status'      => $meta_data->meta_value['token_status'],
			'token_expiry_date' => $meta_data->meta_value['token_expiry_date'],
		);

		return $token_details;

	}

	/**
	 * Assigns the meter to a plot.
	 *
	 * @param $plot_id
	 *
	 * @return WP_Error|true
	 */
	public function assign_to_plot( $plot_id ) {

		$meta_id = add_post_meta( $plot_id, 'kleingarten_meter_assignment',
			$this->post_ID );

		if ( ! $meta_id ) {
			$errors = new WP_Error();
			$errors->add( 'kleingarten_meter_assignment_could_not_create',
				__( 'Could not assign meter to plot.', 'kleingarten' ),
				$plot_id );

			return $errors;
		}

		return true;

	}

	/**
	 * Un-assigns the meter from a plot.
	 *
	 * @param $plot_id
	 *
	 * @return WP_Error|true
	 */
	public function unassign_from_plot( $plot_id ) {

		$meta_id = delete_post_meta( $plot_id, 'kleingarten_meter_assignment',
			$this->post_ID );

		if ( ! $meta_id ) {
			$errors = new WP_Error();
			$errors->add( 'kleingarten_meter_assignment_could_not_create',
				__( 'Could unassign meter from plot.', 'kleingarten' ),
				$plot_id );

			return $errors;
		}

		return true;

	}

	/**
	 * Adds a new meter reading submission token.
	 *
	 * @return int
	 */
	public function create_token( $token_status = 'active' ): bool|int {

		$days_to_add_from_today
			                   = get_option( 'kleingarten_meter_reading_submission_token_time_to_live',
			10 );
		$wp_date_format        = get_option( 'date_format' );
		$today                 = gmdate( $wp_date_format );
		$expiry_date_formated  = gmdate( $wp_date_format,
			strtotime( $today . ' + ' . $days_to_add_from_today . ' days' ) );
		$expiry_date_timestamp = strtotime( $expiry_date_formated );

		$token_data_set_to_save                      = array();
		$token_data_set_to_save['token']             = $this->calc_token();
		$token_data_set_to_save['token_status']      = $token_status;
		$token_data_set_to_save['token_expiry_date'] = $expiry_date_timestamp;

		$meta_id = add_post_meta( absint( wp_unslash( $this->post_ID ) ),
			'kleingarten_meter_reading_submission_token',
			$token_data_set_to_save );

		if ( ! $meta_id ) {
			$errors = WP_Errors();
			$errors->add( 'kleingarten_meter_assignment_could_not_create_meter_reading_submission_token',
				__( 'Could not create new token.', 'kleingarten' ) );

			return $errors;
		}

		return $meta_id;

	}

	/**
	 * Returns a token to be saved as meter reading submission token.
	 *
	 * @return int
	 */
	private function calc_token() {
		$token = random_int( 100000, 999999 );

		return $token;
	}

	/**
	 * Deactivate a meter reading submission token.
	 *
	 * @param $plot_ID
	 *
	 * @return bool|WP_Error
	 * @since 1.1.0
	 */
	public function deactivate_token( $token_id ) {

		$errors = new WP_Error();

		$token_id = absint( $token_id );

		// Try to find the token that shall be deactivated:
		$meta = get_post_meta_by_id( $token_id );
		if ( $meta === false ) {
			$errors->add( 'kleingarten_meter_assignment_could_not_find_meter_reading_submission_token',
				__( 'Could not find token to deactivate. Please refresh the page and try again.',
					'kleingarten' ) );

			return $errors;
		}

		if ( $meta->post_id != $this->post_ID ) {
			$errors->add( 'kleingarten_meter_assignment_meter_reading_submission_token_does_not_belong_to_this_meter',
				__( 'You have no permission to deactivate this token.',
					'kleingarten' ) );

			return $errors;
		}

		// If we found the token and if it actually is a token:
		if ( $meta->meta_key == 'kleingarten_meter_reading_submission_token' ) {

			$meta_value = maybe_unserialize( $meta->meta_value );

			$meta_value['token_status'] = 'deactivated';

			if ( ! update_metadata_by_mid( 'post', $token_id, $meta_value ) ) {
				$errors->add( 'kleingarten_meter_assignment_could_not_update_meter_reading_submission_token',
					__( 'Could not deactivate token.', 'kleingarten' ) );

				return $errors;
			}

		}

		return true;

	}

	/**
	 * Deletes an existing token.
	 *
	 * @param $token_id
	 *
	 * @return void|WP_Error
	 */
	public function delete_token( $token_id ) {

		$errors = new WP_Error();

		$token_id = absint( $token_id );

		// Try to find the token that shall be deactivated:
		$meta = get_post_meta_by_id( $token_id );
		if ( $meta === false ) {
			$errors->add( 'kleingarten_meter_assignment_could_not_find_meter_reading_submission_token',
				__( 'Could not find token to deactivate. Please refresh the page and try again.',
					'kleingarten' ) );

			return $errors;
		}

		if ( $meta->post_id != $this->post_ID ) {
			$errors->add( 'kleingarten_meter_assignment_meter_reading_submission_token_does_not_belong_to_this_meter',
				__( 'You have no permission to deactivate this token.',
					'kleingarten' ) );

			return $errors;
		}

		$meta_value = maybe_unserialize( $meta->meta_value );
		if ( $meta_value['token_status'] != 'deactivated' ) {
			$errors->add( 'kleingarten_meter_assignment_meter_reading_submission_token_not_deactivated',
				__( 'Token must be deactivated first.', 'kleingarten' ) );

			return $errors;
		}

		if ( ! delete_metadata_by_mid( 'post', $token_id ) ) {
			$errors->add( 'kleingarten_meter_assignment_could_not_delete_meter_reading_submission_token',
				__( 'Could not delete token.', 'kleingarten' ) );

			return $errors;
		}

	}

}