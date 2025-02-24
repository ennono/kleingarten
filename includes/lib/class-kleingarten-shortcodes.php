<?php

/**
 * Userfields file.
 *
 * @package Kleingarten/Includes
 */

//use JetBrains\PhpStorm\NoReturn;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomy functions class.
 */
class Kleingarten_Shortcodes {

	/**
     * A handle for all our plots
     *
	 * @var Kleingarten_Plots
	 */
	private $plots;

	/**
	 * Shortcodes constructor.
	 *
	 */
	public function __construct() {

        $this->plots = new Kleingarten_Plots();

		add_shortcode( 'kleingarten_member_profile',
			array( $this, 'kleingarten_member_profile_callback' ) );
		add_shortcode( 'kleingarten_member_profile_settings',
			array( $this, 'kleingarten_member_profile_settings_callback' ) );
		add_shortcode( 'kleingarten_login_form',
			array( $this, 'kleingarten_login_form_callback' ) );
		add_shortcode( 'kleingarten_register_form',
			array( $this, 'kleingarten_register_form_callback' ) );
		add_shortcode( 'kleingarten_likes',
			array( $this, 'kleingarten_likes_callback' ) );
		add_shortcode( 'kleingarten_number_of_private_posts',
			array( $this, 'kleingarten_number_of_private_posts_callback' ) );
		add_shortcode( 'kleingarten_private_posts',
			array( $this, 'kleingarten_private_posts_callback' ) );
		add_shortcode( 'kleingarten_submit_meter_reading_form',
			array( $this, 'kleingarten_submit_meter_reading_form_callback' ) );

		add_action( 'wp_login_failed', array( $this, 'handle_failed_login' ) );

		add_action( 'wp_ajax_kleingarten_like_post',
			array( $this, 'kleingarten_like_post_ajax_callback' ) );
		add_action( 'wp_ajax_nopriv_kleingarten_like_post',
			array( $this, 'kleingarten_like_post_ajax_callback' ) );
		add_action( 'wp_ajax_kleingarten_show_all_likes',
			array( $this, 'kleingarten_show_all_likes_ajax_callback' ) );
		add_action( 'wp_ajax_nopriv_kleingarten_show_all_likes',
			array( $this, 'kleingarten_show_all_likes_ajax_callback' ) );

		add_action( 'admin_post_kleingarten_member_profile_settings', array(
			$this,
			'handle_kleingarten_member_profile_settings_callback'
		) );

		add_shortcode( 'kleingarten_my_plot',
			array( $this, 'kleingarten_my_plot_callback' ) );

	}

	/**
	 * Callback for shortcode kleingarten_login_form. Displays Login Form.
	 *
	 * @param   array  $atts  Parameters
	 *
	 * @return string  HTML output
	 */
	public function kleingarten_login_form_callback( $atts ) {

		global $wp;
		$current_url = home_url( add_query_arg( array(), $wp->request ) );

		// Extract shortcode attributes
		$atts = shortcode_atts( array(
			'register_page'   => '',
			'redirect_to'     => $current_url,
			'logged_out_page' => $current_url,
		),
			$atts
		);

		$user = get_user_by( 'ID', get_current_user_id() );

		ob_start();
		switch ( is_user_logged_in() ) {

			case false:
				if ( isset ( $_GET ["login"] )
				     && $_GET ["login"] == 'failed' ) {
					?><p><?php echo esc_html( __( 'Sorry, login failed.',
						'kleingarten' ) ); ?></p><?php
				}
				if ( isset ( $_GET ["loggedout"] )
				     && $_GET ["loggedout"] == '1' ) {
					?>
                    <p><?php echo esc_html( __( 'You have been logged out. See you soon!',
						'kleingarten' ) ); ?></p><?php
				}
				?>
                <form name="loginform" id="loginform"
                      action="<?php echo esc_attr( wp_login_url() ); ?>"
                      method="post">
                    <p>
                        <label for="user_login"><?php echo esc_html( __( 'Username',
								'kleingarten' ) ); ?>:<br></label><input
                                id="user_login"
                                type="text"
                                size="20" value=""
                                name="log"></p>
                    <p>
                        <label for="user_pass"><?php echo esc_html( __( 'Password',
								'kleingarten' ) ); ?>:<br></label><input
                                id="user_pass"
                                type="password"
                                size="20" value=""
                                name="pwd"></p>
                    <p><input id="rememberme" type="checkbox" value="forever"
                              name="rememberme"><label
                                for="rememberme"><?php echo esc_html( __( 'Remember me',
								'kleingarten' ) ); ?></label>
                    </p>
                    <p><input id="wp-submit" type="submit" value="Login"
                              name="wp-submit"></p>
                    <input type="hidden"
                           value="<?php echo esc_attr( $atts['redirect_to'] ); ?>"
                           name="redirect_to">
                </form>
				<?php
				if ( $atts['register_page'] != '' ) {
					echo '<p>' . esc_html( __( 'Not registered yet?',
							'kleingarten' ) ) . ' <a href="'
					     . esc_attr( $atts['register_page'] ) . '">'
					     . esc_html( __( 'Register now!', 'kleingarten' ) )
					     . '</a></p>';
				}
				break;

			case true:
				$user_profile_page_id = get_option( 'kleingarten_login_page' );
				$args = array( 'loggedout' => '1' );
				if ( $user_profile_page_id == 0 ) {
					// translators: Placeholder is replaced by username.
					echo '<p>' . esc_html( sprintf( __( 'Logged in as %s',
							'kleingarten' ), $user->user_login ) );
				} else {
					echo '<p>' . esc_html( __( 'Logged in as', 'kleingarten' ) )
					     . ' <a href="'
					     . esc_url( get_permalink( $user_profile_page_id ) )
					     . '">' . esc_html( $user->user_login ) . '</a>';
				}
				echo '.&nbsp;<a href="'
				     . esc_url( wp_logout_url( add_query_arg( $args,
						$atts['logged_out_page'] ) ) ) . '">'
				     . esc_html( __( 'Logout', 'kleingarten' ) ) . '</a></p>';
				break;

		}

		return ob_get_clean();

	}

	/**
	 * Handle failed login attempts
	 *
	 * @return void
	 */
	public function handle_failed_login() {

		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {

			$referer = sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) );

			// If there's a valid referrer, and it's not the default log-in screen
			if ( ! empty( $referer ) && ! str_contains( $referer, 'wp-login' )
			     && ! str_contains( $referer, 'wp-admin' )
			) {
				$referer = str_replace( '?login=failed', '',
					$referer );    // To prevent multiple failed parameteres in URL in case of multiple failed login attempts
				$referer = str_replace( '?loggedout=1', '',
					$referer );
				wp_redirect( $referer
				             . '?login=failed' );  // let's append some information (login=failed) to the URL for the theme to use
				exit;
			}
		}

	}

	/**
	 * Callback for shortcode kleingarten_member_profil. Displays member profile.
	 *
	 * @return string  HTML output
	 */
	public function kleingarten_member_profile_callback() {

		$html = '';

		ob_start();
		switch ( is_user_logged_in() ) {

			case true:

				$gardener = new Kleingarten_Gardener( get_current_user_id() );

				//$user = get_user_by( 'ID', get_current_user_id() );
				//$positions = get_the_author_meta( 'positions', $gardener->user_ID );
                //$positions = $gardener->positions;
				//$plot = get_the_author_meta( 'plot', $user->ID );
                //$plot = $gardener->plot;

				?>

                <h2 class="kleingarten-member-profile-section"><?php echo esc_html( __( 'Your Member Profile',
						'kleingarten' ) ); ?></h2>
                <p><?php echo esc_html( __( 'The following data is stored under your user account on this website.',
						'kleingarten' ) ); ?></p>
                <table>
                    <tr>
                        <th>
							<?php echo esc_html( __( 'Username',
								'kleingarten' ) ); ?>
                        </th>
                        <td>
							<?php echo esc_html( $gardener->user_login ); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>
							<?php echo esc_html( __( 'E-Mail',
								'kleingarten' ) ); ?>
                        </th>
                        <td>
							<?php echo esc_html( $gardener->email ); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>
							<?php echo esc_html( __( 'Firstname',
								'kleingarten' ) ); ?>
                        </th>
                        <td>
							<?php echo esc_html( $gardener->first_name ); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>
							<?php echo esc_html( __( 'Lastname',
								'kleingarten' ) ); ?>
                        </th>
                        <td>
							<?php echo esc_html( $gardener->last_name ); ?>
                        </td>
                    </tr>
					<?php
					?>
                    <tr>
                        <th><?php echo esc_html( __( 'Positions',
								'kleingarten' ) ) ?></th>
                        <td><?php
							if ( is_array( $gardener->positions ) ) {

								foreach ( $gardener->positions as $i => $position ) {
									echo esc_html( $position );
									if ( count( $gardener->positions ) > 1
									     && $i < count( $gardener->positions ) - 1
									) {
										echo ', ';
									}
								}

							} else {
								echo esc_html( __( 'You do not have any positions in this club.',
									'kleingarten' ) );
							}
							?></td>
                    </tr><?php
					?>
					<?php
					?>
                    <tr>
                        <th><?php echo esc_html( __( 'Plot',
								'kleingarten' ) ) ?></th>
                        <td><?php
							if ( $gardener->plot ) {
								echo esc_html( get_the_title( $gardener->plot ) );
							} else {
								echo esc_html( __( 'No plot is assign to you.',
									'kleingarten' ) );
							}
							?></td>
                    </tr><?php
					?>
                </table>
				<?php
				break;

			default:
				// Do nothing.
				break;
		}
		$html .= ob_get_clean();

		return $html;

	}

	/**
	 * Callback for shortcode kleingarten_member_profile_settings. Displays member settings.
	 *
	 * @return string  HTML output
	 */
	public function kleingarten_member_profile_settings_callback() {

		//global $wp;

		$html = '';

		if ( is_user_logged_in() ) {

			//$user_id = get_current_user_id();

			$gardener = new Kleingarten_Gardener( get_current_user_id() );

			//$send_email_notifications = get_user_meta( $user_id,
			//	'send_email_notifications', true );

			ob_start();

			?>
            <h2 class="kleingarten-member-profile-settings-section"><?php echo esc_html( __( 'Settings',
					'kleingarten' ) ); ?></h2>
            <p><?php echo esc_html( __( 'Your member profile contains these settings.',
					'kleingarten' ) ); ?></p>
			<?php
			?>
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
                  method="post">
                <input type="hidden" name="action"
                       value="kleingarten_member_profile_settings">
                <p>
                    <label for="send-email-notifications" class="checkbox">
						<?php
						//if ( $send_email_notifications == 1 ) {
                        if ( $gardener->receives_notification_mails() ) {
							?>
                            <input
                                    type="checkbox"
                                    name="send_email_notifications"
                                    value="1"
                                    id="send_email_notifications"
                                    checked
                            />
							<?php
						} else {
							?>
                            <input
                                    type="checkbox"
                                    name="send_email_notifications"
                                    value="1"
                                    id="send_email_notifications"
                            />
							<?php
						}

						echo esc_html( __( 'Receive email notifications.',
							'kleingarten' ) );

						?>
                    </label>
                </p>
                <input type="hidden"
                       value="<?php echo esc_url( get_permalink() ); ?>"
                       name="redirect_url">
                <input
                        type="hidden"
                        name="kleingarten_member_profile_settings_nonce"
                        value="<?php echo esc_attr( wp_create_nonce( 'kleingarten_member_profile_settings_nonce' ) ); ?>"
                >
                <input type="submit"
                       value="<?php echo esc_html( __( 'Save Settings',
					       'kleingarten' ) ); ?>">
            </form>
			<?php

			$html .= ob_get_clean();

		}

		return $html;

	}

	/**
	 * Callback to handle member profile settings form submission.
	 *
	 * @return void  HTML output
	 */
	#[NoReturn] public function handle_kleingarten_member_profile_settings_callback(
	) {

		// Verify nonce or die
		if ( ! isset( $_POST['kleingarten_member_profile_settings_nonce'] )
		     || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['kleingarten_member_profile_settings_nonce'] ) ),
				'kleingarten_member_profile_settings_nonce' )
		) {
			wp_die( esc_html__( 'Are you trying something nasty here?',
				'kleingarten' ) );
		}

		if ( is_user_logged_in() ) {

			//$user_id = get_current_user_id();
			$gardener = new Kleingarten_Gardener( get_current_user_id() );

			if ( isset( $_POST['send_email_notifications'] )
			     && $_POST['send_email_notifications'] == 1 ) {

				//update_user_meta( $user_id, 'send_email_notifications', 1 );
				$gardener->set_notification_mail_receival();

			} else {
				//update_user_meta( $user_id, 'send_email_notifications', 0 );
				$gardener->unset_notification_mail_receival();
			}

		}

		// Redirect after form submission
		if ( isset( $_POST['redirect_url'] ) ) {
			wp_redirect( esc_url( sanitize_url( wp_unslash( $_POST['redirect_url'] ) ) ) );
		}

		exit;

	}

	/**
	 * Callback for shortcode kleingarten_register_form. Displays Registe Form.
	 *
	 * @return string  HTML output
	 */
	public function kleingarten_register_form_callback( $atts ) {

		// Extract shortcode attributes
		$atts = shortcode_atts( array(
			'terms_of_use_url' => 'none',
		),
			$atts
		);

		if ( ! get_option( 'users_can_register' ) ) {
			return __( 'User registration is disabled.', 'kleingarten' );
		}

		$hide_form = false;

		// If registration form submitted create new user:
		if ( isset( $_REQUEST['kleingarten_register_gardener_nonce'] ) ) {

			if ( wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['kleingarten_register_gardener_nonce'] ) ),
				'kleingarten_register_gardener' )
			) {

				// Nonce is matched and valid. Go!

				if ( isset( $_POST["user_email"] ) ) {
					$new_user_data["email"]
						= sanitize_email( wp_unslash( $_POST["user_email"] ) );
				}
				if ( isset( $_POST["user_login"] ) ) {
					$new_user_data["login"]
						= sanitize_user( wp_unslash( $_POST["user_login"] ) );
				}
				if ( isset( $_POST["user_firstname"] ) ) {
					$new_user_data["firstname"]
						= sanitize_text_field( wp_unslash( $_POST["user_firstname"] ) );
				}
				if ( isset( $_POST["user_lastname"] ) ) {
					$new_user_data["lastname"]
						= sanitize_text_field( wp_unslash( $_POST["user_lastname"] ) );
				}
				if ( isset( $_POST["user_plot"] ) ) {
					$new_user_data["plot"] = absint( $_POST["user_plot"] );
				}
				// DO NOT SANITIZE PASSWORD:
				if ( isset( $_POST["user_pw"] )
				     && isset( $_POST["user_pw_confirm"] ) ) {
					$new_user_data["pw"]         = $_POST["user_pw"];
					$new_user_data["pw_confirm"] = $_POST["user_pw_confirm"];
				}

				if ( isset ( $_POST["user_notifications"] ) ) {
					$new_user_data["user_notifications"] = 1;
				}
				if ( isset ( $_POST["user_terms_of_use_accepted"] ) ) {
					$new_user_data["user_terms_of_use_accepted"] = 1;
				} else {
					$new_user_data["user_terms_of_use_accepted"] = 0;
				}

				$user_data_validation
					= $this->validate_user_data( $new_user_data );
				if ( ! is_wp_error( $user_data_validation ) ) {
					//$this->add_gardener( $new_user_data );
                    $gardeners = new Kleingarten_Gardeners( );
				}

			} else {
				$nonce_failed = true;
			}

		}

		$redirect_to = get_home_url();

		$html = '';

		ob_start();
		if ( ! is_user_logged_in() ) {

			?>
            <form name="kleingarten_register_form"
                  id="kleingarten_register_form"
                  action="#kleingarten_register_form" method="post"
                  novalidate="novalidate">
			<?php wp_nonce_field( 'kleingarten_register_gardener',
				'kleingarten_register_gardener_nonce' ); ?>
			<?php

			if ( isset ( $nonce_failed ) ) {
				wp_die( esc_html( __( 'Sorry, seems like something strange going on here.',
					'kleingarten' ) ) );
			}

			// If registration form submitted and if there are any errors...
			// ... print the errors.
			if ( isset ( $_POST['user_email'] )
			     && $user_data_validation
			     && is_wp_error( $user_data_validation )
			) {
				$messages = $user_data_validation->get_error_messages();
				if ( count( $messages ) > 1 ) {
					echo '<p><strong>';
					foreach ( $messages as $message ) {
						echo esc_html( $message ) . '<br>';
					}
					echo '</strong></p>';

				} else {
					echo '<p><strong>' . esc_html( $messages[0] )
					     . '</strong></p>';
				}
			} // But if the form is submitted without any errors print a success message.
            elseif ( isset ( $_POST['user_email'] ) ) {
				echo '<p><strong>'
				     . esc_html( __( 'Registration form submitted. Your account needs to be approved by your club now.',
						'kleingarten' ) ) . '</strong></p>';
				$hide_form = true;
			}

			if ( ! $hide_form ) {

				?>
                <p>
                    <label for="user_login"><?php echo esc_html( __( 'Username',
							'kleingarten' ) ); ?>*</label>
                    <br>
                    <input type="text" name="user_login" id="user_login"
                           value="<?php if ( isset( $_POST["user_login"] ) ) {
						       echo esc_attr( sanitize_text_field( wp_unslash( $_POST["user_login"] ) ) );
					       } ?>" size="25" autocomplete="" required="required">
                </p>
                <p>
                    <label for="user_email"><?php echo esc_html( __( 'E-Mail',
							'kleingarten' ) ); ?>*</label>
                    <br>
                    <input type="email" name="user_email" id="user_email"
                           value="<?php if ( isset( $_POST["user_email"] ) ) {
						       echo esc_attr( sanitize_email( wp_unslash( $_POST["user_email"] ) ) );
					       } ?>" size="25" autocomplete="email"
                           required="required">
                </p>
                <p>
                    <label for="user_firstname"><?php echo esc_html( __( 'Firstname',
							'kleingarten' ) ); ?>*</label>
                    <br>
                    <input type="text" name="user_firstname" id="user_firstname"
                           value="<?php if ( isset( $_POST["user_firstname"] ) ) {
						       echo esc_attr( sanitize_text_field( wp_unslash( $_POST["user_firstname"] ) ) );
					       } ?>" size="25" autocomplete="firstname"
                           required="required">
                </p>
                <p>
                    <label for="user_lastname"><?php echo esc_html( __( 'Lastname',
							'kleingarten' ) ); ?>*</label>
                    <br>
                    <input type="text" name="user_lastname" id="user_lastname"
                           value="<?php if ( isset( $_POST["user_lastname"] ) ) {
						       echo esc_attr( sanitize_text_field( wp_unslash( $_POST["user_lastname"] ) ) );
					       } ?>" size="25" autocomplete="lastname"
                           required="required">
                </p>
                <p>
                    <label for="user_plot"><?php echo esc_html( __( 'Plot',
							'kleingarten' ) ); ?></label>
                    <br>
                    <select name="user_plot" id="user_plot" autocomplete=""
                            required="">
						<?php
						global $wpdb;
						//$available_plots
						//	= $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s and post_status = 'publish'",
						//	'kleingarten_plot' ), ARRAY_A );
                        $available_plot_IDs = $this->plots->get_plot_IDs();
						echo '<option value="">' . esc_html__( 'None',
								'kleingarten' ) . '</option>';
						foreach (
							$available_plot_IDs as $available_plot
						) {
							//if ( $available_plot['ID'] != $plot ) {
							echo '<option value="'
							     . esc_attr( $available_plot ) . '">'
							     . esc_html( get_the_title( $available_plot ) )
							     . '</option>';
							//}
						}
						?>
                    </select>
                </p>
                <p>
                    <label for="user_pw"><?php echo esc_html( __( 'Password',
							'kleingarten' ) ); ?>*</label>
                    <br>
                    <input size="25" name="user_pw" id="user_pw"
                           type="password"/>
                </p>
                <p>
                    <label for="user_pw_confirm"><?php echo esc_html( __( 'Password again',
							'kleingarten' ) ); ?>*</label>
                    <br>
                    <input name="user_pw_confirm" id="user_pw_confirm"
                           type="password" size="25"/>
                </p>
                <p>
                    <input value="1" type="checkbox" id="user_notifications"
                           name="user_notifications" checked/>
                    <label for="user_notifications"><?php echo esc_html( __( 'Send me an E-Mail notification whenever there is something new.',
							'kleingarten' ) ); ?></label>
                </p>
				<?php

				$terms_of_use_url = $atts['terms_of_use_url'];
				if ( $terms_of_use_url != 'none'
				     && wp_http_validate_url( $terms_of_use_url )
				) {

					?>
                    <p>
                        <input value="1" type="checkbox"
                               id="user_terms_of_use_accepted"
                               name="user_terms_of_use_accepted"/>
                        <label for="user_terms_of_use_accepted">
							<?php echo esc_html( __( 'I accept the',
								'kleingarten' ) ); ?>
                            <a target="_blank"
                               href="<?php echo esc_url( $terms_of_use_url ); ?>"><?php echo esc_html( __( 'terms of use',
									'kleingarten' ) ); ?></a>.*
                        </label>
                    </p>
					<?php

				} else {

					?><input type="hidden" value="1"
                             id="user_terms_of_use_accepted"
                             name="user_terms_of_use_accepted"><?php

				}

				?>
                <p id="reg_passmail">
                    <small><?php echo esc_html( __( '* mandatory field',
							'kleingarten' ) ); ?></small></p>
                <input type="hidden" name="redirect_to"
                       value="<?php echo esc_url( $redirect_to ); ?>"/>
                <p class="submit">
                    <input type="submit" name="wp-submit" id="wp-submit"
                           class="button button-primary button-large"
                           value="Registrieren">
                </p>
                </form>
				<?php

			}

		} else {
			echo '<p>'
			     . esc_html( __( 'You are already logged in. No need to register for you..',
					'kleingarten' ) ) . '</p>';
		}

		$html .= ob_get_clean();

		return $html;

	}

	/**
	 * Vadilate user data of new gardener on registration
	 *
	 * @param   array  $user_data  User Data
	 *
	 * @return true|WP_Error  Error Objects|True on success
	 */
	private function validate_user_data( $user_data ) {

		$error = new WP_Error ();

		if ( $user_data["user_terms_of_use_accepted"] !== 1 ) {
			$error->add( 'kleingarten-registration-terms-of-user-not-accepted',
				__( 'Kindly accept the terms of use.', 'kleingarten' ) );
		}

		// Check if user already existst:
		if ( username_exists( $user_data["login"] ) ) {
			$error->add( 'kleingarten-registration-user-exists',
				__( 'Username already taken.', 'kleingarten' ) );
		}

		// Check username is valid:
		if ( ! validate_username( $user_data["login"] ) ) {
			$error->add( 'kleingarten-registration-invalid-username',
				__( 'Invalid username.', 'kleingarten' ) );
		}

		// Check is username is empty:
		if ( $user_data["login"] == '' ) {
			$error->add( 'kleingarten-registration-username-empty',
				__( 'Username empty.', 'kleingarten' ) );
		}

		// Check if email is valid:
		if ( ! is_email( $user_data["email"] ) ) {
			$error->add( 'kleingarten-registration-invalid-email',
				__( 'That is not a valid email address.', 'kleingarten' ) );
		}

		// Check if email is already used:
		if ( email_exists( $user_data["email"] ) ) {
			$error->add( 'kleingarten-registration-email-exists',
				__( 'Email address already in use.', 'kleingarten' ) );
		}

		// Check if password is empty:
		if ( $user_data["pw"] == '' ) {
			$error->add( 'kleingarten-registration-password-empty',
				__( 'Password is empty.', 'kleingarten' ) );
		}

		if ( strlen( $user_data["pw"] ) < 6 ) {
			$error->add( 'kleingarten-registration-password-too-short',
				__( 'Password is too short.', 'kleingarten' ) );
		}

		if ( ! preg_match( "#[0-9]+#", $user_data["pw"] ) ) {
			$error->add( 'kleingarten-registration-password-has-no-number',
				__( 'Password must contain at least one number.',
					'kleingarten' ) );
		}

		if ( ! preg_match( "#[a-zA-Z]+#", $user_data["pw"] ) ) {
			$error->add( 'kleingarten-registration-password-has-no-letter',
				__( 'Password must contain at least one letter.',
					'kleingarten' ) );
		}

		if ( ! preg_match( "@[^\w]@", $user_data["pw"] ) ) {
			$error->add( 'kleingarten-registration-password-has-no-spec-char',
				__( 'Passwords must contain at least one special charachter.',
					'kleingarten' ) );
		}

		if ( $user_data["pw"] != $user_data["pw_confirm"] ) {
			$error->add( 'kleingarten-registration-passwords-not-matching',
				__( 'Passwords do not match.', 'kleingarten' ) );
		}

		if ( $user_data["user_notifications"] < 0
		     || $user_data["user_notifications"] > 1
		) {
			$user_notifications_error_string
				= __( 'Invalid value for user notifications', 'kleingarten' )
				  . ': ' . $user_data["user_notifications"];
			$error->add( 'kleingarten-registration-notify-me-checkbox-invalid-value',
				$user_notifications_error_string );
		}

		if ( $error->has_errors() ) {
			return $error;
		} else {
			return true;
		}

	}

	/**
	 * Add gardeners as WordPress user
	 *
	 * @param   array  $user_data  User Data
	 *
	 * @return void  void
	 */
	private function add_gardener( $user_data ) {

		$user_id = wp_insert_user( array(
				'user_login'      => $user_data["login"],
				'user_pass'       => $user_data["pw"],
				'user_email'      => $user_data["email"],
				'first_name'      => $user_data["firstname"],
				'last_name'       => $user_data["lastname"],
				'user_registered' => gmdate( 'Y-m-d H:i:s' ),
				'role'            => 'kleingarten_pending'
			)
		);

		if ( ! is_wp_error( $user_id ) ) {
			add_user_meta( $user_id, 'plot', $user_data["plot"] );
			add_user_meta( $user_id, 'send_email_notifications',
				$user_data["user_notifications"] );
		}

		$this->send_welcome_email( $user_id );

	}

	/**
	 * Send welcome email
	 *
	 * @param   int  $user_id  User ID
	 *
	 * @return void
	 */
	private function send_welcome_email( $user_id ) {

		if ( get_option( 'kleingarten_send_account_registration_notification' ) ) {

			$site_name   = get_bloginfo( 'name' );
			$admin_email = get_bloginfo( 'admin_email' );
			$user_info   = get_userdata( $user_id );

			$to = $user_info->user_email;

			$headers[] = 'From: ' . $site_name . ' <' . $admin_email . '>';
			$headers[] = 'Content-Type: text/html';
			$headers[] = 'charset=UTF-8';

			$subject
				= sprintf( get_option( 'kleingarten_account_registration_notification_subject' ),
				$site_name );
			$message
				= sprintf( get_option( 'kleingarten_account_registration_notification_message' ),
				$site_name );

			wp_mail( $to, $subject, $message, $headers );

		}

	}

	/**
	 * Callback for shortcode kleingarten_like_link
	 *
	 * @return string HTML output
	 */
	public function kleingarten_likes_callback() {

		$html = '<div id="kleingarten-likes" class="kleingarten-likes">';

		$visitor_label          = esc_html( __( 'Log in to like this post.',
			'kleingarten' ) );
		$label_like             = esc_html( __( 'Like this post',
			'kleingarten' ) );
		$label_dislike          = esc_html( __( 'Dont like this post',
			'kleingarten' ) );
		$visitor_list_alt_text
		                        = esc_html( __( 'Log in to see who likes this post.',
			'kleingarten' ) );
		$counter_label_plural   = esc_html( __( 'gardeners like this post.',
			'kleingarten' ) );
		$counter_label_singular = esc_html( __( 'gardener like this post.',
			'kleingarten' ) );

		$login_page_url
			= esc_url( get_permalink( get_option( 'kleingarten_login_page' ) ) );

		$user_id = get_current_user_id();
		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return '<p>'
			       . esc_html( __( 'Sorry, you can not use this shortcode here.',
					'kleingarten' ) ) . '</p>';
		}

		$seperator = ',';
		$raw_likes = get_post_meta( $post_id, 'kleingarten_likes', true );
		$raw_likes = rtrim( $raw_likes, $seperator );
		$likes     = explode( $seperator, $raw_likes );

		// Remove empty elements from likes array
		// so that no likes results in zero when counting
		foreach ( $likes as $i => $like ) {
			if ( ! strlen( $like ) ) {
				unset( $likes[ $i ] );
			}
		}

		if ( count( $likes ) == 0 || count( $likes ) > 1 ) {
			$counter_label = $counter_label_plural;
		} else {
			$counter_label = $counter_label_singular;
		}


		$html .= '<p>';

		ob_start();
		?>
        <span id="kleingarten-likes-counter-wrapper"
              class="kleingarten-likes-counter-wrapper">
				<span id="kleingarten-likes-counter"
                      class="kleingarten-likes-counter">
					<span id="kleingarten-likes-counter-value"
                          class="kleingarten-likes-counter-value"><?php echo count( $likes ); ?></span>
					<span id="kleingarten-likes-counter-label"
                          class="kleingarten-likes-counter-label"><?php echo esc_html( $counter_label ); ?></span>
					<a id="kleingarten-likes-counter-show-all"
                       class="kleingarten-likes-counter-show-all">(<?php echo esc_html( __( 'Show all',
							'kleingarten' ) ); ?>)</a>
				</span>
			</span>
		<?php
		$html .= ob_get_clean();

		if ( in_array( $user_id, $likes ) ) {
			$label = $label_dislike;
		} else {
			$label = $label_like;
		}

		if ( $this->current_user_is_allowed_to_like() ) {
			$html .= '&emsp;<span class="kleingarten-like-link"><a class="kleingarten-like" id="kleingartenlike">'
			         . esc_html( $label ) . '</a></span>';
		} else {
			$html .= '&emsp;<span class="kleingarten-like-link"><a href="'
			         . esc_url( $login_page_url )
			         . '" class="kleingarten-like">'
			         . esc_html( $visitor_label ) . '</a></span>';
		}

		$html .= '</p>';

		if ( $this->current_user_is_allowed_to_like() ) {

			ob_start();
			?>
            <ul style="display: none;" class="kleingarten-list-of-likes"
                id="kleingarten-list-of-likes"><?php
			foreach ( $likes as $like ) {

				$firstname = get_user_meta( $like, 'first_name', true );
				$lastname  = get_user_meta( $like, 'last_name', true );
				$plot      = get_user_meta( $like, 'plot', true );
				?>
                <li>
					<?php
					echo esc_html( $firstname . ' ' . $lastname );
					if ( isset ( $plot ) && $plot != '' && $plot != 0 ) {
						echo ' (' . esc_html( __( 'Garden No.',
								'kleingarten' ) ) . ' '
						     . esc_html( get_the_title( $plot ) ) . ')';
					}
					?>
                </li>
				<?php

			}
			?></ul><?php
			$html .= ob_get_clean();

		} else {
			$html .= '<span style="display: none;" id="kleingarten-list-of-likes"><a href="'
			         . esc_url( $login_page_url )
			         . '" class="kleingarten-like">'
			         . esc_html( $visitor_list_alt_text ) . '</a></span>';
		}

		$html .= '</div>';

		return $html;

	}

	/**
	 * Check if current user is allowed to like
	 *
	 * @return bool
	 */
	private function current_user_is_allowed_to_like() {

		if ( ! is_user_logged_in() ) {
			return false;
		}

		return true;

	}

	/**
	 * Callback for like post (AJAX)
	 *
	 * @return void
	 */
	#[NoReturn] public function kleingarten_like_post_ajax_callback() {

		if ( ! isset( $_POST['nonce'] )
		     && ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ),
				'kleingarten-likes-ajax-nonce' )
		) {
			wp_die( esc_html__( 'Are you trying something nasty here?',
				'kleingarten' ) );
		}

		if ( $this->current_user_is_allowed_to_like() ) {

			$user_id = get_current_user_id();
			$url     = wp_get_referer();
			$post_id = url_to_postid( $url );

			$seperator = ',';

			$show_all_hide_label = '(' . esc_html( __( 'Hide', 'kleingarten' ) )
			                       . ')';
			$show_all_show_label = '(' . esc_html( __( 'Show all',
					'kleingarten' ) ) . ')';

			if ( $user_id != 0 && $post_id ) {

				$raw_likes = get_post_meta( $post_id, 'kleingarten_likes',
					true );
				$raw_likes = rtrim( $raw_likes, $seperator );
				$likes     = explode( $seperator, $raw_likes );

				// Remove empty elements from likes array
				// so that no likes results in zero when counting
				foreach ( $likes as $i => $like ) {
					if ( ! strlen( $like ) ) {
						unset( $likes[ $i ] );
					}
				}

				$dislikeed = false;
				foreach ( $likes as $i => $like ) {

					if ( $like == $user_id ) {
						unset ( $likes[ $i ] );
						$dislikeed = true;
						$json_response
						           = array(
							'label'         => esc_html( __( 'Disliked',
								'kleingarten' ) ),
							'default_label' => esc_html( __( 'I like this',
								'kleingarten' ) )
						);
					}

				}

				if ( $dislikeed === false ) {

					$json_response = array(
						'label'         => esc_html( __( 'Liked',
							'kleingarten' ) ),
						'default_label' => esc_html( __( 'Disliked',
							'kleingarten' ) )
					);
					$likes[]       = $user_id;

				}

				ob_start();
				foreach ( $likes as $like ) {

					$firstname = get_user_meta( $like, 'first_name', true );
					$lastname  = get_user_meta( $like, 'last_name', true );
					$plot      = get_user_meta( $like, 'plot', true );
					?>
                    <li><?php echo esc_html( $firstname . ' ' . $lastname );
						if ( isset ( $plot ) && $plot != '' && $plot != 0 ) {
							echo ' (' . esc_html( __( 'Garden No.',
									'kleingarten' ) ) . ' '
							     . esc_html( get_the_title( $plot ) ) . ')';
						}
						?>
                    </li>
					<?php

				}
				$list_of_likes_html = ob_get_clean();

				$json_response['list_of_likes'] = $list_of_likes_html;

				$string_to_save = implode( $seperator, $likes );
				update_post_meta( $post_id, 'kleingarten_likes',
					$string_to_save );

				$json_response['counter'] = count( $likes );

				$json_response['show_all_hide_label'] = $show_all_hide_label;
				$json_response['show_all_show_label'] = $show_all_show_label;

				wp_send_json_success( $json_response, 200 );

			}

		}

		wp_die(); // Ajax call must die to avoid trailing 0 in your response.

	}

	/**
	 * Callback for show all likes (AJAX)
	 *
	 * @return void
	 */
	#[NoReturn] public function kleingarten_show_all_likes_ajax_callback() {

		if ( ! isset( $_POST['nonce'] )
		     && ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ),
				'kleingarten-likes-ajax-nonce' )
		) {
			wp_die( esc_html__( 'Are you trying something nasty here?',
				'kleingarten' ) );
		}

		if ( $this->current_user_is_allowed_to_like() ) {

			$url     = wp_get_referer();
			$post_id = url_to_postid( $url );

			$seperator = ',';

			$show_all_hide_label = '(' . esc_html( __( 'Hide', 'kleingarten' ) )
			                       . ')';
			$show_all_show_label = '(' . esc_html( __( 'Show all',
					'kleingarten' ) ) . ')';

			if ( $post_id ) {

				$raw_likes = get_post_meta( $post_id, 'kleingarten_likes',
					true );
				$raw_likes = rtrim( $raw_likes, $seperator );
				$likes     = explode( $seperator, $raw_likes );

				// Remove empty elements from likes array
				// so that no likes results in zero when counting
				foreach ( $likes as $i => $like ) {
					if ( ! strlen( $like ) ) {
						unset( $likes[ $i ] );
					}
				}

				ob_start();
				foreach ( $likes as $like ) {

					$firstname = get_user_meta( $like, 'first_name', true );
					$lastname  = get_user_meta( $like, 'last_name', true );
					$plot      = get_user_meta( $like, 'plot', true );
					?>
                    <li><?php echo esc_html( $firstname . ' ' . $lastname );
						if ( isset ( $plot ) && $plot != '' && $plot != 0 ) {
							echo ' (' . esc_html( __( 'Garden No.',
									'kleingarten' ) ) . ' '
							     . esc_html( get_the_title( $plot ) ) . ')';
						}
						?>
                    </li>
					<?php

				}
				$list_of_likes_html = ob_get_clean();

				$json_response['list_of_likes']       = $list_of_likes_html;
				$json_response['counter']             = count( $likes );
				$json_response['show_all_hide_label'] = $show_all_hide_label;
				$json_response['show_all_show_label'] = $show_all_show_label;

				wp_send_json_success( $json_response, 200 );

			}
		}

		wp_die(); // Ajax call must die to avoid trailing 0 in your response.

	}

	/**
	 * Callback for shortcode kleingarten_number_of_private_posts. Counts private posts and shows the result.
	 *
	 * @return string  HTML output
	 */
	public function kleingarten_number_of_private_posts_callback( $atts ) {

		// Extract shortcode attributes
		$atts = shortcode_atts( array(
			'label'     => '%u',
			'login_url' => '',
		),
			$atts
		);

		$html = '';

		if ( ! is_user_logged_in() ) {

			global $wpdb;

			// Get IDs from all published private posts. No need to get everything from DB.
			$private_posts
				= $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s and post_status = 'private'",
				'post' ), ARRAY_A );

			ob_start();

			echo esc_html( sprintf( $atts['label'], count( $private_posts ) ) );

			if ( $atts['login_url'] != '' ) {
				echo '&nbsp;<a href="' . esc_url( $atts['login_url'] ) . '">'
				     . esc_html( __( 'Login', 'kleingarten' ) ) . '</a>';
			}

			$html .= ob_get_clean();

		}

		return $html;

	}

	/**
	 * Callback for shortcode kleingarten_private_posts. Prints a list of private posts for member area.
	 *
	 * @return string  HTML output
	 */
	public function kleingarten_private_posts_callback( $atts ) {

		$html = '';

		if ( is_user_logged_in() ) {

			$private_post_args = array(
				'post_type'      => 'post',
				'post_status'    => 'private',
				'posts_per_page' => - 1
			);

			ob_start();

			$private_posts = new WP_Query( $private_post_args );

            echo '<div class="kleingarten-member-profile-section">';

			?>
            <h2><?php echo esc_html( __( 'Exclusive Posts',
				'kleingarten' ) ); ?></h2>
            <?php

			if ( $private_posts->have_posts() ) {

				?>
                <p><?php echo esc_html( __( 'You can read these posts exclusively as a registered member.',
					'kleingarten' ) ); ?></p><?php

				echo '<ul>';

				while ( $private_posts->have_posts() ) {
					$private_posts->the_post();
					echo '<li><a href="' . esc_url( get_the_permalink() ) . '">'
					     . esc_html( get_the_title() ) . '</a></li>';
				}

				echo '</ul>';

                echo '</div>';

				$html .= ob_get_clean();

			} else {
				?>
                <p><?php echo esc_html( __( 'There are no exclusive posts currently.',
					'kleingarten' ) ); ?></p><?php
				ob_clean();
			}

			wp_reset_postdata();

		}

		return $html;

	}

	/**
	 * Callback for shortcode kleingarten_submit_meter_reading_form. Build a form to submit meter reading.
	 *
	 * @return string  HTML output
	 */
	public function kleingarten_submit_meter_reading_form_callback( $atts ) {

		// Messages to be printed after form submission:
		$messages = array();

		// Number of errors found while checking submitted data:
		$error_counter = 0;

		// Indicates if form was submitted:
		$submitted = false;

		// If meter reading form was submitted...
		if ( isset ( $_POST['kleingarten_submit_meter_reading_submit'] ) ) {

			// ... set a flag:
			$submitted = true;

			// ... verify nonce or die:
			if ( ! isset( $_POST['kleingarten_meter_reading_submission_nonce'] )
			     || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['kleingarten_meter_reading_submission_nonce'] ) ),
					'kleingarten_meter_reading_submission' )
			) {
				wp_die( esc_html__( 'Are you trying something nasty here?',
					'kleingarten' ) );
			} else {

                // ... sanitize submitted data:
				$submitted_token = 0;
				if ( isset( $_POST['kleingarten_meter_reading_submission_token'] ) ) {
					$submitted_token
						= absint( $_POST['kleingarten_meter_reading_submission_token'] );
				}
				$submitted_reading = 0;
				if ( isset( $_POST['kleingarten_meter_reading'] ) ) {
					$submitted_reading
						= absint( $_POST['kleingarten_meter_reading'] );
				}
				$submitted_date = '';
				if ( isset( $_POST['kleingarten_meter_reading_date'] ) ) {
					$submitted_date
						= sanitize_text_field( $_POST['kleingarten_meter_reading_date'] );
				}

				// ... and finally try to save it:
				$save_reading_result
					= $this->save_meter_reading_by_token( $submitted_token,
					$submitted_reading, $submitted_date );
				if ( is_wp_error( $save_reading_result ) ) {
					$messages = $save_reading_result->get_error_messages();
					$error_counter ++;
				}

			}

		}

		ob_start();

        ?><div class="kleingarten-submit-meter-reading-form-section"><?php

		// Print messages if form was submitted:
		if ( $submitted == true && count( $messages ) > 0 ) {

			echo '<ul class="kleingarten-submit-meter-reading-messages">';
			foreach ( $messages as $message ) {
				echo '<li>' . esc_html( $message ) . '</li>';
			}
			echo '</ul>';

		} elseif ( $submitted == true && $error_counter == 0
		           && count( $messages ) == 0 ) {

			echo '<ul class="kleingarten-submit-meter-reading-messages">';
			echo '<li>' . esc_html( __( 'Meter reading submitted.',
					'kleingarten' ) ) . '</li>';
			echo '</ul>';

		}

		?>
        <form action="<?php echo esc_url( get_permalink() ); ?>"
              method="post">
            <p>
                <label for="kleingarten_meter_reading_submission_token">
					<?php esc_html_e( 'Token', 'kleingarten' ); ?>
                </label>
                <input
                        type="text"
                        name="kleingarten_meter_reading_submission_token"
                        id="kleingarten_meter_reading_submission_token"
                        required
                />
            </p>
            <p>
                <label for="kleingarten_meter_reading_date">
					<?php esc_html_e( 'Reading date', 'kleingarten' ); ?>
                </label>
                <input
                        type="date"
                        name="kleingarten_meter_reading_date"
                        id="kleingarten_meter_reading_date"
                        value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>"
                        required
                />
            </p>
            <p>
                <label for="kleingarten_meter_reading">
					<?php esc_html_e( 'Reading value', 'kleingarten' ); ?>
                </label>
                <input
                        type="number"
                        name="kleingarten_meter_reading"
                        id="kleingarten_meter_reading"
                        required
                />
            </p>
            <p>
                <input type="submit"
                       name="kleingarten_submit_meter_reading_submit"
                       value="<?php esc_attr_e( 'Submit', 'kleingarten' ); ?>">
            </p>
            <input type="hidden"
                   value="<?php echo esc_url( get_permalink() ); ?>"
                   name="redirect_url">
			<?php wp_nonce_field( 'kleingarten_meter_reading_submission',
				'kleingarten_meter_reading_submission_nonce' ); ?>
        </form>
        </div>
		<?php

		$html = ob_get_clean();

		return $html;

	}

	/**
	 * Saves a new meter reading. No validation.
	 *
	 * @return bool|WP_Error
	 */
	private function save_meter_reading_by_token(
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
			= $this->token_is_usable( $token );       // "token_is_usable" will return meta ID or WP_Error
		if ( is_wp_error( $token_id ) ) {
			$errors->merge_from( $token_id );
			return $errors;
		}

		// Validate the reading.
		// Stop right here on failure.
		$reading_validation_data = $this->reading_is_valid( $value_read );
		if ( is_wp_error( $reading_validation_data ) ) {
			$errors->merge_from( $reading_validation_data );

			return $errors;
		}

		// Validate the reading date.
		// Stop right here on failure.
		$reading_date_validation_data
			= $this->reading_date_is_valid( $timestamp, $token_id );
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
		if ( $save_reading_result != false ) {

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
	private function token_is_usable( $token ) {

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
	private function reading_is_valid( $reading ) {

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
	private function reading_date_is_valid( $timestamp, $token_id = 0 ) {

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
	 * Callback for shortcode kleingarten_my_plot. Displays member profile.
	 *
	 * @return string  HTML output
	 */
	public function kleingarten_my_plot_callback( $atts ) {

		// Extract shortcode attributes
		$atts = shortcode_atts( array(
			'allow_reading_submission' => 'false',
            'checkbox_label' => esc_html( __( 'I have checked the date and meter reading. Both are correct.', 'kleingarten' ) ),
		),
			$atts
		);

		$html = '';

		ob_start();

		switch ( is_user_logged_in() ) {

            // For logged in users only:
			case true:

				$user = get_user_by( 'ID', get_current_user_id() );
				$plot = get_the_author_meta( 'plot', $user->ID );

                // If user has plot assigned get its meters...
                if ( $plot > 0 ) {
	                //$assigned_meters = $this->get_assigned_meters( $plot );
	                $assigned_meters = $this->plots->get_assigned_meters( intval( $plot ) );
                // ... but if user has no plot assigned, set an empty array
                // to prevent our loops from throwing warnings later:
                } else {
                    $assigned_meters = array();
                }

				//$error_messages = 0;
				//$error_data = 0;
				$save_reading_result = 0;
                $submitted = false;

				// If meter reading form was submitted...
				if ( isset ( $_POST['kleingarten_inline_meter_reading_submission_form_meter_to_update'] ) ) {

                    // ... set a flag:
					$submitted = true;

					// ... verify nonce or die:
					if ( ! isset( $_POST['kleingarten_inline_meter_reading_submission_nonce'] )
					     || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['kleingarten_inline_meter_reading_submission_nonce'] ) ),
							'kleingarten_inline_meter_reading_submission' )

					) {
						wp_die( esc_html__( 'Are you trying something nasty here?',
							'kleingarten' ) );

                    // Nonce OK? Go on:
					} else {

                        // Set a harmless default value and update it if there is something submitted via $_POST:
                        // ... sanitize submitted data:
                        $reading_data_checked = false;
                        if ( isset( $_POST['kleingarten_inline_meter_reading_submission_form_reading_data_checked'] ) ) {
                            $reading_data_checked = true;
                        }
                        $submitted_meter_id = 0;
                        if ( isset( $_POST['kleingarten_inline_meter_reading_submission_form_meter_to_update'] ) ) {
                            $submitted_meter_id
                                = absint( $_POST['kleingarten_inline_meter_reading_submission_form_meter_to_update'] );
                        }
                        $submitted_reading = 0;
                        if ( isset( $_POST['kleingarten_inline_meter_reading_submission_form_reading_value'] ) ) {
                            $submitted_reading
                                = absint( $_POST['kleingarten_inline_meter_reading_submission_form_reading_value'] );
                        }
                        $submitted_date = '';
                        if ( isset( $_POST['kleingarten_inline_meter_reading_submission_form_date'] ) ) {
                            $submitted_date
                                = sanitize_text_field( $_POST['kleingarten_inline_meter_reading_submission_form_date'] );
                        }

                        // Finally try to save it an save the result for later:
                        $save_reading_result
                            = $this->save_meter_reading_from_inline_form( $submitted_meter_id,
                            $submitted_date, $submitted_reading, get_current_user_id(), $reading_data_checked );

					}

				}

				?>

                <div class="kleingarten-my-plot-section">

                    <h2><?php echo esc_html( __( 'Your Plot',
                            'kleingarten' ) ); ?></h2>
                    <p><?php esc_html_e( 'This plot is assigned to you.',
                            'kleingarten' ); ?></p>
	                <?php

	                // If there were any errors on trying to save new reading...
	                if ( is_wp_error( $save_reading_result ) ) {

                        // ... get the error messages and corresponding error data:
                        $error_codes = $save_reading_result->get_error_codes();

                        // ... let's look at every single error code we received:
		                echo '<ul class="kleingarten-inline-submit-meter-reading-messages">';
                        foreach ( $error_codes as $error_code ) {

                            // Get error message and corresponding data:
                            // (Error data is the meter ID the error belongs to)
	                        $error_message = $save_reading_result->get_error_message( $error_code );
                            $error_data = $save_reading_result->get_error_data( $error_code );

	                        //if ( in_array( $error_data, $assigned_meters) && isset( $error_message ) ) {

                                echo '<li>';
                                echo esc_html( __( 'Error', 'kleingarten' ) ) . ': ';
                                echo esc_html( $error_message );
                                echo '</li>';

	                        //} elseif ( $error_code === 'kleingarten_inline_meter_reading_not_your_plot' ) {
                            /*
		                        echo '<li>';
		                        echo esc_html( __( 'Error', 'kleingarten' ) ) . ': ';
		                        echo esc_html( $error_message );
		                        echo '</li>';
                            */
	                        //}

                        }
		                echo '</ul>';

                    // If the form was submitted but there were no errors, print a success message:
	                } elseif ( $submitted === true) {
                        echo '<ul class="kleingarten-inline-submit-meter-reading-messages">';
		                echo '<li>';
		                echo esc_html( __( 'Reading submitted successfully', 'kleingarten' ) );
		                echo '</li>';
		                echo '</ul>';
	                }

	                ?>
                    <table>
                        <tr>
                            <th>
                                <?php esc_html_e( 'Plot', 'kleingarten' ); ?>
                            </th>
                            <td>
                                <?php
                                if ( isset( $plot) && $plot != 0 ) {
                                    echo esc_html( get_the_title( $plot ) );
                                } else {
                                    esc_html_e( 'There is no plot assigned to you.', 'kleingarten' );
                                }
                                ?>
                            </td>
                        </tr>
                        <?php

                        $i = 0;
                        foreach( $assigned_meters as $assigned_meter ) {
                            $i++;
                            ?>
                            <tr>
                                <th>
                                    <?php
                                    echo esc_html( $i . '. ' );
                                    esc_html_e( 'Meter', 'kleingarten' );

                                    $readings = has_meta( $assigned_meter, 'kleingarten_meter_reading' );
                                    $wp_date_format = get_option( 'date_format' );
                                    $most_recent = 0;                       // Helper for comparing
                                    $most_recent_reading_value = null;      // Latest value
                                    $most_recent_reading_date  = '';        // Latest date
                                    //$assigned_meter_unit = '';
                                    foreach ( $readings as $j => $reading ) {

                                        // Initially $readings contains all meta data. So if the current
                                        // is not a reading...
                                        if ( $reading['meta_key'] != 'kleingarten_meter_reading' ) {

                                            // ... forget it...
                                            unset( $readings[ $j ] );

                                            // ... but if it is a reading...
                                        } else {

                                            // ... and if the value is even a serialized array...
                                            if ( is_serialized( $reading['meta_value'] ) ) {

                                                $reading_data_set = unserialize( $reading['meta_value'] );
                                                $current_date     = $reading_data_set['date'];
                                                if ( $current_date > $most_recent ) {
                                                    $most_recent               = $current_date;
                                                    $most_recent_reading_value = $reading_data_set['value'];
                                                    $most_recent_reading_date  = $reading_data_set['date'];
                                                }

                                            }

                                        }

                                    }
                                    ?>
                                </th>
                                <td>
                                    <?php

                                    echo '<p>';
                                    echo esc_html( get_the_title( $assigned_meter ) );
                                    echo '</p>';

                                    if ( count( $readings ) > 0 ) {

                                        echo '<p>';
                                        $assigned_meter_unit = get_post_meta( $assigned_meter, 'kleingarten_meter_unit', true );
                                        echo esc_html( __( 'Last known reading', 'kleingarten' ) ) . ':<br>' . esc_html( $most_recent_reading_value ) . ' ' . esc_html( $assigned_meter_unit ) . ' ' . esc_html( __( 'as of', 'kleingarten' ) ) . ' ' . esc_html( wp_date( $wp_date_format, $most_recent_reading_date ) );
                                        echo '</p>';

                                    } else {
                                        echo '<p>' . esc_html_e( 'No reading so far.', 'kleingarten' ) . '</p>';
                                    }

                                    if ( $atts['allow_reading_submission'] == 'true' ) {

	                                    ?>
                                        <div class="kleingarten-inline-meter-reading-submission-form">
                                            <form method="post" action="<?php echo esc_url( get_permalink() ); ?>">
                                                <p>
                                                    <input type="hidden" name="kleingarten_inline_meter_reading_submission_form_meter_to_update" value="<?php echo esc_attr( $assigned_meter ); ?>">
                                                    <input name="kleingarten_inline_meter_reading_submission_form_date" type="date" value="<?php echo esc_attr( gmdate("Y-m-d") ); ?>" required>
                                                    <input name="kleingarten_inline_meter_reading_submission_form_reading_value" type="number" required>
                                                </p>
                                                <p>
                                                    <label>
                                                        <input name="kleingarten_inline_meter_reading_submission_form_reading_data_checked" type="checkbox" required>
					                                    <?php echo esc_html( $atts['checkbox_label'] ); ?>
                                                    </label>
                                                </p>
                                                <p>
                                                    <input name="kleingarten_inline_meter_reading_submission_form_submit" type="submit" value="<?php esc_html_e( 'Submit Reading', 'kleingarten' ); ?>">
                                                </p>
	                                            <?php
                                                wp_nonce_field( 'kleingarten_inline_meter_reading_submission',
		                                            'kleingarten_inline_meter_reading_submission_nonce' );
                                                ?>
                                            </form>
                                        </div>
                                        <?php

                                    }

                                    ?>

                                </td>
                            </tr>
                            <?php
                        }

                        ?>
                    </table>
                </div>
				<?php
				break;

            // For none logged in users simply do nothing:
			default:
				// Do nothing.
				break;
		}
		$html .= ob_get_clean();

		return $html;

	}

	/**
	 * Returns a list of meters assigned to a plot.
	 *
	 * @param $plot_ID
	 *
	 * @return array
	 *@since 1.1.3
	 */
    /*
	private function get_assigned_meters( $plot_ID ) {/*
		return $this->plots->get_assigned_meters( intval( $plot_ID ) );
	}

    */

    private function save_meter_reading_from_inline_form( $meter_id, $reading_date, $reading_value, $user_id, $reading_data_checked = true ) {

        $errors = new WP_Error();

        // If meter is not assigned to user by plot, stop right away:
        if ( ! $this->meter_is_assigned_to_user( $meter_id, $user_id ) ) {
	        $errors->add( 'kleingarten_inline_meter_reading_not_your_plot', __( 'You may not send readings for plots that are not assigned to you.', 'kleingarten' ) );
	        //echo 'coco';
            return $errors;
        }

        if ( $reading_data_checked === true ) {

	        $sanitized_data = array();

	        // Check basic pre-conditions (parameters are available):
	        if ( isset( $meter_id ) && $meter_id > 0 && isset( $reading_date ) && isset( $reading_value ) && isset( $user_id ) && $user_id > 0 ) {

		        // Sanitize data:
		        $sanitized_data['date'] = strtotime( sanitize_text_field( wp_unslash( $reading_date ) ) );
		        $sanitized_data['value'] = absint( wp_unslash( $reading_value ) );
		        $sanitized_data['by'] = absint( wp_unslash( $user_id ) );
		        /*
				if ( isset( $_POST['new_kleingarten_meter_reading']['meter-no'] ) ) {
					$sanitized_data['meter-no'] = sanitize_text_field( wp_unslash( $_POST['new_kleingarten_meter_reading']['meter-no'] ) );
				}
				*/

		        // Validate data:
		        $validation_errors = 0;
		        $existing_readings = get_post_meta( $meter_id, 'kleingarten_meter_reading' );
		        if ( $existing_readings ) {

			        // Check if we already have a reading for this date:
			        foreach ( $existing_readings as $existing_reading ) {
				        if ( $existing_reading['date'] === $sanitized_data['date'] ) {

					        $validation_errors++;
					        $errors->add( 'kleingarten_inline_meter_reading_form_not_unique', __( 'A meter reading already exists for this date.', 'kleingarten' ), $meter_id );
					        break;

				        }
			        }

			        // Determine if date is in the future:
			        $reading_date = $sanitized_data['date'];
			        $today = strtotime( gmdate( 'Y-m-d' ) );
			        if ( $reading_date > $today ) {
				        $errors->add( 'kleingarten_inline_meter_reading_form_date_in_future', __( 'Cannot save a reading for a date in the future.', 'kleingarten' ), $meter_id );
			        }

		        }

		        // Finally save it if valid:
		        if ( ! $errors->has_errors() ) {

			        $meta_id = 0;
			        $meta_id = add_post_meta( $meter_id, 'kleingarten_meter_reading', $sanitized_data );
			        if ( ! is_bool( $meta_id ) && ! $meta_id === false ) {
				        return false;
				        //$this->add_message( 'kleingarten_meter_reading', 'kleingarten_meter_reading', __( 'New reading saved.', 'kleingarten' ), 'success' );
			        } else {
				        return $meta_id;
				        //$this->add_message( 'kleingarten_meter_reading', 'kleingarten_meter_reading', __( 'Something went wrong. Reading could not be saved.', 'kleingarten' ), 'error' );
			        }

		        } else {
			        return $errors;
		        }

	        }
	        $errors->add( 'kleingarten_inline_meter_reading_form_missing_data', __( 'Please fill out the form completely.', 'kleingarten' ), $meter_id );
	        return $errors;

        } else {
	        $errors->add( 'kleingarten_inline_meter_reading_form_data_not_checked', __( 'Please confirm that you checked the data for correctness.', 'kleingarten' ), $meter_id );
	        return $errors;
        }

    }


    private function meter_is_assigned_to_user( $meter_id, $user_id ) {

	    $user_meta = get_user_meta( $user_id );
        $assigned_plot = $user_meta['plot'][0];
        //$assigned_meters = $this->get_assigned_meters( $assigned_plot );
	    $assigned_meters = $this->plots->get_assigned_meters( intval( $assigned_plot ) );

        if ( in_array( $meter_id, $assigned_meters ) ) {
            return true;
        }

        return false;

    }

}

