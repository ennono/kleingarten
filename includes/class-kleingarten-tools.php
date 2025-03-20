<?php /** @noinspection PhpUndefinedConstantInspection */
/* Tools class file. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tools class.
 */
class Kleingarten_Tools {

	/**
	 * The single instance of Kleingarten_Tools.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.1.2
	 */
	private static $_instance = null; //phpcs:ignore

	/**
	 * The main plugin object.
	 *
	 * @var     object
	 * @access  public
	 * @since   1.1.2
	 */
	public $parent = null;

	/**
	 * Available tools for plugin.
	 *
	 * @var     array
	 * @access  public
	 * @since   1.1.2
	 */
	public $tools = array();

	/**
	 * Constructor function.
	 *
	 * @param   object  $parent  Parent object.
	 */
	public function __construct( $parent ) {

		$this->parent = $parent;

		// Initialise tools:
		add_action( 'init', array( $this, 'init_tools' ) );

		// Initialise tools base page:
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );


		//add_action ('post_edit_form_tag', array( $this, 'admin_enc_tag' ) );

	}

	/**
	 * Main Kleingarten_Tool Instance
	 *
	 * Ensures only one instance of Kleingarten_Tools is loaded or can be loaded.
	 *
	 * @param   object  $parent  Object instance.
	 *
	 * @return object Kleingarten_Tools instance
	 * @since 1.1.2
	 * @static
	 * @see   Kleingarten()
	 */
	public static function instance( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}

		return self::$_instance;
	}

	/**
	 * Initialises tools.
	 *
	 * @return void
	 * @since 1.1.2
	 */
	public function init_tools() {

		// Load tools as defined:
		$this->tools = $this->tools();

		// Hook a response handler for every defined tool.
		// Response handler must be named like this: "handle"_response
		if ( $this->tools ) {
			foreach ( $this->tools as $tool ) {
				add_action( 'admin_post_kleingarten_' . $tool['handle'],
					array( $this, $tool['handle'] . '_response' ) );
			}
		}

	}

	/**
	 * Describes available tools.
	 *
	 * @return array Fields to be displayed on settings page
	 * @since 1.1.2
	 */
	private function tools() {

		$tools = array();

		$tools['batch_create_plots'] = array(
			'handle'      => 'batch_create_plots',
			'title'       => __( 'Create multiple plots', 'kleingarten' ),
			'description' => __( 'Save time and create several numbered plots at once. The number of plots that can be created here is limited to 500. Be careful not to produce trash! There is no further validation. When you submit the form, the plots will be created.',
				'kleingarten' ),
		);

		$tools['batch_create_meter_reading_submission_tokens'] = array(
			'handle'      => 'batch_create_meter_reading_submission_tokens',
			'title'       => __( 'Create multiple tokens', 'kleingarten' ),
			'description' => __( 'Create a meter reading submission token for each existing meter. Tokens will be active immediately.',
				'kleingarten' ),
		);

		$tools['import_meter_readings'] = array(
			'handle'      => 'import_meter_readings',
			'title'       => __( 'Import Readings', 'kleingarten' ),
			'description' => __( 'Upload a CSV file to import meter readings. Be careful not to produce trash! The tool will not ask questions. When you click the button your data will be imported.',
				'kleingarten' ),
		);

		return $tools;

	}

	/**
	 * Add Kleingarten Tools base page to admin menu.
	 *
	 * @return void
	 * @since 1.1.2
	 */
	public function admin_menu() {
		add_management_page( __( 'Kleingarten', 'kleingarten' ),
			__( 'Kleingarten', 'kleingarten' ), 'manage_options',
			'kleingarten_tools', array( $this, 'admin_page' ), 50 );
	}

	/**
	 * Print HTML for Kleingarten Tools base page.
	 *
	 * @return void
	 * @since 1.1.2
	 */
	function admin_page() {

		$allowed_html          = wp_kses_allowed_html( 'post' );
		$allowed_html['form']  = array(
			'name'   => array(),
			'id'     => array(),
			'method' => array(),
			'action' => array(),
		);
		$allowed_html['input'] = array(
			'class' => array(),
			'id'    => array(),
			'name'  => array(),
			'value' => array(),
			'type'  => array(),
		);

		echo '<div class="wrap">';
		echo '<div class="kleingarten-admin-wrapper">';
		echo '<div class="kleingarten-admin-main-section">';
		echo '<h1>' . esc_html( __( 'Kleingarten Tools', 'kleingarten' ) )
		     . '</h1>';

		if ( $this->tools ) {
			foreach ( $this->tools as $tool ) {

				echo '<div class="card">';
				echo '<h2 class="title">' . esc_html( $tool['title'] )
				     . '</h2>';
				echo '<p>' . esc_html( $tool['description'] ) . '</p>';
				echo wp_kses( call_user_func( array(
					$this,
					$tool['handle'] . '_callback'
				) ), $allowed_html );
				echo '</div>';

			}
		}

		echo '</div>';  // class="kleingarten-admin-main-section"

		echo '<div class="kleingarten-admin-sidebar">';
		echo '<img src=' . esc_url( plugin_dir_url( __DIR__ ) )
		     . 'assets/Kleingarten_Logo_200px.png>';
		echo '</div>';  // class="kleingarten-admin-main-section"

		echo '</div>';  // class="kleingarten-admin-wrapper"
		echo '</div>';  // class="wrap"

	}

	/**
	 * Builds HTML form for batch plot creation.
	 *
	 * @return false|string
	 * @since 1.1.2
	 */
	function batch_create_plots_callback() {

		ob_start();

		// Print messages and notices:
		if ( isset( $_GET['kleingarten_batch_create_plots_get_nonce'] )
		     && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['kleingarten_batch_create_plots_get_nonce'] ) ),
				'kleingarten_batch_create_plots_get_nonce_action' ) ) {

			// Print success message if we got a success flag from last form submission:
			if ( isset( $_GET['kleingarten_batch_create_plots_success'] )
			     && $_GET['kleingarten_batch_create_plots_success'] == true ) {
				$this->print_message( esc_html( __( 'Plots created successfully.',
					'kleingarten' ) ), 'success' );
			}

			// Print errors if there are any:
			if ( isset( $_GET['kleingarten_batch_create_plots_errors']['errors'] ) ) {

				// This sufficiently unslashed and sanitized:
				$kleingarten_batch_create_plots_errors_unslashed
					= map_deep( $_GET['kleingarten_batch_create_plots_errors']['errors'],
					'wp_unslash' );
				$kleingarten_batch_create_plots_errors_sanitized
					= map_deep( $kleingarten_batch_create_plots_errors_unslashed,
					'sanitize_text_field' );
				foreach (
					$kleingarten_batch_create_plots_errors_sanitized as $error
				) {
					$this->print_message( $error[0] );
				}
			}

		}

		?>
        <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
              method="post">
            <input type="hidden" name="action"
                   value="kleingarten_batch_create_plots">
            <p>
                <label for="kleingarten_batch_create_plots_num">
					<?php esc_html_e( 'Number', 'kleingarten' ); ?>:
                    <br>
                    <input name="kleingarten_batch_create_plots_num"
                           type="number" max="500">
                </label>
            </p>
            <p>
                <label for="kleingarten_batch_create_plots_starting_from">
					<?php esc_html_e( 'First Number', 'kleingarten' ); ?>:
                    <br>
                    <input name="kleingarten_batch_create_plots_starting_from"
                           type="number" min="1">
                </label>
            </p>
            <p>
                <label for="kleingarten_batch_create_plots_prefix">
					<?php esc_html_e( 'Prefix', 'kleingarten' ); ?>:
                    <br>
                    <input name="kleingarten_batch_create_plots_prefix"
                           type="text">
                </label>
            </p>
            <p>
                <label for="kleingarten_batch_create_plots_prefix">
					<?php esc_html_e( 'Suffix', 'kleingarten' ); ?>:
                    <br>
                    <input name="kleingarten_batch_create_plots_suffix"
                           type="text">
                </label>
            </p>
            <p>
                <label for="kleingarten_batch_create_plots_add_meters">
                    <input name="kleingarten_batch_create_plots_add_meters"
                           type="checkbox">
					<?php esc_html_e( 'Add one of the defined counters to each plot.',
						'kleingarten' ); ?>
                </label>
            </p>
			<?php wp_nonce_field( 'kleingarten_batch_create_plots_nonce_action',
				'kleingarten_batch_create_plots_nonce' ); ?>
            <p class="submit"><input type="submit" name="submit" id="submit"
                                     class="button button-primary"
                                     value="<?php esc_html_e( 'Create Plots',
				                         'kleingarten' ); ?>"></p>
        </form>
		<?php

		return ob_get_clean();

	}

	/**
	 * Adds a message / error to be printed on admin screens.
	 *
	 * @return void
	 * @since 1.1.2
	 */
	private function print_message( $message, $type = 'error' ) {

		echo '<div id="message" class="' . esc_attr( $type )
		     . ' is-dismissible updated">';
		echo '<p>' . esc_html( $message ) . '</p>';
		echo '</div>';

	}

	/**
	 * Handles batch plot creation form submission by creating plots.
	 *
	 * @return void
	 * @since 1.1.2
	 */
	function batch_create_plots_response() {

		$errors = new WP_Error();

		// Try to get available meter types (units):
		$available_meters_option
			= get_option( 'kleingarten_units_available_for_meters' );
		if ( empty( $available_meters_option ) ) {
			$available_meters = array();
		} else {
			$available_meters = explode( PHP_EOL, $available_meters_option );
		}

		// No nonce, no further action...
		if ( isset( $_POST['kleingarten_batch_create_plots_nonce'] )
		     && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['kleingarten_batch_create_plots_nonce'] ) ),
				'kleingarten_batch_create_plots_nonce_action' ) ) {

			// If a number of plots to create was submitted...
			if ( isset( $_POST['kleingarten_batch_create_plots_num'] )
			     && $_POST['kleingarten_batch_create_plots_num'] > 0 ) {

				// ... limit number to 500:
				$plots_to_create
					= absint( $_POST['kleingarten_batch_create_plots_num'] );
				if ( $plots_to_create > 500 ) {
					$plots_to_create = 500;
				}

				// ... if a starting number was submitted limit it to 499 and make it 1 if was submitted smaller than 0:
				$starting_from = 1;
				if ( isset( $_POST['kleingarten_batch_create_plots_starting_from'] )
				     && $_POST['kleingarten_batch_create_plots_starting_from']
				        > $starting_from ) {

					$starting_from
						= absint( $_POST['kleingarten_batch_create_plots_starting_from'] );

				}

				// ... finally create plots as desired:
				//$plot_ids      = array();
				$error_counter = 0;
				for (
					$i = $starting_from;
					$i <= $plots_to_create + $starting_from - 1; $i ++
				) {

					$prefix = '';
					if ( isset( $_POST['kleingarten_batch_create_plots_prefix'] ) ) {
						$prefix
							= sanitize_textarea_field( wp_unslash( $_POST['kleingarten_batch_create_plots_prefix'] ) );
					}
					$suffix = '';
					if ( isset( $_POST['kleingarten_batch_create_plots_suffix'] ) ) {
						$suffix
							= sanitize_textarea_field( wp_unslash( $_POST['kleingarten_batch_create_plots_suffix'] ) );
					}

					// Create new plot:
					$new_plot = Kleingarten_Plot::create_new( $prefix . ' ' . $i
					                                          . ' ' . $suffix );

					// If creating the plot was successful...
					if ( ! is_wp_error( $new_plot ) ) {

						$new_plot_id = $new_plot->get_ID();

						// Create meters if requested:
						if ( isset( $_POST['kleingarten_batch_create_plots_add_meters'] )
						     && count( $available_meters ) > 0 ) {

							foreach ( $available_meters as $available_meter ) {

								// Create new meter:
                                /*
								$postarr      = array(
									'post_type'   => 'kleingarten_meter',
									'post_title'  => $available_meter . ' '
									                 . $prefix . ' ' . $i . ' '
									                 . $suffix,
									'post_status' => 'publish',
									'post_author' => get_current_user_id(),
								);
								$new_meter_id = wp_insert_post( $postarr );
                                */
								$new_meter_id = Kleingarten_Meter::create_new( $available_meter . ' ' . $prefix . ' ' . $i . ' ' . $suffix );

								// Set new meter's unit:
								update_post_meta( $new_meter_id,
									'kleingarten_meter_unit',
									$available_meter );

								// Assign meter to plot:
								$meta_id = add_post_meta( $new_plot_id,
									'kleingarten_meter_assignment',
									absint( $new_meter_id ) );
								if ( is_bool( $meta_id )
								     && $meta_id === false ) {
									$error_counter ++;
								}

							}

						}

						// ... if creating plot was not sucessful:
					} else {
						$error_counter ++;
					}

				}

				// If there were any errors print a message:
				if ( $error_counter > 0 ) {
					$errors->add( 'kleingarten-could-not-create-plot-the-batch-way',
						__( 'Could not create plot.', 'kleingarten' ) );
				}

				// Print a message if no number of plots to create was submitted:
			} else {
				$errors->add( 'kleingarten-could-not-create-plot-the-batch-way-because-of-missing-count',
					__( 'You have to create at least one plot.',
						'kleingarten' ) );
			}

			// Finally redirect back to form and add errors to $_GET if there are any
			if ( isset( $_POST['_wp_http_referer'] ) ) {

				if ( $errors->has_errors() ) {
					$success = false;
				} else {
					$success = true;
				}

				$url = sanitize_url( wp_unslash( $_POST['_wp_http_referer'] ) );
				wp_redirect(

					esc_url_raw(
						add_query_arg(
							array(
								'kleingarten_batch_create_plots_errors'    => $errors,
								'kleingarten_batch_create_plots_success'   => $success,
								'kleingarten_batch_create_plots_get_nonce' => wp_create_nonce( 'kleingarten_batch_create_plots_get_nonce_action' ),
							),
							//home_url( $url )
							$url
						)
					)
				);

			}

			exit;
		} else {
			wp_die( esc_html__( 'Are you trying something nasty here?',
				'kleingarten' ) );
		}

	}

	function batch_create_meter_reading_submission_tokens_callback() {

		$errors = new WP_Error();

		require_once ABSPATH . 'wp-admin/includes/file.php';
		global $wp_filesystem;

		ob_start();

		// Pre-defined methods to write CSV file. Keep empty to let WordPress chose a proper method:
		$method = '';

		// In case we do not have proper file permissions to write our CSV file, WordPress will give
		// us a form to provide FTP credentials. This URL defines were the form will be sent to:
		$url = wp_nonce_url( admin_url( 'tools.php?page=kleingarten_tools' ),
			'kleingarten' );

		// An array to build our CSV file:
		$lines = array();

		// The upload directory we want to save our CSV file in:
		$wp_upload_dir = wp_upload_dir();

		// How we name our new CSV file:
		$csv_filename = 'tokens_' . time() . '_temp.csv';

		// Get file system credentials . Print a FTP credentials form in case of missing file permissionss:
		if ( false === ( $creds = request_filesystem_credentials( $url, $method,
				false, false ) ) ) {

			// If we get here, then we don't have credentials yet,
			// but have just produced a form for the user to fill in,
			// so stop processing for now.

			// If we have sufficient file permissions to setup filesystem abstraction class, go on:
		} else {

			// Now we have some credentials, so try to get the wp_filesystem running
			if ( ! WP_Filesystem( $creds, $wp_upload_dir['basedir'] ) ) {

				// Our credentials were no good, ask the user for them again:
				request_filesystem_credentials( $url, $method, true,
					false );

				// Finally, if setting up filesystem class ($wp_filesystem) succeeded, go on:
			} else {

				// Check the nonce we generate in "_response" function.
				if ( isset( $_GET['kleingarten_batch_create_meter_reading_submission_tokens_plots_get_nonce'] )
				     && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['kleingarten_batch_create_meter_reading_submission_tokens_plots_get_nonce'] ) ),
						'kleingarten_batch_create_meter_reading_submission_tokens_plots_get_nonce_action' ) ) {

					// If "Create Tokens" button was clicked...
					if ( isset( $_GET['kleingarten_batch_create_meter_reading_submission_tokens_success'] ) ) {

						// ... list all available meters:
                        $available_meters = new Kleingarten_Meters();

						// ... if we fount some meters:
						if ( $available_meters->get_meters_num() > 0 ) {

							require_once ABSPATH . 'wp-admin/includes/file.php';
							global $wp_filesystem;

							// Get the upload directory:
							$wp_upload_dir = wp_upload_dir();

							// Build the first line of our CSV file:
							$fields = array(
								esc_html__( 'Plot', 'kleingarten' ),
								//esc_html__( 'Tenant', 'kleingarten' ),
								esc_html__( 'Meter', 'kleingarten' ),
								esc_html__( 'Token', 'kleingarten' ),
								esc_html__( 'Expires on', 'kleingarten' )
							);
							$file = implode( ';', $fields ) . "\n";

							// Create a token for each meter we found:
							$error_counter = 0;
							foreach ( $available_meters->get_meter_IDs() as $available_meter_ID ) {

								$meter = new Kleingarten_Meter( $available_meter_ID );

								// Create a token...
                                $token_mid = $meter->create_token();
                                $token = $meter->get_token_details( $token_mid );

								// If token could not be saved successfully...
								//if ( ! $token_id ) {
                                if ( ! $token ) {

									// ... note that error:
									$error_counter ++;

									// ... but if there was no error...
								} else {

									// ... find the plots the current meter is assigned to:
									$values = array();
                                    $plots = $meter->get_meter_assignments();

									// ... and add a line for each plot to our CSV file:
									foreach ( $plots as $plot ) {
                                        $plot = new Kleingarten_Plot( $plot );
										$values['plot'] = $plot->get_title();
										$values['meter'] = esc_html( preg_replace( "/\r|\n/",
											"", $meter->get_title() ) );
										$values['token'] = $token['token'];
										$values['expiry'] = $token['token_expiry_date'];
										$lines[] = $values;
									}

								}

							}

							// If any errors occurred add an error message:
							if ( $error_counter > 0 ) {
								$errors->add( 'kleingarten-could-not-create-token-the-batch-way',
									__( 'Could not create tokens.',
										'kleingarten' ) );
							}

							// Build a ;-seperated string from the lines we gathered:
							foreach ( $lines as $line ) {
								$file .= implode( ';', $line ) . "\n";
							}

							// Try to write the CSV file and add an errow in case we fail:
							if ( ! $wp_filesystem->put_contents( trailingslashit( $wp_upload_dir['path'] )
							                                     . $csv_filename,
								$file, FS_CHMOD_FILE ) ) {
								$errors->add( 'kleingarten-could-not-write-csv-file',
									__( 'Could not write CSV file.',
										'kleingarten' ) );
							}

							// No errors so far? Great, add the CSV file to media library:
							if ( ! $errors->has_errors() ) {

								$url_to_temp_csv_file
									= trailingslashit( $wp_upload_dir['url'] )
									  . $csv_filename;

								// Prepare an array of post data for the attachment:
								$attachment = array(
									'name'     => 'tokens_' . time() . '.csv',
									'tmp_name' => download_url( $url_to_temp_csv_file ),
								);

								// Insert the new CSV attachment to make it available in media library.
								// This will create a new file with proper name including timestamp.
								// This new file will be deleted if attachment is removed from media library.
								$attach_id
									= media_handle_sideload( $attachment );

								// Delete the temp file as it is no longer needed:
								$wp_filesystem->delete( trailingslashit( $wp_upload_dir['path'] )
								                        . $csv_filename );

								if ( ! is_wp_error( $attach_id ) ) {
									$success = true;
								} else {
									$errors->add( 'kleingarten-could-add-csv-to-media-library',
										__( 'Could not add CSV file to media library.',
											'kleingarten' ) );
								}

							}

							// Print a message if there are no meters to create tokens for:
						} else {
							$errors->add( 'kleingarten-could-not-create-token-the-batch-way',
								__( 'No meters found.', 'kleingarten' ) );
						}

					}

				}

				// Print errors if there occurred any:
				if ( isset( $errors ) ) {
					if ( $errors->has_errors() ) {
						foreach ( $errors->errors as $error ) {
							$this->print_message( $error[0] );
						}
					} elseif ( isset( $success ) && $success == true ) {
						$this->print_message( esc_html( __( 'Tokens created successfully.',
							'kleingarten' ) ), 'success' );
					}
				}

				if ( isset( $attach_id ) && ! is_wp_error( $attach_id ) ) {
					echo '<p>';
					echo esc_html__( 'A CSV file with all created tokens has been created',
							'kleingarten' ) . ': ' . '<a href="'
					     . esc_url( wp_get_attachment_url( $attach_id ) ) . '">'
					     . esc_html__( 'Download', 'kleingarten' ) . '</a>';
					echo '</p><p>';
					echo esc_html__( 'The file has been added to media library, too. Please download the file immediately and permanently delete it from media library afterwards.',
						'kleingarten' );
					echo '</p>';
				}

				?>
                <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
                      method="post">
                    <input type="hidden" name="action"
                           value="kleingarten_batch_create_meter_reading_submission_tokens">
					<?php wp_nonce_field( 'kleingarten_batch_create_meter_reading_submission_tokens_nonce_action',
						'batch_create_meter_reading_submission_tokens_nonce' ); ?>
                    <p class="submit"><input type="submit"
                                             name="kleingarten_batch_create_meter_reading_submission_tokens_submit"
                                             id="kleingarten_batch_create_meter_reading_submission_tokens_submit"
                                             class="button button-primary"
                                             value="<?php esc_html_e( 'Create Tokens',
						                         'kleingarten' ); ?>"></p>
                </form>
				<?php

			}

		}

		return ob_get_clean();

	}

	/**
	 * Returns a list of plots a meter is assigned to.
	 *
	 * @param $meter_ID
	 *
	 * @return array
	 * @sine 1.1.2
	 */
	private function get_meter_assignments( $meter_ID ) {

		// List all plots which the given meter is assigned to:
		$args                      = array(
			'post_type'      => 'kleingarten_plot',
			'meta_key'       => 'kleingarten_meter_assignment',
			'meta_value'     => strval( $meter_ID ),
			'posts_per_page' => - 1,
		);
		$plots_with_meter_assigned = get_posts( $args );

		$plot_IDs = array();
		if ( is_array( $plots_with_meter_assigned ) ) {
			foreach ( $plots_with_meter_assigned as $plot ) {
				$plot_IDs[] = $plot->ID;
			}

		} else {
			$plot_IDs[] = $plots_with_meter_assigned->ID;
		}

		return $plot_IDs;

	}

	/**
	 * Handle form submission of batch create tokens form.
	 *
	 * @sine 1.1.2
	 */
	function batch_create_meter_reading_submission_tokens_response() {

		// No nonce, no further action...
		if ( isset( $_POST['batch_create_meter_reading_submission_tokens_nonce'] )
		     && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['batch_create_meter_reading_submission_tokens_nonce'] ) ),
				'kleingarten_batch_create_meter_reading_submission_tokens_nonce_action' ) ) {

			// Redirect back to form and add errors to $_GET if there are any
			if ( isset( $_POST['_wp_http_referer'] ) ) {

				$url = sanitize_url( wp_unslash( $_POST['_wp_http_referer'] ) );
				wp_redirect(
					esc_url_raw(
						add_query_arg( array(
							'kleingarten_batch_create_meter_reading_submission_tokens_success'         => 'true',
							'kleingarten_batch_create_meter_reading_submission_tokens_plots_get_nonce' => wp_create_nonce( 'kleingarten_batch_create_meter_reading_submission_tokens_plots_get_nonce_action' ),
						),
							//home_url( $url )
							$url
						)
					)
				);
			}
			exit;
		} else {
			wp_die( esc_html__( 'Are you trying something nasty here?',
				'kleingarten' ) );
		}

	}

	/**
	 * Print meter reading import form.
	 *
	 * @sine 1.1.4
	 */
	function import_meter_readings_callback() {

		$html = '';

		// Check is there is a temporary upload directory.
		// If not we stop right here:
		$temp_dir = get_temp_dir();
		if ( $temp_dir == '' || ! wp_is_writable( $temp_dir ) ) {
			return '<p>'
			       . esc_html( __( 'You have not defined a directory for temporary file uploads. You cannot use this function.',
					'kleingarten' ) ) . '</p>';
		}

		ob_start();

		// If we the form was submitted and now we got back some results from the form handler method ("_response"), check the NONCE first:
		if ( isset( $_GET['kleingarten_import_readings_get_nonce'] )
		     && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['kleingarten_import_readings_get_nonce'] ) ),
				'kleingarten_import_readings_get_nonce_action' ) ) {

			// If there were errors on processing to form submission, print them:
			if ( isset( $_GET['kleingarten_import_readings_errors']['errors'] ) ) {

				// This sufficiently unslashed and sanitized:
				$kleingarten_batch_create_plots_errors_unslashed
					= map_deep( $_GET['kleingarten_import_readings_errors']['errors'],
					'wp_unslash' );
				$kleingarten_batch_create_plots_errors_sanitized
					= map_deep( $kleingarten_batch_create_plots_errors_unslashed,
					'sanitize_text_field' );
				foreach (
					$kleingarten_batch_create_plots_errors_sanitized as $error
				) {
					$this->print_message( $error[0] );
				}

			}

			// If readings were imported successfully, print a success message:
			if ( isset( $_GET['kleingarten_import_readings_success'] )
			     && $_GET['kleingarten_import_readings_success']
			        == true ) {
				$this->print_message( esc_html( __( 'Readings imported successfully.',
					'kleingarten' ) ), 'success' );
			}

		}

		/***********************************************************************
		 *  ATTENTION:
		 *  WordPress will remove the enctype attribute from the form tag. To
		 *  add it anyway there are to lines in admin.js:
		 *
		 *  jQuery('#kleingarten_import_meter_readings_form').attr('enctype','multipart/form-data');
		 *  jQuery('#kleingarten_import_meter_readings_form').attr('encoding', 'multipart/form-data');
		 *
		 *  So do not remove those lines and activate JS!
		 **********************************************************************/

		// Print the form to import readings from CSV file:
		?>
        <form id="kleingarten_import_meter_readings_form"
              action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
              method="post" enctype="multipart/form-data">
            <p>
                <input type="file" accept=".csv"
                       name="kleingarten_import_meter_readings_file">
            </p>
            <p>
                <input type="hidden" name="action"
                       value="kleingarten_import_meter_readings">
				<?php wp_nonce_field( 'kleingarten_import_readings_nonce_action',
					'kleingarten_import_readings_nonce' ); ?>
                <input type="submit" class="button button-primary"
                       value="<?php esc_html_e( 'Import Readings',
					       'kleingarten' ); ?>">
            </p>
        </form>
        <p>
			<?php
			// Build a template to help preparing a CSV file:
			global $wpdb;
			$all_meters
				= $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s and post_status = 'publish'",
				'kleingarten_meter' ), ARRAY_A );
			?>
        <details>
            <summary><?php esc_html_e( 'Prepare CSV file',
					'kleingarten' ) ?></summary>
            <p><?php esc_html_e( 'You can copy these to prepare your CSV file. Remove the headines before upload.',
					'kleingarten' ); ?></p>
			<?php
			echo '<table>';
			echo '<thead>';
			echo '<tr>';
			echo '<th>' . esc_html__( 'ID', 'kleingarten' ) . '</th>';
			echo '<th>' . esc_html__( 'Reading', 'kleingarten' ) . '</th>';
			echo '<th>' . esc_html__( 'Date', 'kleingarten' ) . '</th>';
			echo '<th>' . esc_html__( 'Meter No', 'kleingarten' ) . '</th>';
			echo '<th>' . esc_html__( 'Title', 'kleingarten' ) . '</th>';
			echo '</thead>';
			echo '<tbody>';
			foreach ( $all_meters as $meter ) {
				echo '<tr>';
				echo '<td>' . esc_html( $meter['ID'] ) . '</td>';
				echo '<td>?</td>';
				echo '<td>?</td>';
				echo '<td>?</td>';
				echo '<td>' . esc_html( $meter['post_title'] ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody>';
			echo '</table>';
			?>
        </details>
        </p>
		<?php

		return ob_get_clean();

	}

	/**
	 * Handle form submission of meter reading import.
	 *
	 * @sine 1.1.4
	 */
	function import_meter_readings_response() {

		$errors = new WP_Error();

		// Check NONCE:
		if ( isset( $_POST['kleingarten_import_readings_nonce'] )
		     && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['kleingarten_import_readings_nonce'] ) ),
				'kleingarten_import_readings_nonce_action' ) ) {

			// If there is no writeable temp directory configured, stop right here:
			$temp_dir = get_temp_dir();
			if ( $temp_dir == '' || ! wp_is_writable( $temp_dir ) ) {
				$errors->add( 'kleingarten_import_readings_no_writeable_temp_upload_dir',
					__( 'Could not find temporary upload directory.',
						'kleingarten' ) );
			}

			// If no file uploaded, stop here:
			if ( empty( $_FILES ) ) {
				$errors->add( 'kleingarten_import_readings_no_file',
					__( 'No file uploaded.',
						'kleingarten' ) );
			}

			// If file is not an CSV, stop it:
			$csv_mimes = array(
				'text/csv',
				'text/plain',
				'application/csv',
				'text/comma-separated-values',
				'application/excel',
				'application/vnd.ms-excel',
				'application/vnd.msexcel',
				'text/anytext',
				'application/octet-stream',
				'application/txt',
			);
			if ( isset( $_FILES['kleingarten_import_meter_readings_file']['type'] ) ) {
				if ( ! in_array( $_FILES['kleingarten_import_meter_readings_file']['type'],
					$csv_mimes ) ) {
					$errors->add( 'kleingarten_import_readings_file_is_not_csv',
						__( 'This is not a CSV file.',
							'kleingarten' ) );
				}
			}

			// No errors so far? Go on. Read the CSV file and shrink it into an array:
			if ( ! $errors->has_errors() ) {

				if ( isset( $_FILES['kleingarten_import_meter_readings_file']['tmp_name'] ) ) {

					$filepath
						= sanitize_text_field( $_FILES['kleingarten_import_meter_readings_file']['tmp_name'] );

					// If opening CSV file succeeds...
					$readings = array();
					$row      = 1;
					if ( ( $csv_file_handle = fopen( $filepath, 'r' ) )
					     !== false ) {

						// ... read it row by row:
						while ( ( $reading_from_csv = fgetcsv( $csv_file_handle,
								null,
								';' ) ) !== false ) {

							// Read the current row column by column and put into array:
							$num = count( $reading_from_csv );
							for ( $c = 0; $c < $num; $c ++ ) {
								switch ( $c ) {
									// First column: Meter ID (WordPress Post ID):
									case 0:
										$readings[ $row ]['meter_id']
											= $reading_from_csv[ $c ];
										break;
									// Second column: Reading value:
									case 1:
										$readings[ $row ]['reading_value']
											= $reading_from_csv[ $c ];
										break;
									// Third column: Reading date:
									case 2:
										$readings[ $row ]['reading_date']
											= $reading_from_csv[ $c ];
										break;
									// Fourth column: Meter no:
									case 3:
										$readings[ $row ]['meter_no']
											= $reading_from_csv[ $c ];
										break;
								}
							}
							$row ++;
						}
						fclose( $csv_file_handle );


						// Try to save the readings we found in CSV file:
						foreach ( $readings as $k => $reading ) {

							// Try to save the current reading:
							$add_meter_reading_result
								= $this->add_meter_reading( $reading['meter_id'],
								$reading['reading_value'],
								$reading['reading_date'],
								'csv_import_' . get_current_user_id(),
								$reading['meter_no']
							);

							// If that failed for the current reading, add the error we encountered:
							if ( is_wp_error( $add_meter_reading_result ) ) {
								$add_meter_reading_result->export_to( $errors );
							}

						}

					}

					// ... else, if opening CSV failed, print an error if opening CSV file failed:
				} else {
					$errors->add( 'kleingarten_import_readings_could_not_read_csv_file',
						__( 'Could not open CSV file.',
							'kleingarten' ) );
				}

			}

			// No errors so far? Set the success flag. The flag indicates that readings
			// were imported successfully. Errors can be added anyway.
			$success = false;
			if ( ! $errors->has_errors() ) {
				$success = true;
			}

			// Delete the CSV file. We do that down here to not affect success flag.
			// CSV file was processed successfully any. So we were successful AND hat
			// an error:
			if ( ! empty( $filepath ) ) {
				if ( ! unlink( $filepath ) ) {
					$errors->add( 'kleingarten_import_readings_could_not_remove_csv_file',
						__( 'Could not delete CSV file. Please delete it manually.',
							'kleingarten' ) );
				}
			}

			$o = count( $errors->get_error_messages() );
			if ( $o >= 20 ) {
				$errors = new WP_Error();
				$errors->add( 'kleingarten_import_readings_too_many_errors',
					__( 'Too many errors to print them all. Something is wrong with your CSV file.',
						'kleingarten' ) . '(' . $o . ')' );
			}

		}

		// And finally go back to our form. Send errors, success flag and a nonce, too:
		$url = sanitize_url( wp_unslash( $_POST['_wp_http_referer'] ) );
		wp_redirect(
			esc_url_raw(
				add_query_arg(
					array(
						'kleingarten_import_readings_errors'    => $errors,
						'kleingarten_import_readings_success'   => $success,
						'kleingarten_import_readings_get_nonce' => wp_create_nonce( 'kleingarten_import_readings_get_nonce_action' ),
						// Pass the readings back e.g. to display them above the form:
						//'kleingarten_import_readings_readings'  => $readings,
					),
					//home_url( $url )
					$url
				)
			)
		);

		exit;

	}

	private function add_meter_reading(
		$meter_id, $reading_value, $reading_date, $submitted_by, $meter_no = ''
	) {

		$errors = new WP_Error();

		// Check if meter ID is empty:
		if ( empty( $meter_id ) ) {
			$errors->add( 'kleingarten_add_meter_reading_empty_data_'
			              . wp_rand(),
				__( 'Empty meter ID.', 'kleingarten' ) );
		}

		// Check if given meter ID exists:
		if ( ! get_post_status( $meter_id ) ) {
			if ( ! empty( $meter_id ) ) {
				$errors->add( 'kleingarten_add_meter_reading_meter_does_not_exist_'
				              . $meter_id,
					__( 'Meter does not exist.', 'kleingarten' ) . ' ('
					. $meter_id . ')' );
			} else {
				$errors->add( 'kleingarten_add_meter_reading_meter_does_not_exist_'
				              . $meter_id,
					__( 'Meter does not exist.',
						'kleingarten' ) );
			}
		}

		// Sanitize the data:
		$sanitized_data['date']
			                     = strtotime( sanitize_text_field( wp_unslash( $reading_date ) ) );
		$sanitized_data['value'] = absint( wp_unslash( $reading_value ) );
		$sanitized_data['by']
			                     = sanitize_text_field( wp_unslash( $submitted_by ) );
		$sanitized_data['meter-no']
			                     = sanitize_text_field( wp_unslash( $meter_no ) );

		// More validation:
		$existing_readings = get_post_meta( $meter_id,
			'kleingarten_meter_reading' );
		if ( $existing_readings ) {

			// Check if we already have a reading for this date:
			foreach ( $existing_readings as $existing_reading ) {
				if ( $existing_reading['date'] === $sanitized_data['date'] ) {

					$errors->add( 'kleingarten_add_meter_reading_date_already_has_reading_'
					              . $meter_id,
						__( 'A meter reading already exists for this date.',
							'kleingarten' ) . ' (' . $meter_id . ')' );
					break;

				}
			}

			// Determine if date is in the future:
			$reading_date = $sanitized_data['date'];
			$today        = strtotime( gmdate( 'Y-m-d' ) );
			if ( $reading_date > $today ) {
				$errors->add( 'kleingarten_add_meter_reading_date_in_future_'
				              . $meter_id,
					__( 'Cannot save a reading for a date in the future.',
						'kleingarten' ) . ' ' . gmdate( 'Y-m-d',
						$reading_date ) );
			}

		}

		// And finally save the reading if valid:
		if ( ! $errors->has_errors() ) {

			// Save it as post meta:
			$meta_id = add_post_meta( $meter_id, 'kleingarten_meter_reading',
				$sanitized_data );

			// Return errors on failure...
			if ( is_bool( $meta_id ) && $meta_id === false ) {
				if ( ! empty( $meter_id ) ) {
					$errors->add( 'kleingarten_add_meter_reading_could_not_save_'
					              . $meter_id,
						__( 'Something went wrong. Reading could not be saved.',
							'kleingarten' ) . ' (' . $meter_id . ')' );
				} else {
					$errors->add( 'kleingarten_add_meter_reading_could_not_save_'
					              . wp_rand(),
						__( 'Something went wrong. Reading could not be saved.',
							'kleingarten' ) );
				}

				return $errors;
				// ... and true on success:
			} else {
				//return true;
				return $meta_id;
			}

		} else {
			return $errors;
		}

	}

}