<?php
/**
 * Userfields file.
 *
 * @package Kleingarten/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomy functions class.
 */
class Kleingarten_Userfields {

	/**
	 * Userfields constructor.
	 *
	 */
	public function __construct() {

		// Add fields to user profile page
		add_action( 'show_user_profile',
			array( $this, 'display_kleingarten_user_fields' ) );
		add_action( 'edit_user_profile',
			array( $this, 'display_kleingarten_user_fields' ) );
		add_action( 'user_new_form',
			array( $this, 'display_kleingarten_user_fields' ) );

		// Save user fields
		add_action( 'user_register',
			array( $this, 'save_kleingarten_user_fields' ) );
		add_action( 'profile_update',
			array( $this, 'save_kleingarten_user_fields' ) );
	}

	/**
	 * Display special Kleingarten user fields
	 *
	 * @return void
	 */
	public function display_kleingarten_user_fields( $user ) {

		global $wpdb;

		if ( is_object( $user ) ) {
			//$garden_no = esc_attr( get_the_author_meta( 'garden-no', $user->ID ) );
			$plot = get_the_author_meta( 'plot', $user->ID );
		} else {
			//$garden_no = null;
			$plot = null;
		}

		$positions           = get_the_author_meta( 'positions', $user->ID );
		$available_positions = explode( "\r\n",
			get_option( 'kleingarten_available_positions' ) );

		//$plot = get_the_author_meta( 'plot', $user->ID );
		$available_plots
			= $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s and post_status = 'publish'",
			'kleingarten_plot' ), ARRAY_A );

		$send_email_notifications
			= get_the_author_meta( 'send_email_notifications', $user->ID );

		?>

        <h3><?php echo esc_html( __( 'Garden', 'kleingarten' ) ); ?></h3>
        <table class="form-table">
            <tr>
                <th>
                    <label for="plot"><?php echo esc_html( __( 'Garden No.',
							'kleingarten' ) ); ?></label></th>
                <td>
                    <select name="plot" id="plot">
						<?php
						if ( $plot ) {
							echo '<option value="' . esc_attr( $plot ) . '">'
							     . esc_html( get_the_title( $plot ) )
							     . '</option>';
						} else {
							echo '<option value="">' . esc_html( __( 'None',
									'kleingarten' ) ) . '</option>';
						}
						foreach (
							$available_plots as $available_plot
						) {
							if ( $available_plot['ID'] != $plot ) {
								echo '<option value="'
								     . esc_attr( $available_plot['ID'] ) . '">'
								     . esc_html( $available_plot['post_title'] )
								     . '</option>';
							}
						}
						if ( $plot ) {
							echo '<option value="">' . esc_html__( 'None',
									'kleingarten' ) . '</option>';
						}
						?>
                    </select>
                    <span class="description"><?php echo esc_html( __( 'The number of the allotment plot.',
							'kleingarten' ) ); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="positions"><?php echo esc_html( __( 'Positions',
							'kleingarten' ) ); ?></label></th>
                <td>

					<?php

					$k = 0;

					// Build and display checkboxes for positions as set in settings area
					if ( count( $available_positions ) >= 1
					     && $available_positions[0] != '' ) {

						foreach ( $available_positions as $k => $v ) {
							$checked = false;
							if ( in_array( $v, (array) $positions, true ) ) {
								$checked = true;
							}
							echo '<p><label for="positions_' . esc_attr( $k )
							     . '" class="checkbox_multi"><input type="checkbox" '
							     . checked( $checked, true, false )
							     . 'name="positions[]" value="' . esc_attr( $v )
							     . '" id="positions_' . esc_attr( $k ) . '" /> '
							     . esc_html( $v ) . '</label></p>';
						}

						$k ++;

					} else {
						echo '<p><em>'
						     . esc_html__( 'There are no positions defined yet.',
								'kleingarten' ) . '</em></p>';
					}

					// Build and display checkboxes for positions that are set for this user but no longer available in settings area
					if ( is_array( $positions ) ) {
						foreach ( $positions as $position ) {

							if ( ! in_array( $position,
								$available_positions )
							) {
								echo '<p><label for="positions_'
								     . esc_attr( $k )
								     . '" class="checkbox_multi"><input type="checkbox" checked="checked"'
								     //. checked( true, true, false )
								     . 'name="positions[]" value="'
								     . esc_attr( $position )
								     . '" id="positions_'
								     . esc_attr( $k ) . '" /> '
								     . esc_html( $position . ' ('
								                 . __( 'No longer available!',
											'kleingarten' ) ) . ')</label></p>';
							}
							$k ++;
						}
					}

					?>
                    <span class="description"><?php echo esc_html( __( 'Positions in the club.',
							'kleingarten' ) ); ?></span>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="send-email-notifications"><?php echo esc_html( __( 'Notifications',
							'kleingarten' ) ); ?></label></th>
                <td>

                    <p>
                        <label for="send-email-notifications" class="checkbox">
							<?php

							if ( $send_email_notifications == 1 ) {
								?>
                                <input
                                        type="checkbox"
                                        name="send-email-notifications"
                                        value="1"
                                        id="send-email-notifications"
                                        checked
                                />
								<?php
							} else {
								?>
                                <input
                                        type="checkbox"
                                        name="send-email-notifications"
                                        value="1"
                                        id="send-email-notifications"
                                />
								<?php
							}

							echo esc_html( __( 'Send email notifications.',
								'kleingarten' ) );

							?>
                        </label>
                    </p>

                    <span class="description"><?php echo esc_html( __( 'Check to send email notifications.',
							'kleingarten' ) ); ?></span>
                </td>
            </tr>
        </table>
		<?php
	}

	/**
	 * Save userfields
	 *
	 * @param   int  $user_id  User ID
	 *
	 * @return false|void
	 */
	public function save_kleingarten_user_fields( $user_id ) {

		if ( empty( $_POST['_wpnonce'] )
		     || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ),
				'update-user_' . $user_id )
		) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( isset( $_POST['positions'] ) ) {

			$positions           = array();
			$available_positions = explode( "\r\n",
				get_option( 'kleingarten_available_positions' ) );
			$formdata
			                     = array_unique( array_map( 'sanitize_text_field',
				wp_unslash( $_POST['positions'] ) ) );
			foreach ( $formdata as $position ) {
				// Commented out because checking for unavailable positions is
				// unwanted behavior. It shall be possible to keep unavailable
				// positions.
				//if ( in_array( $position, $available_positions ) ) {
				$positions[] = $position;
				//}
			}

			// Save Positions
			update_user_meta( $user_id, 'positions', $positions );

		} else {
			// If $_POST['positions'] does not exist that means, that no position
			// was selected => Delete corresponding user meta.
			delete_user_meta( $user_id, 'positions' );
		}

		// Save Plot
		if ( isset( $_POST['plot'] ) ) {
			update_user_meta( $user_id, 'plot', absint( $_POST['plot'] ) );
		}

		if ( isset( $_POST['send-email-notifications'] )
		     && $_POST['send-email-notifications'] >= 1 ) {

			// Save Notifications
			//update_user_meta( $user_id, 'send_email_notifications',
			//    absint( $_POST['send-email-notifications'] ) );

			update_user_meta( $user_id, 'send_email_notifications', 1 );

		} else {

			update_user_meta( $user_id, 'send_email_notifications', 0 );

		}

	}

	/**
	 * Print allotment plots as options
	 *
	 * @return string HTML options
	 */
	private function print_allotment_plots_as_options() {

		global $wpdb;

		$output = '';

		$custom_post_type
			= 'kleingarten_plot'; // define your custom post type slug here

		// A sql query to return all post titles
		$results
			= $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s and post_status = 'publish'",
			$custom_post_type ), ARRAY_A );

		// Return null if we found no results
		if ( ! $results ) {
			return null;
		}

		// HTML for our select printing post titles as loop
		//$output = '<select name="project" id="project">';

		foreach ( $results as $post ) {
			$output .= '<option value="' . $post['ID'] . '">'
			           . $post['post_title'] . '</option>';
		}

		//$output .= '</select>'; // end of select element

		// get the html
		return $output;
	}

}
