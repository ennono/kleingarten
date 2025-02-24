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

    private $plots;

	/**
	 * Userfields constructor.
	 *
	 */
	public function __construct() {

        $this->plots = new Kleingarten_Plots();

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

        // Represents the gardner we are dealing with:
        $gardener = new Kleingarten_Gardener( $user->ID );

        // Get a list of all available positions:
		$available_positions = explode( "\r\n",
			get_option( 'kleingarten_available_positions' ) );

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
						if ( $gardener->plot != 0 ) {
							echo '<option value="' . esc_attr( $gardener->plot ) . '">'
							     . esc_html( get_the_title( $gardener->plot ) )
							     . '</option>';
						} else {
							echo '<option value="">' . esc_html( __( 'None',
									'kleingarten' ) ) . '</option>';
						}
                        foreach ( $this->plots->get_plot_IDs() as $plot_ID ) {
                            if ( ! $this->plots->plot_is_assigned_to_user( $plot_ID, $user->ID ) ) {
		                        echo '<option value="'
		                             . esc_attr( $plot_ID ) . '">'
		                             . esc_html( get_the_title( $plot_ID ) )
		                             . '</option>';
	                        }
                        }
						if ( $gardener->has_assigned_plot() ) {
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
							if ( in_array( $v, (array) $gardener->positions, true ) ) {
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
					if ( is_array( $gardener->positions ) ) {
						foreach ( $gardener->positions as $position ) {

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

                            if ( $gardener->receives_notification_mails() ) {
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

		// Represents the gardner we are dealing with:
		$gardener = new Kleingarten_Gardener( $user_id );

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( isset( $_POST['positions'] ) ) {
			$gardener->set_positions( $_POST['positions'] );
		} else {
			// If $_POST['positions'] does not exist that means, that no position
			// was selected => Delete corresponding user meta.
            $gardener->remove_all_positions();
		}

		// Assign or remove plot
		if ( isset( $_POST['plot'] ) ) {
            $gardener->assign_plot( $_POST['plot'] );
		}

		if ( isset( $_POST['send-email-notifications'] )
		     && $_POST['send-email-notifications'] >= 1 ) {
            $gardener->set_notification_mail_receival();
		} else {
            $gardener->unset_notification_mail_receival();
		}

	}

}
