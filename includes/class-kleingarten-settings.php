<?php
/**
 * Settings class file.
 *
 * @package Kleingarten Plugin/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class.
 */
class Kleingarten_Settings {

	/**
	 * The single instance of Kleingarten_Settings.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_instance = null; //phpcs:ignore

	/**
	 * The main plugin object.
	 *
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 *
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	/**
	 * Constructor function.
	 *
	 * @param   object  $parent  Parent object.
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;

		$this->base = 'kleingarten_';

		// Initialise settings.
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add settings page to menu.
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page.
		add_filter(
			'plugin_action_links_' . plugin_basename( $this->parent->file ),
			array(
				$this,
				'add_settings_link',
			)
		);

		// Configure placement of plugin settings page. See readme for implementation.
		add_filter( $this->base . 'menu_settings',
			array( $this, 'configure_settings' ) );
	}

	/**
	 * Main Kleingarten_Settings Instance
	 *
	 * Ensures only one instance of Kleingarten_Settings is loaded or can be loaded.
	 *
	 * @param   object  $parent  Object instance.
	 *
	 * @return object Kleingarten_Settings instance
	 * @since 1.0.0
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
	 * Initialise settings
	 *
	 * @return void
	 */
	public function init_settings() {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Build settings fields
	 *
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {

		$settings['kleingarten-club'] = array(
			'title'       => __( 'Club', 'kleingarten' ),
			'description' => __( 'Customise the plugin to suit the circumstances of your club.',
				'kleingarten' ),
			'fields'      => array(
				array(
					'id'          => 'available_positions',
					'label'       => __( 'Available Positions', 'kleingarten' ),
					'description' => __( 'Available postitions in the club. One position per line.',
						'kleingarten' ),
					'default'     => '',
					'placeholder' => __( "e.g. chairman\r\ntreasurer\r\nsecretary\r\n...",
						'kleingarten' ),
					'callback'    => array(
						$this,
						'available_positions_sanitize_callback'
					),
				),
			),
		);

		$settings['kleingarten-plots'] = array(
			'title'       => __( 'Plots', 'kleingarten' ),
			'description' => __( 'Configure plots and supply meters.',
				'kleingarten' ),
			'fields'      => array(
				array(
					'id'          => 'units_available_for_meters',
					'label'       => __( 'Available meter types / units',
						'kleingarten' ),
					'description' => __( 'Define which units are available for supply meters. At the same time, define the available meter types. One unit per line.',
						'kleingarten' ),
					'default'     => '',
					'placeholder' => __( "e.g. kWh\r\nm3\r\n...",
						'kleingarten' ),
					'callback'    => array(
						$this,
						'units_available_for_meters_callback'
					),
				),
				array(
					'id'          => 'meter_reading_submission_token_time_to_live',
					'label'       => __( 'Token Time-To-Live', 'kleingarten' ),
					'description' => __( 'How many days should a token be usable?',
						'kleingarten' ),
					'default'     => '10',
					'placeholder' => '',
					'callback'    => array(
						$this,
						'meter_reading_submission_token_time_to_live_callback'
					),
				),
			),
		);

		$settings['kleingarten-members'] = array(
			'title'       => __( 'Members', 'kleingarten' ),
			'description' => __( 'Control how the plugin handles user accounts.',
				'kleingarten' ),
			'fields'      => array(
				array(
					'id'          => 'login_page',
					'label'       => __( 'Login Page', 'kleingarten' ),
					'description' => __( 'Page with login shortcode.',
						'kleingarten' ),
					'default'     => '',
					'callback'    => array(
						$this,
						'login_page_sanitize_callback'
					),
				),
				array(
					'id'          => 'anti_spam_challenge',
					'label'       => __( 'Antispam Question', 'kleingarten' ),
					'description' => __( 'New members must answer this question on registration.',
						'kleingarten' ),
					'default'     => '',
					'placeholder' => __( 'In which city is our club located?', 'kleingarten' ),
					'callback'    => array(
						$this,
						'anti_spam_challenge_sanitize_callback'
					),
				),
				array(
					'id'          => 'anti_spam_response',
					'label'       => __( 'Antispam Answer', 'kleingarten' ),
					'description' => __( 'The correct answer on your antispam question.',
						'kleingarten' ),
					'default'     => '',
					'placeholder' => __( 'Berlin', 'kleingarten' ),
					'callback'    => array(
						$this,
						'anti_spam_response_sanitize_callback'
					),
				),
			),
		);

		$settings['kleingarten-content'] = array(
			'title'       => esc_html( __( 'Content', 'kleingarten' ) ),
			'description' => esc_html( __( 'Set up how the plugin will deal with your content.',
				'kleingarten' ) ),
			'fields'      => array(
				array(
					'id'          => 'post_types_with_auto_likes_shortcode',
					'label'       => esc_html( __( 'Post type with like function',
						'kleingarten' ) ),
					'description' => esc_html( __( 'Select for which post types the like box shall be activated.',
						'kleingarten' ) ),
					'default'     => '',
					'callback'    => array(
						$this,
						'post_types_with_auto_likes_shortcode_callback'
					),
				),
				array(
					'id'          => 'auto_likes_shortcode_position',
					'label'       => esc_html( __( 'Like box position',
						'kleingarten' ) ),
					'description' => esc_html( __( 'Select where to put the like box.',
						'kleingarten' ) ),
					'default'     => 'top',
					'callback'    => array(
						$this,
						'auto_likes_shortcode_position_callback'
					),
				),
			),
		);

		$settings['kleingarten-tasks'] = array(
			'title'       => esc_html( __( 'Tasks', 'kleingarten' ) ),
			'description' => esc_html( __( 'Set up how you want to organize your tasks.',
				'kleingarten' ) ),
			'fields'      => array(
				array(
					'id'          => 'show_status_in_admin_menu',
					'label'       => esc_html( __( 'Allow custom status.', 'kleingarten' ,
						'kleingarten' ) ),
					'description' => esc_html( __( 'Shows status in admin menu to allow custom status.',
						'kleingarten' ) ),
					'default'     => 'on',
					'callback'    => array(
						$this,
						'show_status_in_admin_menu_callback'
					),
				),
			),
		);

		$settings['kleingarten-nofifications'] = array(
			'title'       => esc_html( __( 'Nofifications', 'kleingarten' ) ),
			'description' => esc_html( __( 'Set up email notifications.',
				'kleingarten' ) ),
			'fields'      => array(

				array(
					'id'          => 'send_account_registration_notification',
					'label'       => esc_html( __( 'Registration notification',
						'kleingarten' ) ),
					'description' => esc_html( __( 'Send an email notification on user registration.',
						'kleingarten' ) ),
					'default'     => '',
					'callback'    => array(
						$this,
						'send_account_registration_notification_callback'
					),
				),
				array(
					'id'          => 'account_registration_notification_subject',
					'label'       => esc_html( __( 'Registration notification subject',
						'kleingarten' ) ),
					// translators: Fake! This is not a real placeholder.
					'description' => esc_html( __( 'Use %s as a placeholder for your website title.',
						'kleingarten' ) ),
					'placeholder' => esc_html( __( 'Subject',
						'kleingarten' ) ),
					// translators: This not a placeholder. This is a sample text.
					'default'     => esc_html( __( 'Registration received - %s',
						'kleingarten' ) ),
					'callback'    => array(
						$this,
						'account_registration_notification_subject_callback'
					),
				),
				array(
					'id'          => 'account_registration_notification_message',
					'label'       => esc_html( __( 'Registration notification message',
						'kleingarten' ) ),
					// translators: Fake! This is not a real placeholder.
					'description' => esc_html( __( 'Use %s as a placeholder for your website title.',
						'kleingarten' ) ),
					'placeholder' => esc_html( __( 'Put your message here.',
						'kleingarten' ) ),
					// translators: This not a placeholder. This is a sample text.
					'default'     => esc_html( __( 'Thank you for your registration on %s.',
						'kleingarten' ) ),
					'callback'    => array(
						$this,
						'account_registration_notification_message_callback'
					),
				),

				array(
					'id'          => 'send_account_activation_notification',
					'label'       => esc_html( __( 'Activation notification',
						'kleingarten' ) ),
					'description' => esc_html( __( 'Send an email notification when user account changes from pending to active.',
						'kleingarten' ) ),
					'default'     => '',
					'callback'    => array(
						$this,
						'send_account_activation_notification_callback'
					),
				),
				array(
					'id'          => 'account_activation_notification_subject',
					'label'       => esc_html( __( 'Activation notification subject',
						'kleingarten' ) ),
					// translators: %s is replaced with website title
					'description' => esc_html( __( 'Use %s as a placeholder for your website title.',
						'kleingarten' ) ),
					'placeholder' => esc_html( __( 'Subject',
						'kleingarten' ) ),
					// translators: This not a placeholder. This is a sample text.
					'default'     => esc_html( __( 'Your user account has been activated - %s',
						'kleingarten' ) ),
					'callback'    => array(
						$this,
						'account_activation_notification_subject_callback'
					),
				),
				array(
					'id'          => 'account_activation_notification_message',
					'label'       => esc_html( __( 'Activation notification message',
						'kleingarten' ) ),
					// translators: Fake! This is not a real placeholder.
					'description' => esc_html( __( 'Use %s as a placeholder for your website title.',
						'kleingarten' ) ),
					//'type'        => 'textarea',
					// translators: %s is replaced with website title
					'default'     => esc_html( __( 'Your user account on %s has been activated.',
						'kleingarten' ) ),
					'placeholder' => esc_html( __( 'Put your message here.',
						'kleingarten' ) ),
					'callback'    => array(
						$this,
						'account_activation_notification_message_callback'
					),
				),

				array(
					'id'          => 'send_new_post_notification',
					'label'       => esc_html( __( 'New post notification',
						'kleingarten' ) ),
					'description' => esc_html( __( 'Send an email notification when a new post is published.',
						'kleingarten' ) ),
					'default'     => '',
					'callback'    => array(
						$this,
						'send_new_post_notification_callback'
					),
				),
				array(
					'id'          => 'send_new_post_notification_post_type_selection',
					'label'       => esc_html( __( 'Post types to notify about',
						'kleingarten' ) ),
					'description' => esc_html( __( 'Select for which post types to send a notification for.',
						'kleingarten' ) ),
					'default'     => '',
					'callback'    => array(
						$this,
						'send_new_post_notification_post_type_selection_callback'
					),
				),
				array(
					'id'          => 'new_post_notification_subject',
					'label'       => esc_html( __( 'New post notification subject',
						'kleingarten' ) ),
					// translators: %s is replaced with website title
					'description' => esc_html( __( 'Use %s as a placeholder for your website title.',
						'kleingarten' ) ),
					'placeholder' => esc_html( __( 'Subject',
						'kleingarten' ) ),
					// translators: This not a placeholder. This is a sample text.
					'default'     => esc_html( __( 'New Post - %s',
						'kleingarten' ) ),
					'callback'    => array(
						$this,
						'new_post_notification_subject_callback'
					),
				),
				array(
					'id'          => 'new_post_notification_message',
					'label'       => esc_html( __( 'New post notification message',
						'kleingarten' ) ),
					// translators: Fake! These are no real placeholders.
					'description' => esc_html( __( 'Placeholders: %1$s for post title, %2$s for post URL, %3$s for website title.',
						'kleingarten' ) ),
					// translators: This not a placeholder. This is a sample text.
					'default'     => esc_html( __( 'There is a new post on %3$s',
						'kleingarten' ) ),
					'placeholder' => esc_html( __( 'Put your message here.',
						'kleingarten' ) ),
					'callback'    => array(
						$this,
						'new_post_notification_message_callback'
					),
				),

			),

		);

		$settings['kleingarten-advanced'] = array(
			'title'       => esc_html( __( 'Advanced', 'kleingarten' ) ),
			'description' => esc_html( __( 'Advanced settings for Kleingarten.',
				'kleingarten' ) ),
			'fields'      => array(
				array(
					'id'          => 'show_footer_credits',
					'label'       => esc_html( __( 'Show credits in footer',
						'kleingarten' ) ),
					'description' => esc_html( __( 'Developing Kleingarten takes time and money. Please support the further development by clicking on the link in the footer.',
						'kleingarten' ) ),
					'default'     => 'on',
					'callback'    => array(
						$this,
						'show_footer_credits_callback'
					),
				),
			),
		);

		return apply_filters( $this->parent->_token . '_settings_fields',
			$settings );
	}

	/**
	 * Add settings page to admin menu
	 *
	 * @return void
	 */
	public function add_menu_item() {

		$args = $this->menu_settings();

		// Do nothing if wrong location key is set.
		if ( is_array( $args ) && isset( $args['location'] )
		     && function_exists( 'add_' . $args['location'] . '_page' )
		) {
			switch ( $args['location'] ) {
				case 'options':
				case 'submenu':
					$page = add_submenu_page( $args['parent_slug'],
						$args['page_title'], $args['menu_title'],
						$args['capability'], $args['menu_slug'],
						$args['function'] );
					break;
				case 'menu':
					$page = add_menu_page( $args['page_title'],
						$args['menu_title'], $args['capability'],
						$args['menu_slug'], $args['function'],
						$args['icon_url'], $args['position'] );
					break;
				default:
					return;
			}
			add_action( 'admin_print_styles-' . $page,
				array( $this, 'settings_assets' ) );
		}
	}

	/**
	 * Prepare default settings page arguments
	 *
	 * @return mixed|void
	 */
	private function menu_settings() {
		return apply_filters(
			$this->base . 'menu_settings',
			array(
				'location'    => 'options',
				// Possible settings: options, menu, submenu.
				'parent_slug' => 'options-general.php',
				'page_title'  => __( 'Kleingarten', 'kleingarten' ),
				'menu_title'  => __( 'Kleingarten', 'kleingarten' ),
				'capability'  => 'manage_options',
				'menu_slug'   => $this->parent->_token . '_settings',
				'function'    => array( $this, 'settings_page' ),
				'icon_url'    => '',
				'position'    => null,
			)
		);
	}

	/**
	 * Container for settings page arguments
	 *
	 * @param   array  $settings  Settings array.
	 *
	 * @return array
	 */
	public function configure_settings( $settings = array() ) {
		return $settings;
	}

	/**
	 * Load settings JS & CSS
	 *
	 * @return void
	 */
	public function settings_assets() {

		// We're including the farbtastic script & styles here because they're needed for the colour picker
		// If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the wpt-admin-js script below.
		//wp_enqueue_style( 'farbtastic' );
		//wp_enqueue_script( 'farbtastic' );

		// We're including the WP media scripts here because they're needed for the image upload field.
		// If you're not including an image upload then you can leave this function call out.
		//wp_enqueue_media();

		//wp_register_script( $this->parent->_token . '-settings-js', $this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js', array( 'farbtastic', 'jquery' ), '1.0.0', true );
		//wp_enqueue_script( $this->parent->_token . '-settings-js' );
	}

	/**
	 * Add settings link to plugin list table
	 *
	 * @param   array  $links  Existing links.
	 *
	 * @return array        Modified links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page='
		                 . $this->parent->_token . '_settings">'
		                 . esc_html__( 'Settings', 'kleingarten' ) . '</a>';
		//array_push( $links, $settings_link ); // Original
		$links[] = $settings_link;             // PHPStrom's proposal

		return $links;
	}

	/**
	 * Register plugin settings
	 *
	 * @return void
	 */
	public function register_settings() {
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab.
			//phpcs:disable
			$current_section = '';
			if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
				$current_section = sanitize_text_field( $_POST['tab'] );
			} else {
				if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
					$current_section = sanitize_text_field( $_GET['tab'] );
				}
			}
			//phpcs:enable

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section !== $section ) {
					continue;
				}

				// Add section to page.
				add_settings_section( $section, $data['title'],
					array( $this, 'settings_section' ),
					$this->parent->_token . '_settings' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field.
					/*
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}
					*/

					$args = array();
					if ( isset( $field['callback'] ) ) {
						$args['sanitize_callback'] = $field['callback'];
					}
					if ( isset( $field['default'] ) ) {
						$args['default'] = $field['default'];
					}

					// Register field.
					$option_name = $this->base . $field['id'];
					//register_setting( $this->parent->_token . '_settings',
					//	$option_name, $validation );
					register_setting( $this->parent->_token . '_settings',
						$option_name, $args );

					// Add field to page.
					add_settings_field(
						$field['id'],
						$field['label'],
						array( $this->parent->admin, 'display_field' ),
						$this->parent->_token . '_settings',
						$section,
						array(
							'field'  => $field,
							'prefix' => $this->base,
						)
					);
				}

				if ( ! $current_section ) {
					break;
				}
			}
		}
	}

	/**
	 * Settings section.
	 *
	 * @param   array  $section  Array of section ids.
	 *
	 * @return void
	 */
	public function settings_section( $section ) {
		$html = '<p> '
		        . esc_html( $this->settings[ $section['id'] ]['description'] )
		        . '</p>' . "\n";
		echo $html; //phpcs:ignore
	}

	/**
	 * Load settings page content.
	 *
	 * @return void
	 */
	public function settings_page() {

		// Build page HTML.
		$html = '<div class="wrap" id="' . $this->parent->_token . '_settings">'
		        . "\n";
		$html .= '<div class="kleingarten-admin-wrapper">' . "\n";
		$html .= '<div class="kleingarten-admin-main-section">';
		$html .= '<h1>' . esc_html__( 'Settings', 'kleingarten' )
		         . '&nbsp;›&nbsp;Kleingarten</h1>' . "\n";

		$tab = '';
		//phpcs:disable
		if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
			$tab .= sanitize_text_field( $_GET['tab'] );
		}
		//phpcs:enable

		// Show page tabs.
		if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

			$html .= '<h2 class="nav-tab-wrapper">' . "\n";

			$c = 0;
			foreach ( $this->settings as $section => $data ) {

				// Set tab class.
				$class = 'nav-tab';
				if ( ! isset( $_GET['tab'] ) ) { //phpcs:ignore
					if ( 0 === $c ) {
						$class .= ' nav-tab-active';
					}
				} else {
					if ( isset( $_GET['tab'] )
					     && $section == $_GET['tab']
					) { //phpcs:ignore
						$class .= ' nav-tab-active';
					}
				}

				// Set tab link.
				$tab_link = add_query_arg( array( 'tab' => $section ) );
				if ( isset( $_GET['settings-updated'] ) ) { //phpcs:ignore
					$tab_link = remove_query_arg( 'settings-updated',
						$tab_link );
				}

				// Output tab.
				$html .= '<a href="' . $tab_link . '" class="'
				         . esc_attr( $class ) . '">'
				         . esc_html( $data['title'] ) . '</a>' . "\n";

				++ $c;

			}

			$html .= '</h2>' . "\n";
		}


		//echo $this->parent->_token . '_settings' . '-options';
		$html .= '<form method="post" action="options.php" enctype="multipart/form-data">'
		         . "\n";

		// Get settings fields.
		ob_start();
		settings_fields( $this->parent->_token . '_settings' );
		do_settings_sections( $this->parent->_token . '_settings' );
		$html .= ob_get_clean();

		$html .= '<p class="submit">' . "\n";
		$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab )
		         . '" />' . "\n";
		$html .= '<input name="Submit" type="submit" class="button-primary" value="'
		         . esc_attr__( 'Save Settings', 'kleingarten' ) . '" />'
		         . "\n";
		$html .= '</p>' . "\n";
		$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";

		$html .= '<div class="kleingarten-admin-sidebar">';
		$html .= '<a target="_blank" href="https://www.wp-kleingarten.de">';
		$html .= '<img src=' . esc_url( plugin_dir_url( __DIR__ ) )
		         . 'assets/Kleingarten_Logo_200px.png>';
		$html .= '</a>';
		$html .= '</div>' . "\n";

		$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";

		echo $html; //phpcs:ignore
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__,
			esc_html( __( 'Cloning of Kleingarten_API is forbidden.',
				'kleingarten' ) ), esc_attr( $this->parent->_version ) );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__,
			esc_html( __( 'Unserializing instances of Kleingarten_API is forbidden.',
				'kleingarten' ) ), esc_attr( $this->parent->_version ) );
	} // End __wakeup()

	/**
	 * Sanitize "Available Positions" setting
	 *
	 * @param $input string Setting input to sanitize.
	 *
	 * @return string Sanitized setting.
	 */
	public function available_positions_sanitize_callback( $input ) {
		return sanitize_textarea_field( $input );
	}

	/**login_page_sanitize_callback
	 * Sanitize "Login Page" setting
	 *
	 * @param $input integer Setting input to sanitize.
	 *
	 * @return int Sanitized setting.
	 */
	public function login_page_sanitize_callback( $input ) {
		return absint( $input );
	}

	/**
	 * Sanitize "Post types with auto likes shortcode" setting
	 *
	 * @param $input array Setting input to sanitize.
	 *
	 * @return array|bool
	 */
	public function post_types_with_auto_likes_shortcode_callback( $input ) {

		// Check every single submitted post type...
		if ( is_array( $input ) ) {

			foreach ( $input as $posttype ) {

				// ... if only a single post type does not exist keep the last setting.
				if ( ! post_type_exists( $posttype ) ) {
					return get_option( 'kleingarten_post_types_with_auto_likes_shortcode' );
				}

			}

		} else {
			return false;
		}

		return $input;
	}

	/**
	 * Sanitize "Auto likes Shortcode Position" setting
	 *
	 * @param $input string Setting input to sanitize.
	 *
	 * @return string Sanitized setting.
	 */
	public function auto_likes_shortcode_position_callback( $input ) {

		// If setting is anything else than "top" or "bottom" set it to "top"
		if ( $input != 'top' && $input != 'bottom' ) {
			return 'top';
		}

		return $input;
	}

	/**
	 * Sanitize "Send registration mail" setting
	 *
	 * @param $input string Setting input to sanitize.
	 *
	 * @return string Sanitized setting.
	 */
	public function send_account_registration_notification_callback( $input ) {

		if ( $input != '' && $input != 'on' ) {
			return '';
		}

		return $input;
	}

	/**
	 * Sanitize "Registration mail subject" setting
	 *
	 * @param $input string Setting input to sanitize.
	 *
	 * @return string Sanitized setting.
	 */
	public function account_registration_notification_subject_callback( $input
	) {

		return sanitize_text_field( $input );
	}

	/**
	 * Sanitize "Registration mail message" setting
	 *
	 * @param $input string Setting input to sanitize.
	 *
	 * @return string Sanitized setting.
	 */
	public function account_registration_notification_message_callback( $input
	) {

		return $input;
	}

	/**
	 * Sanitize "Send activation mail" setting
	 *
	 * @param $input string Setting input to sanitize.
	 *
	 * @return string Sanitized setting.
	 */
	public function send_account_activation_notification_callback( $input ) {

		if ( $input != '' && $input != 'on' ) {
			return '';
		}

		return $input;
	}

	/**
	 * Sanitize "Activation mail subject" setting
	 *
	 * @param $input string Setting input to sanitize.
	 *
	 * @return string Sanitized setting.
	 */
	public function account_activation_notification_subject_callback( $input ) {

		return sanitize_text_field( $input );
	}

	/**
	 * Sanitize "Activation mail message" setting
	 *
	 * @param $input string Setting input to sanitize.
	 *
	 * @return string Sanitized setting.
	 */
	public function account_activation_notification_message_callback( $input ) {

		return $input;
	}

	/**
	 * Sanitize "Send new post mail" setting
	 *
	 * @param $input string Setting input to sanitize.
	 *
	 * @return string Sanitized setting.
	 */
	public function send_new_post_notification_callback( $input ) {

		if ( $input != '' && $input != 'on' ) {
			return '';
		}

		return $input;
	}

	/**
	 * Sanitize "New Post Notification post type selection" setting
	 *
	 * @param $input array Setting input to sanitize.
	 *
	 * @return array|string
	 */
	public function send_new_post_notification_post_type_selection_callback(
		$input
	) {

		$posttypes = $input;

		if ( $input ) {

			// Check every single submitted post type...
			foreach ( $posttypes as $posttype ) {

				// ... if only a single post type does not exist keep the last setting.
				if ( ! post_type_exists( $posttype ) ) {
					return get_option( 'kleingarten_post_types_with_auto_likes_shortcode' );
				}

			}

		}

		return $input;
	}

	/**
	 * Sanitize "New post mail subject" setting
	 *
	 * @param $input string Setting input to sanitize.
	 *
	 * @return string Sanitized setting.
	 */
	public function new_post_notification_subject_callback( $input ) {

		return sanitize_text_field( $input );
	}

	/**
	 * Sanitize "New post mail message" setting
	 *
	 * @param $input string Setting input to sanitize.
	 *
	 * @return string Sanitized setting.
	 */
	public function new_post_notification_message_callback( $input ) {

		return $input;
	}

	/**
	 * Sanitize "Units for meters" setting
	 *
	 * @param $input string Setting input to sanitize.
	 *
	 * @return string Sanitized setting.
	 */
	public function units_available_for_meters_callback( $input ) {

		return sanitize_textarea_field( $input );
	}

	/**
	 * Sanitize "Token Time-To-Live" setting
	 *
	 * @param $input string Setting input to sanitize.
	 *
	 * @return int Sanitized setting.
	 */
	public function meter_reading_submission_token_time_to_live_callback( $input
	) {

		return absint( $input );
	}

	/**
	 * Sanitize "Show credits in footer option" setting
	 *
	 * @param $input int Setting input to sanitize.
	 *
	 * @return int Sanitized setting.
	 * @since 1.1.1
	 *
	 */
	public function show_footer_credits_callback( $input ) {

		if ( $input != '' && $input != 'on' ) {
			return '';
		}

		return $input;

	}

	/**
	 * Sanitize "Show credits in footer option" setting
	 *
	 * @param $input int Setting input to sanitize.
	 *
	 * @return int Sanitized setting.
	 * @since 1.1.1
	 *
	 */
	public function show_status_in_admin_menu_callback( $input ) {

		if ( $input != '' && $input != 'on' ) {
			return '';
		}

		return $input;

	}

	/**
	 * Sanitize "Antispam Challenge" option callback
	 *
	 * @return mixed
	 */
	public function anti_spam_challenge_sanitize_callback( $input ) {
		return sanitize_text_field( $input );
	}

	/**
	 * Sanitize "Antispam Challenge" option callback
	 *
	 * @return mixed
	 */
	public function anti_spam_response_sanitize_callback( $input ) {
		return sanitize_text_field( $input );
	}

}
