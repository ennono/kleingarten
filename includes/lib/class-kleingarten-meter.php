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

					$this->readings[] = array(
						'value' => $reading_data_set['value'],
						'date' => $reading_data_set['date'],
					);

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
		$reading_validation_data = Kleingarten_Meter::reading_is_valid( $value_read );
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
		$meter_id = 0;
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
	 * Returns the number of readings.
	 *
	 * @return array
	 */
	public function count_readings() {
		return count( $this->readings );
	}

	/**
	 * Return the most recent reading.
	 *
	 * @return array
	 */
	public function get_most_recent_reading() {

		$most_recent = 0;                       // Helper for comparing
		$most_recent_reading_value = null;      // Latest value
		$most_recent_reading_date  = '';        // Latest date
		foreach ( $this->readings as $reading ) {

			$current_date = $reading['date'];
			if ( $current_date > $most_recent ) {
				$most_recent = $current_date;
				$most_recent_reading_value = $reading['value'];
				$most_recent_reading_date  = $reading['date'];
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

}