<?php
/**
 * Post meta file.
 *
 * @package Kleingarten/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post meta functions class.
 */
class Kleingarten_Post_Meta {

	/**
	 * Userfields constructor.
	 *
	 * @return void
	 */
	public function __construct() {

		add_action( 'add_meta_boxes', array( $this, 'add_likes_meta_box' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meter_readings_meta_box' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meter_unit_meta_box' ) );      // OLD STUFF
		add_action( 'add_meta_boxes', array( $this, 'add_meter_type_meta_box' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_plot_assignment_meta_box' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meter_assignment_meta_box' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meter_reading_submission_token_meta_box' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_task_status_meta_box' ) );

        add_action( 'save_post', array( $this, 'save_likes_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_meter_unit_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_meter_readings_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_meter_assignment_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_meter_reading_submission_token_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_task_status_meta_box' ) );

        add_action( 'admin_notices', array( $this, 'print_admin_notices' ) );

		add_action( 'wp_ajax_kleingarten_add_meter_reading_submission_token', array( $this, 'kleingarten_add_meter_reading_submission_token_ajax_callback' ) );

	}

	/**
	 * Add likes meta box to posts.
	 *
	 * @return void
	 */
	public function add_likes_meta_box() {

		$post_type = 'post';

		add_meta_box( 'kleingarten_likes_meta_box', __( 'Likes', 'kleingarten' ),
			array( $this, 'render_likes_meta_box_content' ), $post_type,
			'side' );

	}

    /**
	 * Add meter readings meta box to meters.
	 *
	 * @return void
	 * @since 1.1.0
	 */
	public function add_meter_readings_meta_box() {

		$post_type = 'kleingarten_meter';

		add_meta_box( 'kleingarten_meter_readings_meta_box', __( 'Meter Readings', 'kleingarten' ),
			array( $this, 'render_meter_readings_meta_box_content' ), $post_type );

	}

    /**
	 * Add plot assignment meta box to meters.
	 *
	 * @return void
	 * @since 1.1.0
	 */
	public function add_plot_assignment_meta_box() {

		$post_type = 'kleingarten_meter';

		add_meta_box( 'kleingarten_plot_assignment_meta_box', __( 'Plot Assignment', 'kleingarten' ),
			array( $this, 'render_plot_assignment_meta_box_content' ), $post_type,
			'side');

	}

    /**
	 * Add meter assignment meta box to plots.
	 *
	 * @return void
	 * @since 1.1.0
	 */
	public function add_meter_assignment_meta_box() {

		$post_type = 'kleingarten_plot';

		add_meta_box( 'kleingarten_meter_assignment_meta_box', __( 'Meter Assignment', 'kleingarten' ),
			array( $this, 'render_meter_assignment_meta_box_content' ), $post_type );

	}

    /**
	 * OLD STUFF: Add meter unit/type meta box to meters.
	 *
	 * @return void
	 * @since 1.1.0
	 */
	public function add_meter_unit_meta_box() {

		$post_type = 'kleingarten_meter';

		add_meta_box( 'kleingarten_meter_unit_meta_box', __( 'Meter Unit', 'kleingarten' ),
			array( $this, 'render_meter_unit_meta_box_content' ), $post_type,
			'side');

	}

    /**
	 * Add meter type meta box to meters.
	 *
	 * @return void
	 * @since 1.1.0
	 */
	public function add_meter_type_meta_box() {

		$post_type = 'kleingarten_meter';

		add_meta_box( 'kleingarten_meter_type_meta_box', __( 'Meter Type', 'kleingarten' ),
			array( $this, 'render_meter_type_meta_box_content' ), $post_type,
			'side');

	}

    /**
	 * Add meter reading submission token meta box to meters.
	 *
	 * @return void
	 * @since 1.1.0
	 */
	public function add_meter_reading_submission_token_meta_box() {

		$post_type = 'kleingarten_meter';

		add_meta_box( 'kleingarten_meter_reading_submission_token_meta_box', __( 'Meter Reading Submission Tokens', 'kleingarten' ),
			array( $this, 'render_meter_reading_submission_token_meta_box_content' ), $post_type );

	}

    /**
	 * Adds task status meta box to meters.
	 *
	 * @return void
	 * @since 1.1.0
	 */
    public function add_task_status_meta_box() {

		$post_type = 'kleingarten_task';

		add_meta_box( 'kleingarten_task_status_meta_box', __( 'Status', 'kleingarten' ),
			array( $this, 'render_task_status_meta_box_content' ), $post_type,
			'side' );

    }


    /**
    * Renders the task status meta box.
    *
    * @param $post
    *
    * @return void
    */
    public function render_task_status_meta_box_content( $post ) {

        $task = new Kleingarten_Task( $post->ID );
        $current_status = $task->get_status();

        $all_available_status = Kleingarten_Tasks::get_all_available_status();

        wp_nonce_field( 'kleingarten_save_task_status_nonce_action',
				'kleingarten_save_task_status_nonce' );

        echo '<select name="kleingarten_task_status">';

        if ( in_array( $current_status, $all_available_status ) ) {
            echo '<option value="' . esc_attr( $current_status->slug ) . '">' . esc_html( $current_status->name ) . '</option>';
        }

        foreach ( $all_available_status as $status ) {

            if ( $current_status != $status ) {
                echo '<option value="' . esc_attr(  $status->slug ) . '">' . esc_html( $status->name ) . '</option>';
            }
        }

        echo '</select>';

    }

	/**
	* Build like meta box content.
	*
    * @param $post
    *
    * @return true
    */
	public function render_likes_meta_box_content( $post ) {

		// Get existing likes
		$seperator = ',';
		$raw_likes = get_post_meta( $post->ID, 'kleingarten_likes', true );
		$raw_likes = rtrim( $raw_likes, $seperator );
		$likes     = explode( $seperator, $raw_likes );
		$users     = get_users( array(
                                        'fields' => array( 'ID' ),
                                        'role__not_in' => array( 'kleingarten_pending' ),
                                    )
                                );

		foreach ( $likes as $i => $like ) {
			if ( ! strlen( $like ) ) {
				unset( $likes[ $i ] );
			}
		}

		?><div class="custom-field-panel"><?php

		foreach ( $users as $user ) {
            $gardener = new Kleingarten_Gardener( $user->ID );
			//$user_meta = get_user_meta( $user->ID );
			$checked   = '';
			if ( in_array( $user->ID, $likes ) ) {
				$checked = 'checked';
			}

			?>

            <input name="kleingarten_likeed_by[]"
                   id="kleingarten_likeed_by_<?php echo esc_attr( $user->ID ); ?>"
                   type="checkbox" <?php echo esc_attr( $checked ); ?>
                   value="<?php echo esc_attr( $user->ID ); ?>">
            <label for="kleingarten_likeed_by_<?php echo esc_attr( $user->ID ); ?>">
				<?php

				echo esc_html( $gardener->first_name );
				echo ' ';
				echo esc_html( $gardener->last_name );

                if ( isset( $gardener->plot ) && $gardener->plot != 0 ) {
                    $plot = new Kleingarten_Plot( $gardener->plot );
					echo ' (';
					echo esc_html( $plot->get_title() );
					echo ')';
				}

				?>
            </label>
            <br>
			<?php

		}

        wp_nonce_field( 'save_kleingarten_likes', 'kleingarten_likes_nonce' );

		echo '</div>' . "\n";

        return true;
	}

 	/**
	* Build meter readings meta box content.
	*
    * @param $post
    *
    * @return true Altered Post
    * @since 1.1.0
    */
	public function render_meter_readings_meta_box_content( $post ) {

        // Build an array of all the readings we already have saved for this meter...
        $meter = new Kleingarten_Meter( $post->ID );
        $known_readings = $meter->get_readings();

        ?><div class="custom-field-panel"><?php

        // If a unit was defined for this meter / If this meter as been saved once and therefore has a defined unit/type...
        //$current_unit = get_post_meta( $post->ID, 'kleingarten_meter_unit', true );
        $current_unit = $meter->get_unit();
        if ( isset( $current_unit ) && $current_unit != '' ) {

           // ... build a form to enter a new reading...
           ?><table class="kleingarten-admin-meter-readings">
                <thead>
                    <tr>
                       <th><?php esc_html_e( 'Date', 'kleingarten' ); ?></th>
                       <th><?php esc_html_e( 'Value read', 'kleingarten' ); ?></th>
                       <th><?php esc_html_e( 'Meter No.', 'kleingarten' ); ?></th>
                       <th><?php esc_html_e( 'Submitted by', 'kleingarten' ); ?></th>
                       <th><?php esc_html_e( 'Actions', 'kleingarten' ); ?></th>
                    </tr>
                    <tr>
                        <td><input name="new_kleingarten_meter_reading[date]" type="date" value="<?php echo esc_attr( gmdate("Y-m-d") ); ?>"></td>
                        <td><input name="new_kleingarten_meter_reading[value]" type="number" min="0"> <?php echo esc_html( $current_unit ); ?></td>
                        <td><input name="new_kleingarten_meter_reading[meter-no]" type="text"></td>
                        <td><?php
                            $gardener = new Kleingarten_Gardener( get_current_user_id() );
                            echo esc_html( $gardener->disply_name ); ?><input type="hidden" name="new_kleingarten_meter_reading[by]" value="<?php echo esc_attr( $gardener->get_user_id() );
                        ?>"></td>
                        <td></td>
                    </tr>
                </thead>
                <tbody>
            <?php

            // ... and then list all present readings for this meter...
            $wp_date_format = get_option('date_format');    // Get WordPress date format from settings.
            if ( is_array( $known_readings ) ) {
                foreach ( $known_readings as $i => $reading ) {

                ?><tr><?php

                    echo '<td>' . esc_html( wp_date( $wp_date_format, intval( $reading['date'] ) ) ) . '</td>';

                    echo '<td>' . esc_html( number_format( $reading['value'], 0, ',', '.') ) . ' ' . esc_html( $current_unit ) . '</td>';
                    echo '<td>' . esc_html( $reading['meter-no'] ) . '</td>';

                    if ( isset( $reading['by'] ) && str_contains( $reading['by'], 'token_' ) ) {
                        echo '<td>' . esc_html( __( 'Token', 'kleingarten' ) ) . ':<br>' . esc_html( substr( $reading['by'], 6 ) ) . '</td>';
                    } elseif ( isset( $reading['by'] ) && str_contains( $reading['by'], 'csv_import_' ) ) {
                        $gardener = new Kleingarten_Gardener( intval( substr( $reading['by'], 11 ) ) );
                        echo '<td>' . esc_html( __( 'CSV Import', 'kleingarten' ) ) . ':<br>' . esc_html( $gardener->disply_name ) . '</td>';
                    } elseif ( isset( $reading['by'] ) ) {
                        $gardener = new Kleingarten_Gardener( $reading['by'] );
                        echo '<td>' . esc_html( __( 'User', 'kleingarten' ) ) . ':<br>' . esc_html( $gardener->disply_name ) . '</td>';
                    } else {
                        echo '<td>'.esc_html__( 'Unknown', 'kleingarten' ).'</td>';
                    }

                    echo '<td><label for="delete_kleingarten_meter_reading_' . esc_attr( $reading['meta_id'] ) . '"><input id="delete_kleingarten_meter_reading_' . esc_attr( $reading['meta_id'] ) . '" name="delete_kleingarten_meter_readings[]" type="checkbox" value="' . esc_attr( $reading['meta_id'] ) . '">' . esc_html( __( 'Delete', 'kleingarten' ) ) . '</label></td>';

                ?></tr>

                <?php

            }
            }


            ?>
            </tbody>
            </table><?php

        // ... or if this meter has not been saved once yet and therefore has not defined unit/type yet...
        } else {
            echo '<p><em>' . esc_html__( 'Please select a unit and save the meter to add meter readings.', 'kleingarten' ) . '</em></p>';
        }

        wp_nonce_field( 'save_kleingarten_meter_readings', 'kleingarten_meter_readings_nonce' );

		?></div><?php

        return true;
	}

    /**
	* Build plot assignments meta box content.
    * Will be displayed on meter post type. 
	*
    * @param $post
    *
    * @return true
    * @since 1.1.0
    */
    public function render_plot_assignment_meta_box_content( $meter ) {

        $meter_ID = $meter->ID;

        $meter = new Kleingarten_Meter( $meter_ID );
        $plots = new Kleingarten_Plots( $meter_ID );

		?><div class="custom-field-panel"><?php

        if ($meter->meter_is_assigned() ) {

            echo '<p>' . esc_html__( 'Meter is assigned to', 'kleingarten' ) . '&nbsp;';
            $assignment_plots = $plots->get_plot_IDs();
            $assignments_num = count ( $assignment_plots );
            foreach ( $assignment_plots as $n => $assignment_plot ) {
                $plot = new Kleingarten_Plot( $assignment_plot );
                echo '<a href="' . esc_url( get_edit_post_link( $assignment_plot ) ) . '">';
                echo esc_html( $plot->get_title() );
                echo '</a>';
                if ( $assignments_num - 1 - $n > 0 ) {
                    echo ', ';
                }
            }
            echo '.</p>';

        } else {
            echo '<p><em>' . esc_html( __( 'Meter is currently not assigned to a plot.', 'kleingarten' ) ) . '</em></p>';
        }

		?></div><?php

        return true;
	}

    /**
	* Build meter assignments meta box content.
    * Will be displayed on plot post type.
	*
    * @param $post
    *
    * @return true
    * @since 1.1.0
    */
	public function render_meter_assignment_meta_box_content( $plot ) {

        $meters = new Kleingarten_Meters();

        $plot_ID = $plot->ID;
        $plot = new Kleingarten_Plot( $plot_ID );

        // List all meters assigned to this plot:
        $assigned_meters = $plot->get_assigned_meters();

        // List all available meters:
        $available_meters = $meters->get_meter_IDs();

		?><div class="custom-field-panel"><?php

		$k = 0;

		// Build and print checkboxes to assign meters to this plot:
		if ( count( $available_meters ) >= 1 && $available_meters[0] != '' ) {

            echo '<ul class="kleingarten_meters_list">';
						foreach ( $available_meters as $k => $available_meter ) {

                            $meter = new Kleingarten_Meter( $available_meter );

                            // Check if this available meter is already assign to the plot we're currently editing.
                            // If so set a flag to check the checkbox.
							$checked = false;
							if ( in_array( $available_meter, $assigned_meters) ) {
								$checked = true;
							}

                            // Build the checkbox:
							echo '<li><label for="kleingarten_selected_meters_' . esc_attr( $k )
							     . '" class="checkbox_multi"><input type="checkbox" '
							     . checked( $checked, true, false )
							     //. disabled( $disable, true, false )
							     . 'name="kleingarten_selected_meters[]" value="' . esc_attr( $available_meter )
							     . '" id="kleingarten_selected_meters_' . esc_attr( $k ) . '" /> '
							     . esc_html( $meter->get_title() )
							     //. $disabled_hint
							     . '</label></li>';

						}
                        echo '<input type="hidden" name="kleingarten_selected_meters_submitted" value="1">';
                        echo '</ul>';

                        $k++;

					} else {
						echo '<p><em>'
						     . esc_html__( 'There are no meters defined yet.',
								'kleingarten' ) . '</em></p>';

        }


        wp_nonce_field( 'save_kleingarten_meter_assignments', 'kleingarten_meter_assignments_nonce' );

		?></div><?php

        return true;
	}

     /**
	* OLD STUFF: Build meter unit meta box content.
	*
    * @param $post
    *
    * @return true Altered Post
    * @since 1.1.0
    */
	public function render_meter_unit_meta_box_content( $post ) {

        $available_units = explode( "\r\n",
			get_option( 'kleingarten_units_available_for_meters' ) );

        if ( empty( $available_units ) || is_array( $available_units ) && count( $available_units ) == 1 && $available_units[0] == '' ) {
            esc_html_e( 'There are no units defined yet. Go to settings to define some.', 'kleingarten' );
        } else {

            //$current_unit = get_post_meta( $post->ID, 'kleingarten_meter_unit', true );
            $meter = new Kleingarten_Meter( $post->ID );
            $current_unit = $meter->get_unit();

            $disabled = false;
            if ( isset ( $current_unit ) && $current_unit != '' ) {
                $disabled = true;
            }

            ?><div class="custom-field-panel"><?php
            ?><select name="meter_unit" <?php disabled( $disabled ); ?>><?php

            if ( isset ( $current_unit ) && $current_unit != '' ) {
                echo '<option value="' . esc_attr( $current_unit ) . '">'.esc_attr( $current_unit ) . '</option>';
            } else {
                foreach ( $available_units as $unit ) {
                    echo '<option value="' . esc_attr( $unit ) . '">' . esc_html( $unit ) . '</option>';
                }
            }

            ?></select><?php

            if ( isset ( $current_unit ) && $current_unit != '' ) {
                echo '<input type="hidden" name="meter_unit" value="' . esc_attr( $current_unit ) . '">';
                echo '<p>' . esc_html__( 'The unit cannot be changed. Please create a new meter if you need something else.', 'kleingarten' ) . '</p>';
            } else {
                echo '<p>' . esc_html__( 'This selection will be disabled as soon as meter was published. You cannot change the meters unit later.', 'kleingarten' ) . '</p>';
            }

            wp_nonce_field( 'save_kleingarten_meter_unit', 'kleingarten_meter_unit_nonce' );

            ?></div><?php

        }

        return true;
	}

         /**
	* Build meter unit meta box content.
	*
    * @param $post
    *
    * @return true Altered Post
    * @since 1.1.0
    */
	public function render_meter_type_meta_box_content( $post ) {

        /*
        $available_types = explode( "\r\n",
			get_option( 'kleingarten_units_available_for_meters' ) );
        */
        $available_types = get_option( 'kleingarten_meter_types' );

        if ( ! is_array( $available_types ) ) {
            esc_html_e( 'Something went wrong here.', 'kleingarten' );
        } elseif ( empty( $available_types ) ) {
            esc_html_e( 'There are no units defined yet. Go to settings to define some.', 'kleingarten' );
        } else {

            //$current_unit = get_post_meta( $post->ID, 'kleingarten_meter_unit', true );
            $meter = new Kleingarten_Meter( $post->ID );
            $current_unit = $meter->get_unit();

            $disabled = false;
            if ( isset ( $current_unit ) && $current_unit != '' ) {
                $disabled = true;
            }

            ?><div class="custom-field-panel"><?php
            ?><select name="meter_unit" <?php disabled( $disabled ); ?>><?php

            if ( isset ( $current_unit ) && $current_unit != '' ) {
                echo '<option value="' . esc_attr( $current_unit ) . '">'.esc_attr( $current_unit ) . '</option>';
            } else {
                foreach ( $available_types as $type ) {
                    echo '<option value="' . esc_attr( $type['type'] ) . '">' . esc_html( $type['type'] ) . '</option>';
                }
            }

            ?></select><?php

            if ( isset ( $current_unit ) && $current_unit != '' ) {
                echo '<input type="hidden" name="meter_unit" value="' . esc_attr( $current_unit ) . '">';
                echo '<p>' . esc_html__( 'The unit cannot be changed. Please create a new meter if you need something else.', 'kleingarten' ) . '</p>';
            } else {
                echo '<p>' . esc_html__( 'This selection will be disabled as soon as meter was published. You cannot change the meters unit later.', 'kleingarten' ) . '</p>';
            }

            wp_nonce_field( 'save_kleingarten_meter_unit', 'kleingarten_meter_unit_nonce' );

            ?></div><?php

        }

        return true;
	}

    /**
	* Build meter reading submission token meta box content.
	*
    * @param $post
    *
    * @return true
    * @since 1.1.0
    */
    public function render_meter_reading_submission_token_meta_box_content( $post ) {

        // Build a list of existing tokens:
        $meter = new Kleingarten_Meter( $post->ID );
        $existing_tokens = $meter->get_tokens();

        $wp_date_format = get_option('date_format');    // Get WordPress date format from settings.

        // Print a table with existing tokens starting with the header:
        echo '<div id="kleingarten-add-meter-reading-submission-tokens">';
        $current_unit = get_post_meta( $post->ID, 'kleingarten_meter_unit', true );
        if ( isset( $current_unit ) &&  $current_unit != '' ) {

            echo '<p><a id="kleingarten-add-token-link" class="button hide-if-no-js">' . esc_html( __( 'Add Token', 'kleingarten' ) ) . '</a></p>';
            echo '<table id="kleingarten-active-tokens" class="kleingarten-active-tokens">';
            echo '<thead>';
            echo '<tr>';
            echo    '<th>' . esc_html( __( 'Token', 'kleingarten' ) ) . '</th>';
            echo    '<th>' . esc_html( __( 'Status', 'kleingarten' ) ) . '</th>';
            echo    '<th>' . esc_html( __( 'Expires', 'kleingarten' ) ) . '</th>';
            echo    '<th>' . esc_html( __( 'Actions', 'kleingarten' ) ) . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            // If there are no existing tokens...
            if ( ! $existing_tokens ) {

                // ... print a message:
                echo '<tr id="kleingarten-no-existing-tokens-hint"><td colspan="4">';
                esc_html_e( 'There is no active token. Gardeners cannot submit a reading using the web form.', 'kleingarten' );
                echo '</tr></td>';

            // But if there are existing tokens print a table row for each:
            } else {
                foreach ( $existing_tokens as $j => $existing_token ) {

                    echo '<tr>';

                    // Column: The token itself
                    echo    '<td>' . esc_html( $existing_token['token_data']['token'] ) . '</td>';

                    // Column: Token status
                    // If not expired...
                    if ( $existing_token['token_data']['token_expiry_date'] >= strtotime( 'now' ) && $existing_token['token_data']['token_status'] != 'used' ) {
                        switch ( $existing_token['token_data']['token_status'] ) {
                            case 'active':
                                echo    '<td>' . esc_html( __( 'Active', 'kleingarten' ) ) . '</td>';
                                break;
                            case 'deactivated':
                                echo    '<td>' . esc_html( __( 'Deactivated', 'kleingarten' ) ) . '</td>';
                                break;
                            case 'used':
                                echo    '<td>' . esc_html( __( 'Used', 'kleingarten' ) ) . '</td>';
                                break;
                            default:
                                echo    '<td>' . esc_html( __( 'Unknown', 'kleingarten' ) ) . '</td>';
                                break;
                        }
                    // ... or if expired:
                    } else {
                        switch ( $existing_token['token_data']['token_status'] ) {
                            case 'used':
                                echo    '<td>' . esc_html( __( 'Used', 'kleingarten' ) ) . '</td>';
                                break;
                            default:
                                echo    '<td>' . esc_html( __( 'Expired', 'kleingarten' ) ) . '</td>';
                                break;
                        }
                        //echo    '<td>' . esc_html( __( 'Expired', 'kleingarten' ) ) . '</td>';
                    }

                    // Column: Expiry date
                    echo '<td>' . esc_html( gmdate( $wp_date_format, intval( $existing_token['token_data']['token_expiry_date'] ) ) ) . '</td>';

                    // Column: Actions
                    // For expired tokens:
                    if ( $existing_token['token_data']['token_expiry_date'] < strtotime( 'now' ) ) {
                            echo '<td>';
                            // Delete Checkbox:
                            echo    '<label for="kleingarten_delete_tokens"><input name="kleingarten_delete_tokens[]" type="checkbox" value="' . esc_attr( $existing_token['meta_id'] ) . '">' . esc_html( __( 'Delete', 'kleingarten' ) ) . '</label>';
                            echo '</td>';
                    } else {
                        // For active tokens:
                        if ( $existing_token['token_data']['token_status'] == 'active' ) {
                            // Deactivate Checkbox:
                            echo '<td>';
                            echo    '<label style="margin-right: 1rem;" for="kleingarten_deactivate_tokens"><input name="kleingarten_deactivate_tokens[]" type="checkbox" value="' . esc_attr( $existing_token['meta_id'] ) . '">' . esc_html( __( 'Deactivate', 'kleingarten' ) ) . '</label>';
                        // For deactivated tokens:
                        } elseif ( $existing_token['token_data']['token_status'] == 'deactivated' ) {
                            echo '<td>';
                            // Activate Checkbox:
                            echo    '<label for="kleingarten_activate_or_delete_tokens"><input name="kleingarten_activate_or_delete_tokens[' . absint( $existing_token['meta_id'] ) . ']" type="radio" value="reactivate_' . esc_attr( $existing_token['meta_id'] ) . '">' . esc_html( __( 'Reactivate', 'kleingarten' ) ) . '</label><br>';
                            // Delete Checkbox:
                            echo    '<label for="kleingarten_activate_or_delete_tokens"><input name="kleingarten_activate_or_delete_tokens[' . absint( $existing_token['meta_id'] ) . ']" type="radio" value="delete_' . esc_attr( $existing_token['meta_id'] ) . '">' . esc_html( __( 'Delete', 'kleingarten' ) ) . '</label>';
                            echo '</td>';
                        // For used tokens:
                        } elseif ( $existing_token['token_data']['token_status'] == 'used' ) {
                            echo '<td>';
                            // Delete Checkbox:
                            echo    '<label for="kleingarten_delete_tokens"><input name="kleingarten_delete_tokens[]" type="checkbox" value="' . esc_attr( $existing_token['meta_id'] ) . '">' . esc_html( __( 'Delete', 'kleingarten' ) ) . '</label>';
                            echo '</td>';
                        }
                        else {
                            echo '<td></td>';
                        }
                    }

                    echo '</tr>';

                }

            }

            echo '</tbody>';
            echo '</table>';
            wp_nonce_field( 'save_kleingarten_meter_reading_submission_tokens', 'kleingarten_meter_reading_submission_tokens_nonce' );

        } else {
            echo '<p><em>' . esc_html( __( 'Please select a unit and save the meter to add tokens.', 'kleingarten' ) ) . '</em></p>';
        }

        echo '</div>';

        return true;

    }

	/**
	* Save like meta box.
	*
    * @param $post_id
    *
    * @return void
    */
	public function save_likes_meta_box( $post_id ) {

		if ( ! isset ( $_POST['kleingarten_likes_nonce'] )
		     || ! wp_verify_nonce( sanitize_key( wp_unslash ( $_POST['kleingarten_likes_nonce'] ) ),
				'save_kleingarten_likes' )
		) {
			return;
		} else {

			if ( isset( $_POST['kleingarten_likeed_by'] ) ) {

				$string_to_save = '';
				$separator      = ',';

                //if ( isset( $_POST['kleingarten_likeed_by'] ) ) {

                    $formdata = array_unique( array_map( 'absint', wp_unslash( $_POST['kleingarten_likeed_by'] ) ) );

                //}

        			foreach ( $formdata as $likeed_by ) {
		    			$string_to_save .= sanitize_text_field( $likeed_by ) . $separator;
			    	}

				    update_post_meta( $post_id, 'kleingarten_likes', $string_to_save );

			} else {
                update_post_meta( $post_id, 'kleingarten_likes', '' );
            }

		}

	}

 	/**
	* Save meter readings meta box.
	*
    * @param $post_id
    *
    * @return void
    * @since 1.1.0
    */
	public function save_meter_unit_meta_box( $post_id ) {

        if ( ! isset ( $_POST['kleingarten_meter_unit_nonce'] )
		     || ! wp_verify_nonce( sanitize_key( wp_unslash ( $_POST['kleingarten_meter_unit_nonce'] ) ),
				'save_kleingarten_meter_unit' )
		) {
			return;
		} else {

            $meter = new Kleingarten_Meter( $post_id );

            if ( $meter->get_unit() !== null ) return;

            if ( isset( $_POST['meter_unit'] ) ) {

                if ( ! is_wp_error( $meter->set_unit( sanitize_text_field( wp_unslash( $_POST['meter_unit'] ) ) ) ) ) {
                    $this->add_message( 'kleingarten_meter_unit', 'kleingarten_meter_unit', __( 'Meter unit set.', 'kleingarten' ), 'success' );
                } else {
                    $this->add_message( 'kleingarten_meter_unit', 'kleingarten_meter_unit', __( 'Something went wrong. Meter unit could not be set.', 'kleingarten' ), 'error' );
                }
/*
                $sanitized_data = sanitize_text_field( wp_unslash( $_POST['meter_unit'] ) );
                $meta_id = 0;
                $meta_id = update_post_meta( $post_id, 'kleingarten_meter_unit', $sanitized_data );
                if ( ! is_bool( $meta_id ) && ! $meta_id === false ) {
                    $this->add_message( 'kleingarten_meter_unit', 'kleingarten_meter_unit', __( 'Meter unit set.', 'kleingarten' ), 'success' );
                // ... or, but only if meter is not already set, print an error:
                } elseif ( metadata_exists( 'post', $meta_id, 'kleingarten_meter_unit' ) ) {
                    $this->add_message( 'kleingarten_meter_unit', 'kleingarten_meter_unit', __( 'Something went wrong. Meter unit could not be set.', 'kleingarten' ), 'error' );
                }
*/

            }

		}

	}

    /**
    * Adds a message / error to be printed on admin screens.
    *
    * @param $plot_ID
    *
    * @return void
    *@since 1.1.0
    */
    private function add_message( $setting, $code, $message, $type = 'error' ):void {

        /*
        if ( ! is_bool ( $dismissible ) ) {
            $dismissible = false;
        }
        */

       add_settings_error(
            strval( $setting ),
            strval( $code ),
            strval( $message ),
            strval( $type )
       );

       set_transient( 'kleingarten_post_meta_notices', get_settings_errors(), 30 );

    }

	/**
	* Save meter readings meta box.
	*
    * @param $post_id
    *
    * @return void
    * @since 1.1.0
    */
	public function save_meter_readings_meta_box( $post_id ) {

        // First check nonce and stop here if check failed...
		if ( ! isset ( $_POST['kleingarten_meter_readings_nonce'] )
		     || ! wp_verify_nonce( sanitize_key( wp_unslash ( $_POST['kleingarten_meter_readings_nonce'] ) ),
				'save_kleingarten_meter_readings' )
		) {
			return;
        // ... but if nonce check succeeded:
		} else {

            // If there is a new meter reading so save...
		    if ( isset( $_POST['new_kleingarten_meter_reading'] ) ) {

				$sanitized_data = array();

                // ... and if essential reading data set at least is not empty...
                if ( isset( $_POST['new_kleingarten_meter_reading']['value'] ) && $_POST['new_kleingarten_meter_reading']['value'] != '' && isset( $_POST['new_kleingarten_meter_reading']['date'] ) && $_POST['new_kleingarten_meter_reading']['date'] != '' && isset( $_POST['new_kleingarten_meter_reading']['by'] ) && $_POST['new_kleingarten_meter_reading']['by'] != '' ) {

                    // ... sanitize data...
                    $sanitized_data['date'] = strtotime( sanitize_text_field( wp_unslash( $_POST['new_kleingarten_meter_reading']['date'] ) ) );
                    $sanitized_data['value'] = absint( wp_unslash( $_POST['new_kleingarten_meter_reading']['value'] ) );
                    $sanitized_data['by'] = absint( wp_unslash(  $_POST['new_kleingarten_meter_reading']['by'] ) );
                    if ( isset( $_POST['new_kleingarten_meter_reading']['meter-no'] ) ) {
                        $sanitized_data['meter-no'] = sanitize_text_field( wp_unslash( $_POST['new_kleingarten_meter_reading']['meter-no'] ) );
                    }

                    // ... validate data...
                    $validation_errors = 0;
                    //$existing_readings = get_post_meta( $post_id, 'kleingarten_meter_reading' );
                    $meter = new Kleingarten_Meter( $post_id );
                    $existing_readings = $meter->get_readings();
                    if ( $existing_readings ) {

                        // Check if we already have a reading for this date:
                        foreach ( $existing_readings as $existing_reading ) {
                            if ( $existing_reading['date'] === $sanitized_data['date'] ) {

                                $validation_errors++;
                                $this->add_message( 'kleingarten_meter_reading', 'kleingarten_meter_reading', __( 'A meter reading already exists for this date.', 'kleingarten' ), 'error' );
                                break;

                            }
                        }

                        // Determine if date is in the future:
                        //$reading_date = strtotime( date( 'Y-m-d', strtotime( $sanitized_data['date'] ) ) );
                        $reading_date = $sanitized_data['date'];
                        $today = strtotime( gmdate( 'Y-m-d' ) );
                        //$today = strtotime ( 'now' );
                        if ( $reading_date > $today ) {
                             $validation_errors++;
                             $this->add_message( 'kleingarten_meter_reading', 'kleingarten_meter_reading', __( 'Cannot save a reading for a date in the future.', 'kleingarten' ), 'error' );
                        }

                    }

                    // ...and finally save it if valid:
                    if ( $validation_errors === 0 ) {

                        $meta_id = 0;
                        //$meta_id = add_post_meta( $post_id, 'kleingarten_meter_reading', $sanitized_data );
                        $meta_id = $meter->add_reading( $sanitized_data['value'], $sanitized_data['date'], $sanitized_data['by'], $sanitized_data['meter-no'] );
                        if ( ! is_bool( $meta_id ) && ! $meta_id === false ) {
                            $this->add_message( 'kleingarten_meter_reading', 'kleingarten_meter_reading', __( 'New reading saved.', 'kleingarten' ), 'success' );
                        } else {
                            $this->add_message( 'kleingarten_meter_reading', 'kleingarten_meter_reading', __( 'Something went wrong. Reading could not be saved.', 'kleingarten' ), 'error' );
                        }

                    }

                }
			}

            // If there are meter readings to delete...
            if ( isset( $_POST['delete_kleingarten_meter_readings'] ) ) {

                // ... and if it's more than one single reading...
                //if ( is_array( $_POST['delete_kleingarten_meter_readings'] ) ) {

                    $readings_to_delete = array_unique( array_map( 'absint', wp_unslash( $_POST['delete_kleingarten_meter_readings'] ) ) );

                    // ... delete them all:
                    $error_counter = 0;
                    foreach ( $readings_to_delete as $reading_to_delete ) {
                        $meter = new Kleingarten_Meter( $post_id );
                        $meter->remove_reading( $reading_to_delete );
                        if ( is_wp_error( $meter ) ) {
                            $error_counter++;
                        }
                    }

                    if ( ! $error_counter > 0 ) {
                        $this->add_message( 'kleingarten_meter_reading', 'kleingarten_meter_reading', __( 'Readings deleted.', 'kleingarten' ), 'info' );
                    } else {
                        $this->add_message( 'kleingarten_meter_reading', 'kleingarten_meter_reading', __( 'Something went wrong. Some readings could not be deleted.', 'kleingarten' ), 'error' );
                    }

                //}
                // ... or if it is just one single reading...
                // (This is mostly pro forma. Probably never triggered, because single readings are presented as array, too.)
                /*
                else {
                    $this->add_message( 'kleingarten_meter_reading', 'kleingarten_meter_reading', __( 'SINGLE!!.', 'kleingarten' ), 'info' );
                    // ... delete it:
                    if (delete_metadata_by_mid( 'post', $readings_to_delete ) ) {
                        $this->add_message( 'kleingarten_meter_reading', 'kleingarten_meter_reading', __( 'Reading deleted.', 'kleingarten' ), 'info' );
                    } else {
                        $this->add_message( 'kleingarten_meter_reading', 'kleingarten_meter_reading', __( 'Something went wrong. Reading could not be deleted.', 'kleingarten' ), 'error' );
                    }
                }
                */

            }

		}

	}

    /**
	* Save meter assignments meta box.
	*
    * @param $post_id
    *
    * @return void
    * @since 1.1.0
    */
	public function save_meter_assignment_meta_box( $plot_id ) {

        if ( ! isset ( $_POST['kleingarten_meter_assignments_nonce'] )
		     || ! wp_verify_nonce( sanitize_key( wp_unslash ( $_POST['kleingarten_meter_assignments_nonce'] ) ),
				'save_kleingarten_meter_assignments' )
		) {
			return;
		} else {

            $plot = new Kleingarten_Plot( $plot_id );

            // Get a list of currently assigned meters:
            //$currently_assigned_meters = $this->get_assigned_meters( $plot_id );
            $currently_assigned_meters = $plot->get_assigned_meters();

            // We will need all these "isset" checking to prevent warnings
            // resulting from dealing with empty or non-existing arrays.
            // So think before remove!

            // Assign new meters:
            if ( isset( $currently_assigned_meters ) && isset( $_POST['kleingarten_selected_meters'] ) ) {

                // List all submitted meters that are not already in DB:
                //$meters_to_add = array_diff( (array) $_POST['kleingarten_selected_meters'], (array) $currently_assigned_meters );
                $meters_to_add = array_diff( array_unique( array_map( 'absint', wp_unslash( $_POST['kleingarten_selected_meters'] ) ) ), $currently_assigned_meters );

                // Then add them:
                $error_counter = 0;
                $success_counter = 0;
                foreach ( $meters_to_add as $meter_to_add ) {

                    $meter = new Kleingarten_Meter( $meter_to_add );
                    $meter->assign_to_plot( $plot_id );

                    $meta_id = 0;
                    $meta_id = add_post_meta( $plot_id, 'kleingarten_meter_assignment', absint ($meter_to_add) );
                    //if ( is_bool( $meta_id ) && $meta_id === false ) {
                    if ( is_wp_error( $meta_id ) ) {
                        $error_counter++;
                    } else {
                        $success_counter++;
                    }

                }

                if ( $error_counter === 0 && $success_counter > 0 ) {
                    $this->add_message( 'kleingarten_meter_assignment', 'kleingarten_meter_assignment', __( 'Meters assigned.', 'kleingarten' ), 'success' );
                } elseif ( $error_counter > 0 )  {
                    $this->add_message( 'kleingarten_meter_assignment', 'kleingarten_meter_assignment', __( 'Something went wrong. Meters could not be assigned.', 'kleingarten' ), 'error' );
                }

            }

            // Remove meters:
            if ( isset( $currently_assigned_meters ) /* && isset( $_POST['kleingarten_selected_meters'] ) */ ) {

                // List all meters that are in DB but not in form data anymore:
                if ( isset( $_POST['kleingarten_selected_meters'] ) ) {
                    $meters_to_remove = array_diff( $currently_assigned_meters, array_unique( array_map( 'absint', wp_unslash( $_POST['kleingarten_selected_meters'] ) ) ) );

                } else {
                    $meters_to_remove = $currently_assigned_meters;
                }

                // Then delete them:
                $error_counter = 0;
                $success_counter = 0;
                foreach ( $meters_to_remove as $meter_to_remove ) {
                    $meter = new Kleingarten_Meter( $meter_to_remove );
                    if ( is_wp_error( $meter->unassign_from_plot( $plot_id ) ) ) {
                        $error_counter++;
                    } else {
                        $success_counter++;
                    }
                }

                if ( $error_counter === 0 && $success_counter > 0 ) {
                    $this->add_message( 'kleingarten_meter_assignment', 'kleingarten_meter_assignment', __( 'Meter assignments removed.', 'kleingarten' ), 'success' );
                } elseif ( $error_counter > 0 )  {
                    $this->add_message( 'kleingarten_meter_assignment', 'kleingarten_meter_assignment', __( 'Something went wrong. Meter assignments could not be removed.', 'kleingarten' ), 'error' );
                }

            }

        }

	}

    /**
    * Prints custom admin notices. To be used as callback on "admin_notices".
    *
    * @param $plot_ID
    *
    * @return void
    *@since 1.1.0
    */
    public function print_admin_notices() {

        // No errors? Great! Stop right here:
        if ( ! ( $messages = get_transient( 'kleingarten_post_meta_notices' ) ) ) {
            return;
        }

        // But if there are errors build HTML to print them:
        foreach ( $messages as $message ) {

            $class = '';
            switch ( $message['type'] ) {

                case 'error':
                    $class = 'notice notice-error';
                    break;

                case 'success':
                    $class = 'notice notice-success';
                    break;

                case 'warning':
                    $class = 'notice notice-warning';
                    break;

                case 'info': default:
                    $class = 'notice notice-info';
                    break;

            }

            echo '<div id="kleingarten-post-meta-message" class="' . esc_attr( $class ) . ' is-dismissible"><p>';
            echo esc_html( $message['message'] );
            echo '</p></div>';

        }

        // Clear and the transient and unhook any other notices so we don't see duplicate messages
        delete_transient( 'kleingarten_post_meta_notices' );
        remove_action( 'admin_notices', 'print_admin_notices' );

    }

    /**
    * Adds a new token to database. To be used as AJAX callback.
    *
    * @param $plot_ID
    *
    * @since 1.1.0
    */
    public function kleingarten_add_meter_reading_submission_token_ajax_callback() {

        // Check nonce and kill script if check fails:
        if ( ! isset ( $_POST['nonce'] )
		     || ! wp_verify_nonce( sanitize_key( wp_unslash ( $_POST['nonce'] ) ), 'kleingarten-admin-ajax-nonce' ) ) {
            die ( 'Busted!');
        }

        if ( isset ( $_POST['meter_id'] ) ) {

            // Create a token...
            $meter = new Kleingarten_Meter( absint( wp_unslash( $_POST['meter_id'] ) ) );
            $token_id = $meter->create_token();

            if ( ! is_wp_error( $token_id ) ) {

                $token_details = $meter->get_token_details( $token_id );
                $wp_date_format = get_option('date_format');
                //$token_details['token_expiry_date'] = date( $wp_date_format, $token_details['token_expiry_date'] );
                $token_details['token_expiry_date'] = gmdate( $wp_date_format, $token_details['token_expiry_date'] );

                $json_response = $token_details;
                // Fine, return the token so JS and die:
                wp_send_json_success( $json_response, 200 );
            }


        }

		wp_die(); // Ajax call must die to avoid trailing 0 in your response.

    }

    /**
    * Saves meta readings submission tokens. As creating tokens is usually handled by AJAX this function mostly cares about deactivating tokens.
    * To be uses as a callback on "save_post".
    *
    * @param $plot_ID
    *
    * @return void
    *@since 1.1.0
    */
    public function save_meter_reading_submission_token_meta_box( $meter_id ) {

        // First check nonce and stop here if check failed...
		if ( ! isset ( $_POST['kleingarten_meter_reading_submission_tokens_nonce'] )
		     || ! wp_verify_nonce( sanitize_key( wp_unslash ( $_POST['kleingarten_meter_reading_submission_tokens_nonce'] ) ),
				'save_kleingarten_meter_reading_submission_tokens' )
		) {
			return;
        // ... but if nonce check succeeded:
		} else {

           $meter = new Kleingarten_Meter( $meter_id );

           // If there are tokens to deactivate...
           if ( isset( $_POST['kleingarten_deactivate_tokens'] ) ) {

               $tokens_to_deactivate = array_unique( array_map( 'sanitize_text_field', wp_unslash(  $_POST['kleingarten_deactivate_tokens'] ) ) );
               //$tokens_to_deactivate = $_POST['kleingarten_deactivate_tokens'];
               // ... and if it's more than one single reading...
               if ( is_array( $tokens_to_deactivate ) ) {

                   // ... deactivate them all:
                   //$error_counter = 0;
                   $j = 0;
                   foreach ( $tokens_to_deactivate as $token_to_deactivate ) {
                       if ( is_wp_error( $result = $meter->deactivate_token( $token_to_deactivate ) ) ) {
                           //$error_counter++;
                           $this->add_message( 'kleingarten_meter_reading_submission_token', 'kleingarten_meter_reading_submission_token', $result->get_error_message(), 'error' );
                       } else {
                           $j++;
                       }
                   }
                   if ( $j >= 1 ) {
                       /* translators: Number of deactivated tokens */
                       $this->add_message( 'kleingarten_meter_reading_submission_token', 'kleingarten_meter_reading_submission_token', sprintf( __( '%u tokens deactivated.', 'kleingarten' ), $j ), 'error' );
                   } elseif ( $j == 1 ) {
                       /* translators: Number of deactivated tokens */
                       $this->add_message( 'kleingarten_meter_reading_submission_token', 'kleingarten_meter_reading_submission_token', sprintf( __( '%u token deactivated.', 'kleingarten' ), $j ), 'error' );
                   }

                   /*
                   if ( ! $error_counter > 0 ) {
                       $this->add_message( 'kleingarten_meter_reading_submission_token', 'kleingarten_meter_reading_submission_token', __( 'Tokens deactivated.', 'kleingarten' ), 'info' );
                   } else {
                       $this->add_message( 'kleingarten_meter_reading_submission_token', 'kleingarten_meter_reading_submission_token', __( 'Something went wrong. Some tokens could not be deactivated.', 'kleingarten' ), 'error' );
                   }
                   */

               }
               // ... or if it is just one single reading...
               // (This is mostly pro forma. Probably never triggered, because single readings are presented as array, too.)
               /*
               else {
                   // ... deactivate it:
                   //if (delete_metadata_by_mid( 'post', absint( $tokens_to_delete ) ) ) {
                   if ( $this->deactivate_meter_reading_submission_token( absint( $tokens_to_delete ) ) ) {
                       $this->add_message( 'kleingarten_meter_reading_submission_token', 'kleingarten_meter_reading_submission_token', __( 'Token deactivated.', 'kleingarten' ), 'info' );
                   } else {
                       $this->add_message( 'kleingarten_meter_reading_submission_token', 'kleingarten_meter_reading_submission_token', __( 'Something went wrong. Token could not be deactivated.', 'kleingarten' ), 'error' );
                   }
               }
               */

           }

           // If there are tokens to delete...
           if ( isset( $_POST['kleingarten_delete_tokens'] ) ) {

               //$tokens_to_delete = $_POST['kleingarten_delete_tokens'];
               $tokens_to_delete = array_unique( array_map( 'absint', wp_unslash( $_POST['kleingarten_delete_tokens'] ) ) );

               // ... and if it's more than one single reading...
               if ( is_array( $tokens_to_delete ) ) {

                   // ... deactivate them all:
                   $j = 0;
                   foreach ( $tokens_to_delete as $token_to_delete ) {

                       // If the token has been used deactivate it first.
                       // Otherwise, it cannot be deleted.
                       $token_details = $meter->get_token_details( $token_to_delete );
                       if ( $token_details['token_status'] == 'used' ) {
                           $meter->deactivate_token( $token_to_delete );
                       }

                       // Delete the token:
                       if ( is_wp_error( $result = $meter->delete_token( $token_to_delete ) ) ) {
                           $this->add_message( 'kleingarten_meter_reading_submission_token', 'kleingarten_meter_reading_submission_token', $result->get_error_message(), 'error' );
                       } else {
                           $j++;
                       }

                   }
                   if ( $j >= 1 ) {
                       /* translators: Number of deleted tokens */
                       $this->add_message( 'kleingarten_meter_reading_submission_token', 'kleingarten_meter_reading_submission_token', sprintf( __( '%u tokens deleted.', 'kleingarten' ), $j ), 'error' );
                   } elseif ( $j == 1 ) {
                       /* translators: Number of deleted tokens */
                       $this->add_message( 'kleingarten_meter_reading_submission_token', 'kleingarten_meter_reading_submission_token', sprintf( __( '%u token deleted.', 'kleingarten' ), $j ), 'error' );
                   }

               }
               // ... or if it is just one single token...
               // (This is mostly pro forma. Probably never triggered, because single readings are presented as array, too.)
               /*
               else {
                   // ... deactivate it:
                   if (delete_metadata_by_mid( 'post', absint( $tokens_to_delete ) ) ) {
                   //if ( $this->deactivate_meter_reading_submission_token( absint( $tokens_to_delete ) ) ) {
                       $this->add_message( 'kleingarten_meter_reading_submission_token', 'kleingarten_meter_reading_submission_token', __( 'Token deleted.', 'kleingarten' ), 'info' );
                   } else {
                       $this->add_message( 'kleingarten_meter_reading_submission_token', 'kleingarten_meter_reading_submission_token', __( 'Something went wrong. Token could not be deleted.', 'kleingarten' ), 'error' );
                   }
               }
               */

           }

           // If there are deactivated tokens to delete OR to re-activate...
           if ( isset( $_POST['kleingarten_activate_or_delete_tokens'] ) ) {

               $tokens_to_delete_or_reactivate = array_unique( array_map( 'sanitize_text_field', wp_unslash( $_POST['kleingarten_activate_or_delete_tokens'] ) ) );

               // ... and if it's more than one single token...
               if ( is_array( $tokens_to_delete_or_reactivate ) ) {

                   // ... look at every single token we have to deal with:
                   $j = 0;
                   foreach ( $tokens_to_delete_or_reactivate as $token_to_delete_or_reactivate ) {

                       // If token shall be deleted...
                       if ( str_contains( $token_to_delete_or_reactivate, 'delete_' ) ) {

                           // Get token meta ID:
                           $token_to_delete = absint( str_replace( 'delete_', '', $token_to_delete_or_reactivate ) ) ;

                           // Delete it and print an error on failure:
                           if ( is_wp_error( $result = $meter->delete_token( $token_to_delete ) ) ) {
                               $this->add_message( 'kleingarten_meter_reading_submission_token', 'kleingarten_meter_reading_submission_token', $result->get_error_message(), 'error' );
                           } else {
                               $j++;
                           }

                       }
                   }
                   if ( $j >= 1 ) {
                       /* translators: Number of deleted tokens */
                       $this->add_message( 'kleingarten_meter_reading_submission_token', 'kleingarten_meter_reading_submission_token', sprintf( __( '%u tokens deleted.', 'kleingarten' ), $j ), 'error' );
                   } elseif ( $j == 1 ) {
                       /* translators: Number of deleted tokens */
                       $this->add_message( 'kleingarten_meter_reading_submission_token', 'kleingarten_meter_reading_submission_token', sprintf( __( '%u token deleted.', 'kleingarten' ), $j ), 'error' );
                   }

                   // ... look at every single token we have to deal with again:
                   $j = 0;
                   foreach ( $tokens_to_delete_or_reactivate as $token_to_delete_or_reactivate ) {

                       // If token shall be re-activated...
                       if ( str_contains( $token_to_delete_or_reactivate, 'reactivate_' ) ) {

                           // Get token meta ID:
                           $token_to_activate = absint( str_replace( 'reactivate_', '', $token_to_delete_or_reactivate ) ) ;

                           // Delete it and print an error on failure:
                           if ( is_wp_error( $result = $meter->activate_token( $token_to_activate ) ) ) {
                               $this->add_message( 'kleingarten_meter_reading_submission_token', 'kleingarten_meter_reading_submission_token', $result->get_error_message(), 'error' );
                           } else {
                               $j++;
                           }

                       }

                   }
                   if ( $j >= 1 ) {
                       /* translators: Number of deleted tokens */
                       $this->add_message( 'kleingarten_meter_reading_submission_token', 'kleingarten_meter_reading_submission_token', sprintf( __( '%u tokens (re-)activated.', 'kleingarten' ), $j ), 'error' );
                   } elseif ( $j == 1 ) {
                       /* translators: Number of deleted tokens */
                       $this->add_message( 'kleingarten_meter_reading_submission_token', 'kleingarten_meter_reading_submission_token', sprintf( __( '%u token (re-)activated.', 'kleingarten' ), $j ), 'error' );
                   }

               }

           }

        }

    }


    /**
    * Sets task status.
    *
    * @param $task_ID
    *
    * @return void
    */
    public function save_task_status_meta_box( $task_ID ) {

        if ( ! isset ( $_POST['kleingarten_save_task_status_nonce'] )
	         || ! wp_verify_nonce( sanitize_key( wp_unslash ( $_POST['kleingarten_save_task_status_nonce'] ) ),
			    'kleingarten_save_task_status_nonce_action' )
	    ) {
		    return;
        }

        if ( isset( $_POST['kleingarten_task_status'] ) ) {

            $all_available_status = Kleingarten_Tasks::get_all_available_status();
            $all_available_status_slugs = array();
            foreach ( $all_available_status as $status ) {
                $all_available_status_slugs[] = $status->slug;
            }

            $new_status = sanitize_text_field( wp_unslash( $_POST['kleingarten_task_status'] ) );

		    //if ( $new_status == 'todo' || $new_status == 'next' || $new_status == 'done'  ) {
            if ( in_array( $new_status, $all_available_status_slugs ) ) {

                $task = new Kleingarten_Task( $task_ID );
                $task->set_status( $new_status );

            }
        }

    }

}