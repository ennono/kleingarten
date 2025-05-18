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
        add_shortcode( 'kleingarten_private_content', array( $this, 'kleingarten_private_content_callback' ) );

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

		add_filter( 'authenticate',
			array( $this, 'handle_user_authentification' ), 31, 3 );

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

		ob_start();
		switch ( is_user_logged_in() ) {

			case false:
				if ( isset ( $_GET['kleingarten_failed_login_nonce'] )
				     && wp_verify_nonce( sanitize_key( wp_unslash ( $_GET['kleingarten_failed_login_nonce'] ) ),
						'kleingarten_failed_login_nonce_action' )
				) {
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
				}

                /*
                $nonce = wp_create_nonce( 'kleingarten_login_form_nonce_action' );
				$redirect_url = add_query_arg( 'kleingarten_login_form_nonce', $nonce, $atts['redirect_to'] );
                */

				?>

                <form name="loginform" id="loginform"
                      action="<?php echo esc_url( wp_login_url() ); ?>"
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
                           value="<?php echo esc_attr( $atts['redirect_to'] ); /* echo esc_attr( $redirect_url ); */ ?>"
                           name="redirect_to">
                    <?php wp_nonce_field( 'kleingarten_login_form_nonce_action', 'kleingarten_login_form_nonce' ); ?>
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
				$user = get_user_by( 'ID', get_current_user_id() );
				$user_profile_page_id = get_option( 'kleingarten_login_page' );
				$args                 = array( 'loggedout' => '1' );
				if ( $user_profile_page_id == 0 ) {
					// translators: Placeholder is replaced by username.
					echo '<p>' . esc_html( sprintf( __( 'Logged in as %s',
							'kleingarten' ), $user->user_login ) );
				} else {
					echo '<p>' . esc_html( __( 'Logged in as', 'kleingarten' ) )
					     . ' <a href="'
					     . esc_url( get_permalink( $user_profile_page_id ) )
					     . '">' . esc_html( $user->user_login ) . '</a>.';
				}
				echo '&nbsp;<a href="'
				     . esc_url( wp_logout_url( add_query_arg( $args,
						$atts['logged_out_page'] ) ) ) . '">'
				     . esc_html( __( 'Logout', 'kleingarten' ) ) . '</a></p>';
				break;

		}

		return ob_get_clean();

	}

	/**
	 * Handles failed login attempts.
	 *
	 * @return void
	 */
	public function handle_failed_login() {

		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {

			$nonce = wp_create_nonce( 'kleingarten_failed_login_nonce_action' );

			$referer = sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) );

			// If there's a valid referrer, and it's not the default log-in screen:
			if ( ! empty( $referer ) && ! str_contains( $referer, 'wp-login' )
			     && ! str_contains( $referer, 'wp-admin' )
			) {
				$referer = str_replace( '?login=failed', '',
					$referer );    // To prevent multiple failed parameteres in URL in case of multiple failed login attempts
				$referer = str_replace( '?loggedout=1', '',
					$referer );
				$referer = add_query_arg( 'login', 'failed', $referer );
				$referer = add_query_arg( 'kleingarten_failed_login_nonce', $nonce, $referer );
				wp_redirect( $referer );
				exit;
			}
		}

	}

	/**
	 * @param $user
	 * @param $username
	 * @param $password
	 *
	 * @return mixed
	 */
	public function handle_user_authentification( $user, $username, $password
	) {

		if ( empty( $_POST ) ) {
			// Request not posted, so do nothing:
			return;
		}

		if ( isset( $_POST['kleingarten_login_form_nonce'] ) ) {
			$nonce = sanitize_key( wp_unslash( $_POST['kleingarten_login_form_nonce'] ) );
		} else {
			$nonce = false;
		}
		if ( $user || ( $nonce && wp_verify_nonce( $nonce, 'kleingarten_login_form_nonce_action' ) ) ) {

			if ( is_wp_error( $user ) && isset( $_SERVER['HTTP_REFERER'] )
			     && ! strpos( sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ),
					'wp-admin' )
			     && ! strpos( sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ),
					'wp-login.php' ) ) {

				$referrer
					= sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
				foreach ( $user->errors as $key => $error ) {

					if ( in_array( $key,
						array( 'empty_password', 'empty_username' ) ) ) {
						unset( $user->errors[ $key ] );
						$user->errors[ 'custom_' . $key ] = $error;
					}

				}

			}

			return $user;

		}

        return false;

	}

	/**
	 * Callback for shortcode kleingarten_member_profil. Displays member profile.
	 *
	 * @return string  HTML output
	 */
	public function kleingarten_member_profile_callback() {

		$html         = '';

		ob_start();
		switch ( is_user_logged_in() ) {

			case true:

				$gardener = new Kleingarten_Gardener( get_current_user_id() );
				$plot = new Kleingarten_Plot( $gardener->plot );

                /*
				?>

                <h2 class="kleingarten-member-profile-section"><?php echo esc_html( __( 'Your Member Profile',
						'kleingarten' ) ); ?></h2>
                <p><?php echo esc_html( __( 'The following data is stored under your user account on this website.',
						'kleingarten' ) ); ?></p>

                <?php
                */

                ?>

                <div class="kleingarten-member-profile-section">
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

								foreach (
									$gardener->positions as $i => $position
								) {
									echo esc_html( $position );
									if ( count( $gardener->positions ) > 1
									     && $i < count( $gardener->positions )
									             - 1
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
								echo esc_html( $plot->get_title() );
							} else {
								echo esc_html( __( 'No plot is assign to you.',
									'kleingarten' ) );
							}
							?></td>
                    </tr><?php
					?>
                </table>
                </div>
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

			$gardener = new Kleingarten_Gardener( get_current_user_id() );

			ob_start();

			?>
            <div class="kleingarten-member-profile-section">
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
                  method="post">
                <input type="hidden" name="action"
                       value="kleingarten_member_profile_settings">
                <p>
                    <label for="send-email-notifications" class="checkbox">
						<?php
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
            </div>
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
	public function handle_kleingarten_member_profile_settings_callback(
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

			$gardener = new Kleingarten_Gardener( get_current_user_id() );

			if ( isset( $_POST['send_email_notifications'] )
			     && $_POST['send_email_notifications'] == 1 ) {

				$gardener->set_notification_mail_receival();

			} else {
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
	 * Callback for shortcode kleingarten_register_form. Displays registration form.
	 *
	 * @return string  HTML output
	 */
	public function kleingarten_register_form_callback( $atts ) {

		// Extract shortcode attributes
		$atts = shortcode_atts( array(
			'terms_of_use_url' => 'none',
            'anti_spam_challenge' => 'false',
		),
			$atts
		);

		$anti_spam_challenge = $atts['anti_spam_challenge'];

		if ( ! get_option( 'users_can_register' ) ) {
			return __( 'User registration is disabled.', 'kleingarten' );
		}

		$hide_form = false;

		// If registration form submitted:
		if ( isset( $_REQUEST['kleingarten_register_gardener_nonce'] ) ) {

			if ( wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['kleingarten_register_gardener_nonce'] ) ),
				'kleingarten_register_gardener' )
			) {

				// Nonce is matched and valid. Go!

				// Sanitize form input:
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
				if ( $anti_spam_challenge == 'true' ) {
					if ( isset( $_POST["anti_spam_response"] ) ) {
						$new_user_data["anti_spam_response"] = sanitize_text_field( wp_unslash( $_POST["anti_spam_response"] ) );
					} else {
						$new_user_data["anti_spam_response"] = null;
					}
				}

				// Validate user data and get error messages:
				$user_data_validation
					= $this->validate_user_data( $new_user_data );

				// If we got here without errors, create the new gardener:
				if ( ! is_wp_error( $user_data_validation ) ) {
					Kleingarten_Gardener::add_gardener( $new_user_data );
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
						$available_plot_IDs = $this->plots->get_plot_IDs();
						echo '<option value="">' . esc_html__( 'None',
								'kleingarten' ) . '</option>';
						foreach (
							$available_plot_IDs as $available_plot
						) {
							echo '<option value="'
							     . esc_attr( $available_plot ) . '">'
							     . esc_html( get_the_title( $available_plot ) )
							     . '</option>';
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
                <?php

				$anti_spam_challenge_string = get_option( 'kleingarten_anti_spam_challenge' );
				$anti_spam_response_string = get_option( 'kleingarten_anti_spam_response' );
				if ( $anti_spam_challenge == 'true' ) {

                    if ( ! empty( $anti_spam_challenge_string ) && ! empty( $anti_spam_response_string ) ) {
	                    ?>
                        <p>
                            <label for="anti_spam_response"><?php echo esc_html( $anti_spam_challenge_string ); ?>*</label>
                            <br>
                            <input type="text" name="anti_spam_response" id="anti_spam_response"
                                   value="<?php if ( isset( $_POST["anti_spam_response"] ) ) {
			                           echo esc_attr( sanitize_text_field( wp_unslash( $_POST["anti_spam_response"] ) ) );
		                           } ?>" size="25"
                                   required="required">
                        </p>
	                    <?php
                    } else {
	                    ?>
                        <p>
                            <?php esc_html_e( 'Antispam is not configured.', 'kleingarten' ); ?>
                        </p>
	                    <?php
                    }

				}

                ?>
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
	 * Validates user data and creates fitting error messages to be shown
	 * with user registration form.
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

        $correct_answer = get_option( 'kleingarten_anti_spam_response' );
        if ( $user_data["anti_spam_response"] != $correct_answer ) {
            if ( $user_data["anti_spam_response"] != '') {
	            $error->add( 'kleingarten-registration-terms-of-user-not-accepted',
		            /* translators: User's answer on antispam challenge. */
		            sprintf( __( '%s is not the correct answer. Try again.', 'kleingarten' ), esc_html( $user_data["anti_spam_response"] ) )
	            );
            } else {
	            $error->add( 'kleingarten-registration-terms-of-user-not-accepted',
		            /* translators: User's answer on antispam challenge. */
		            __( 'This is not the correct answer. Try again.', 'kleingarten' )
	            );
            }

        }

		// Check if user already exists:
		if ( username_exists( $user_data["login"] ) ) {
			$error->add( 'kleingarten-registration-user-exists',
				__( 'Username already taken.', 'kleingarten' ) );
		}

		// Check username is valid:
		if ( ! validate_username( $user_data["login"] ) ) {
			$error->add( 'kleingarten-registration-invalid-username',
				__( 'Invalid username.', 'kleingarten' ) );
		}

		// Check if username is empty:
		if ( $user_data["login"] == '' ) {
			$error->add( 'kleingarten-registration-username-empty',
				__( 'Username empty.', 'kleingarten' ) );
		}

		// Check if firstname is empty:
		if ( $user_data["firstname"] == '' ) {
			$error->add( 'kleingarten-registration-firstname-empty',
				__( 'Firstname empty.', 'kleingarten' ) );
		}

		// Check if lastname is empty:
		if ( $user_data["lastname"] == '' ) {
			$error->add( 'kleingarten-registration-lastname-empty',
				__( 'Lastname empty.', 'kleingarten' ) );
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
	 * Callback for shortcode kleingarten_likes
	 *
	 * @return string HTML output
	 */
	public function kleingarten_likes_callback() {

        //if ( doing_filter( 'default_excerpt' ) ) return '';

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

		$gardener = new Kleingarten_Gardener( get_current_user_id() );
		if ( $gardener->is_allowed_to_like() ) {
			$html .= '&emsp;<span class="kleingarten-like-link"><a class="kleingarten-like" id="kleingartenlike">'
			         . esc_html( $label ) . '</a></span>';
		} else {
			$html .= '&emsp;<span class="kleingarten-like-link"><a href="'
			         . esc_url( $login_page_url )
			         . '" class="kleingarten-like">'
			         . esc_html( $visitor_label ) . '</a></span>';
		}

		$html .= '</p>';

		$gardener = new Kleingarten_Gardener( get_current_user_id() );
		if ( $gardener->is_allowed_to_like() ) {

			ob_start();
			?>
            <ul style="display: none;" class="kleingarten-list-of-likes"
                id="kleingarten-list-of-likes"><?php
			foreach ( $likes as $like ) {

				$gardener = new Kleingarten_Gardener( $like );

				?>
                <li>
					<?php
					echo esc_html( $gardener->first_name . ' '
					               . $gardener->last_name );
					if ( isset ( $gardener->plot ) && $gardener->plot != ''
					     && $gardener->plot != 0 ) {
						$plot = new Kleingarten_Plot( $gardener->plot );
						echo ' (' . esc_html( $plot->get_title() ) . ')';
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

		//if ( $this->current_user_is_allowed_to_like() ) {
		$gardener = new Kleingarten_Gardener( get_current_user_id() );
		if ( $gardener->is_allowed_to_like() ) {

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

				$disliked = false;
				foreach ( $likes as $i => $like ) {

					if ( $like == $user_id ) {
						unset ( $likes[ $i ] );
						$disliked = true;
						$json_response
						          = array(
							'label'         => esc_html( __( 'Disliked',
								'kleingarten' ) ),
							'default_label' => esc_html( __( 'I like this',
								'kleingarten' ) )
						);
					}

				}

				if ( $disliked === false ) {

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

					$gardener = new Kleingarten_Gardener( $like );

					?>
                    <li>
						<?php
						echo esc_html( $gardener->first_name . ' '
						               . $gardener->last_name );
						if ( isset ( $gardener->plot ) && $gardener->plot != ''
						     && $gardener->plot != 0 ) {
							$plot = new Kleingarten_Plot( $gardener->plot );
							echo ' (' . esc_html( $plot->get_title() ) . ')';
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

		//if ( $this->current_user_is_allowed_to_like() ) {
		$gardener = new Kleingarten_Gardener( get_current_user_id() );
		if ( $gardener->is_allowed_to_like() ) {

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

					$gardener = new Kleingarten_Gardener( $like );

					?>
                    <li><?php echo esc_html( $gardener->first_name . ' '
					                         . $gardener->last_name );
						if ( isset ( $gardener->plot ) && $gardener->plot != ''
						     && $gardener->plot != 0 ) {
							$plot = new Kleingarten_Plot( $gardener->plot );
							echo ' (' . esc_html( $plot->get_title() ) . ')';
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

			$cache_key = 'kleingarten_private_post_ids';
			$cache_group = 'kleingarten';

			// Attempt to get cached data:
			$private_posts = wp_cache_get( $cache_key, $cache_group );

			if ( false === $private_posts ) {

				// Get IDs from all published private posts. No need to get everything from DB.
				$private_posts
					= $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s and post_status = 'private'",
					'post' ), ARRAY_A );

				// Store data in cache
				wp_cache_set( $cache_key, $private_posts, $cache_group, 60 ); // Cache for 1 minute
			}

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

			if ( $private_posts->have_posts() ) {

                /*
				?>
                <p><?php echo esc_html( __( 'You can read these posts exclusively as a registered member.',
					'kleingarten' ) ); ?></p><?php
                */

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
						= sanitize_text_field( wp_unslash( $_POST['kleingarten_meter_reading_date'] ) );
				}

				// ... and finally try to save it:
				$save_reading_result
					//= $this->save_meter_reading_by_token( $submitted_token,
					= Kleingarten_Meter::save_meter_reading_by_token( $submitted_token,
					$submitted_reading, $submitted_date );
				if ( is_wp_error( $save_reading_result ) ) {
					$messages = $save_reading_result->get_error_messages();
					$error_counter ++;
				}

			}

		}

		ob_start();

		?>
        <div class="kleingarten-submit-meter-reading-form-section"><?php

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
                           value="<?php esc_attr_e( 'Submit',
						       'kleingarten' ); ?>">
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
	 * Callback for shortcode kleingarten_my_plot. Displays member profile.
	 *
	 * @return string  HTML output
	 */
	public function kleingarten_my_plot_callback( $atts ) {

		// Extract shortcode attributes
		$atts = shortcode_atts( array(
			'allow_reading_submission' => 'false',
			'checkbox_label'           => esc_html( __( 'I have checked the date and meter reading. Both are correct.',
				'kleingarten' ) ),
		),
			$atts
		);

		$html         = '';

		ob_start();

		switch ( is_user_logged_in() ) {

			// For logged in users only:
			case true:

				$gardener = new Kleingarten_Gardener( get_current_user_id() );
				$plot = new Kleingarten_Plot( $gardener->plot );

				// If user has plot assigned get its meters...
				if ( $gardener->plot > 0 ) {
					$assigned_meters = $plot->get_assigned_meters();
					// ... but if user has no plot assigned, set an empty array
					// to prevent our loops from throwing warnings later:
				} else {
					$assigned_meters = array();
				}

				$save_reading_result = 0;
				$submitted           = false;

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
								= sanitize_text_field( wp_unslash( $_POST['kleingarten_inline_meter_reading_submission_form_date'] ) );
						}

						// Finally try to save it an save the result for later:
						$save_reading_result
							= $this->save_meter_reading_from_inline_form( $submitted_meter_id,
							$submitted_date, $submitted_reading,
							get_current_user_id(), $reading_data_checked );

					}

				}

				?>

                <div class="kleingarten-my-plot-section">

                    <?php
                    /*
                    ?>
                    <h2><?php echo esc_html( __( 'Your Plot',
							'kleingarten' ) ); ?></h2>
                    <p><?php esc_html_e( 'This plot is assigned to you.',
							'kleingarten' ); ?></p>
					<?php
                    */

					// If there were any errors on trying to save new reading...
					if ( is_wp_error( $save_reading_result ) ) {

						// ... get the error messages and corresponding error data:
						$error_codes = $save_reading_result->get_error_codes();

						// ... let's look at every single error code we received:
						echo '<ul class="kleingarten-inline-submit-meter-reading-messages">';
						foreach ( $error_codes as $error_code ) {

							$error_message
								= $save_reading_result->get_error_message( $error_code );

							echo '<li>';
							echo esc_html( __( 'Error', 'kleingarten' ) )
							     . ': ';
							echo esc_html( $error_message );
							echo '</li>';

						}
						echo '</ul>';

						// If the form was submitted but there were no errors, print a success message:
					} elseif ( $submitted === true ) {
						echo '<ul class="kleingarten-inline-submit-meter-reading-messages">';
						echo '<li>';
						echo esc_html( __( 'Reading submitted successfully',
							'kleingarten' ) );
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
								if ( isset( $gardener->plot )
								     && $gardener->plot != 0 ) {
									echo esc_html( $plot->get_title() );
								} else {
									esc_html_e( 'There is no plot assigned to you.',
										'kleingarten' );
								}
								?>
                            </td>
                        </tr>
						<?php

						$i = 0;
						foreach ( $assigned_meters as $assigned_meter ) {

							$meter = new Kleingarten_Meter( $assigned_meter );

							$i ++;
							?>
                            <tr>
                                <th>
									<?php
									echo esc_html( $i . '. ' );
									esc_html_e( 'Meter', 'kleingarten' );

									$wp_date_format
										= get_option( 'date_format' );
									$most_recent_reading
										= $meter->get_most_recent_reading();
									$most_recent_reading_value
										= $most_recent_reading['reading'];
									$most_recent_reading_date
										= $most_recent_reading['date'];
									?>
                                </th>
                                <td>
									<?php

									echo '<p>';
									echo esc_html( $meter->get_title() );
									echo '</p>';

									if ( $meter->count_readings() > 0 ) {

										echo '<p>';
										echo esc_html( __( 'Last known reading',
												'kleingarten' ) ) . ':<br>'
										     . esc_html( $most_recent_reading_value )
										     . ' '
										     . esc_html( $meter->get_unit() )
										     . ' ' . esc_html( __( 'as of',
												'kleingarten' ) ) . ' '
										     . esc_html( wp_date( $wp_date_format,
												$most_recent_reading_date ) );
										echo '</p>';

									} else {
										echo '<p>'
										     . esc_html_e( 'No reading so far.',
												'kleingarten' ) . '</p>';
									}

									if ( $atts['allow_reading_submission']
									     == 'true' ) {
										?>
                                        <div class="kleingarten-inline-meter-reading-submission-form">
                                            <form method="post"
                                                  action="<?php echo esc_url( get_permalink() ); ?>">
                                                <p>
                                                    <input type="hidden"
                                                           name="kleingarten_inline_meter_reading_submission_form_meter_to_update"
                                                           value="<?php echo esc_attr( $assigned_meter ); ?>">
                                                    <input name="kleingarten_inline_meter_reading_submission_form_date"
                                                           type="date"
                                                           value="<?php echo esc_attr( gmdate( "Y-m-d" ) ); ?>"
                                                           required>
                                                    <input name="kleingarten_inline_meter_reading_submission_form_reading_value"
                                                           type="number"
                                                           required>
                                                </p>
                                                <p>
                                                    <label>
                                                        <input name="kleingarten_inline_meter_reading_submission_form_reading_data_checked"
                                                               type="checkbox"
                                                               required>
														<?php echo esc_html( $atts['checkbox_label'] ); ?>
                                                    </label>
                                                </p>
                                                <p>
                                                    <input name="kleingarten_inline_meter_reading_submission_form_submit"
                                                           type="submit"
                                                           value="<?php esc_html_e( 'Submit Reading',
														       'kleingarten' ); ?>">
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
	 * Processes reading data from inline form.
	 *
	 * @param $meter_id
	 * @param $reading_date
	 * @param $reading_value
	 * @param $user_id
	 * @param $reading_data_checked
	 *
	 * @return string|WP_Error
	 */
	private function save_meter_reading_from_inline_form(
		$meter_id, $reading_date, $reading_value, $user_id,
		$reading_data_checked = true
	) {

		$errors = new WP_Error();
		$meter  = new Kleingarten_Meter( $meter_id );

		// If meter is not assigned to user by plot, stop right away:
		if ( ! $meter->may_be_updated_by_user( $user_id ) ) {
			$errors->add( 'kleingarten_inline_meter_reading_not_your_plot',
				__( 'You may not send readings for plots that are not assigned to you.',
					'kleingarten' ) );

			return $errors;
		}

		if ( $reading_data_checked === true ) {

			$sanitized_data = array();

			// Check basic pre-conditions (parameters are available):
			if ( isset( $meter_id ) && $meter_id > 0 && isset( $reading_date )
			     && isset( $reading_value )
			     && isset( $user_id )
			     && $user_id > 0 ) {

				// Sanitize data:
				/*
				$sanitized_data['date'] = strtotime( sanitize_text_field( wp_unslash( $reading_date ) ) );
				$sanitized_data['value'] = absint( wp_unslash( $reading_value ) );
				$sanitized_data['by'] = absint( wp_unslash( $user_id ) );
				return $meter->add_reading( $sanitized_data['value'], $sanitized_data['date'], $sanitized_data['by'] );
				*/

				// Add the the reading (Method will return a proper WP_Error
				// object on failure.):
				return $meter->add_reading( $reading_value, strtotime( $reading_date ),
					$user_id );

			}

			// If we got here that means there was data missing:
			$errors->add( 'kleingarten_inline_meter_reading_form_missing_data',
				__( 'Please fill out the form completely.', 'kleingarten' ),
				$meter_id );

			return $errors;

		} else {
			$errors->add( 'kleingarten_inline_meter_reading_form_data_not_checked',
				__( 'Please confirm that you checked the data for correctness.',
					'kleingarten' ), $meter_id );

			return $errors;
		}

	}

	function kleingarten_private_content_callback ($attr, $content = null) {

		extract(shortcode_atts(array(
			'refusal_output' => __( 'For members only.', 'kleingarten' ),
		), $attr));

		if ( current_user_can( 'read_private_posts' ) && ! is_null( $content ) && ! is_feed() ) {
            return $content;
		}

		return ( $refusal_output );

	}

}

