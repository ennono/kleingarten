<?php
/**
 * Main plugin class file.
 *
 * @package Kleingarten Plugin/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class Kleingarten {

	/**
	 * The single instance of Kleingarten.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_instance = null; //phpcs:ignore

	/**
	 * Local instance of Kleingarten_Admin_API
	 *
	 * @var Kleingarten_Admin_API|null
	 */
	public $admin = null;

	/**
	 * Settings class object
	 *
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * Admin pages class object
	 *
	 * @var     object
	 * @access  public
	 * @since   1.1.2
	 */
	public $admin_pages = null;

	/**
	 * The version number.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version; //phpcs:ignore

	/**
	 * The token.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token; //phpcs:ignore

	/**
	 * The main plugin file.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for JavaScripts.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor funtion.
	 *
	 * @param   string  $file     File constructor.
	 * @param   string  $version  Plugin version.
	 */
	public function __construct( $file = '', $version = '1.0.0' ) {
		$this->_version = $version;
		$this->_token   = 'kleingarten';

		// Load plugin environment variables.
		$this->file       = $file;
		$this->dir        = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/',
			$this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? ''
			: '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load frontend JS & CSS.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Load admin JS & CSS.
		add_action( 'admin_enqueue_scripts',
			array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts',
			array( $this, 'admin_enqueue_styles' ) );

		// Remove "Private" prefix
		add_filter( 'private_title_format',
			array( $this, 'change_private_title_prefix' ) );

		// Disable comments for vistors who are not logged in
		// WARNING: This assumes that the theme uses comments_open/pings_open functions!!
		// Otherwise, it probably won't work properly.
		add_filter( 'comments_open', array( $this, 'disable_comments' ), 20,
			2 );
		add_filter( 'pings_open', array( $this, 'disable_comments' ), 20, 2 );
		add_filter( 'get_comment_author',
			array( $this, 'extend_comment_author' ), 20, 3 );

		// Auto logout pending users immediatly after login
		add_action( 'wp_login', array( $this, 'logout_pending_gardeners' ), 10,
			2 );

		add_filter( 'show_admin_bar', array( $this, 'hide_admin_bar' ) );

		add_action( 'admin_init', array( $this, 'hide_admin_dashboard' ) );

		//add_action ('save_post', array ($this, 'send_new_post_nofification'), 10, 3);
		add_action( 'transition_post_status',
			array( $this, 'send_new_post_nofification' ), 10, 3 );

		add_filter( 'the_content',
			array( $this, 'add_likes_shortcode_to_selected_posttypes' ) );

		add_action( 'wp_footer', array( $this, 'printer_footer_credits' ),
			100 );

		add_action( 'template_redirect',
			array( $this, 'redirect_private_post_404_to_login_page' ) );

		add_action( 'wp_trash_post',
			array( 'Kleingarten_Plot', 'remove_gardener_assignments' ) );
		add_action( 'delete_post',
			array( 'Kleingarten_Plot', 'remove_gardener_assignments' ) );

		// Load API for generic admin functions.
		if ( is_admin() ) {
			$this->admin = new Kleingarten_Admin_API();
		}

		// Handle localisation.
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
	} // End __construct ()

	/**
	 * Load plugin textdomain
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function load_plugin_textdomain() {
		$domain = 'kleingarten';

		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain,
			WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale
			. '.mo' );
		load_plugin_textdomain( $domain, false,
			dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	/**
	 * Main Kleingarten Instance
	 *
	 * Ensures only one instance of Kleingarten is loaded or can be loaded.
	 *
	 * @param   string  $file     File instance.
	 * @param   string  $version  Version parameter.
	 *
	 * @return Object Kleingarten instance
	 * @see   Kleingarten()
	 * @since 1.0.0
	 * @static
	 */
	public static function instance( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}

		return self::$_instance;
	}

	/**
	 * Wrapper function to add custom user fields.
	 *
	 * @return Kleingarten_Userfields
	 */
	public function add_userfields() {

		return new Kleingarten_Userfields ();
	}

	/**
	 * Wrapper function to add post meta boxes.
	 *
	 * @return Kleingarten_Post_Meta
	 */
	public function add_post_meta() {

		return new Kleingarten_Post_Meta ();
	}

	/**
	 * Wrapper function to add custom user roles.
	 *
	 * @return Kleingarten_User_Roles
	 */
	public function add_user_roles() {

		return new Kleingarten_User_Roles ();
	}

	/**
	 * Wrapper function to add custom user fields.
	 *
	 * @return Kleingarten_Post_Types
	 */
	public function add_post_types() {

		return new Kleingarten_Post_Types ();
	} // End enqueue_styles ()

	/**
	 * Wrapper function to add custom shortcodes.
	 *
	 * @return Kleingarten_Shortcodes
	 */
	public function add_shortcodes() {

		return new Kleingarten_Shortcodes ();
	} // End enqueue_scripts ()

	/**
	 * Load frontend CSS.
	 *
	 * @access  public
	 * @return void
	 * @since   1.0.0
	 */
	public function enqueue_styles() {

		wp_register_style( $this->_token . '-frontend',
			esc_url( $this->assets_url ) . 'css/frontend.css', array(),
			$this->_version );
		wp_enqueue_style( $this->_token . '-frontend' );

	}

	/**
	 * Load frontend Javascript.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function enqueue_scripts() {
		wp_register_script( $this->_token . '-frontend',
			esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix
			. '.js', array( 'jquery' ), $this->_version, true );
		wp_enqueue_script( $this->_token . '-frontend' );

		wp_localize_script( $this->_token . '-frontend', 'kleingarten_frontend',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'kleingarten-likes-ajax-nonce' ),
			)
		);

	} // End enqueue_scripts ()

	/**
	 * Admin enqueue style.
	 *
	 * @param   string  $hook  Hook parameter.
	 *
	 * @return void
	 */
	public function admin_enqueue_styles( $hook = '' ) {

		wp_enqueue_style( 'wp-color-picker' );

		wp_register_style( $this->_token . '-admin',
			esc_url( $this->assets_url ) . 'css/admin.css', array(),
			$this->_version );
		wp_enqueue_style( $this->_token . '-admin' );

	} // End load_localisation ()

	/**
	 * Load admin Javascript.
	 *
	 * @access  public
	 *
	 * @param   string  $hook  Hook parameter.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function admin_enqueue_scripts( $hook = '' ) {
		global $post;
		wp_register_script( $this->_token . '-admin',
			esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix
			. '.js', array( 'jquery' ), $this->_version, true );
		wp_enqueue_script( $this->_token . '-admin' );

		wp_localize_script( $this->_token . '-admin', 'kleingarten_admin',
			array(
				'ajaxurl'          => admin_url( 'admin-ajax.php' ),
				'nonce'            => wp_create_nonce( 'kleingarten-admin-ajax-nonce' ),
				'trans_active'     => esc_html( __( 'Active', 'kleingarten' ) ),
				'trans_deactivate' => esc_html( __( 'Deactivate',
					'kleingarten' ) ),
				'trans_delete'     => esc_html( __( 'Delete', 'kleingarten' ) ),
				'trans_token'      => esc_html( __( 'Token', 'kleingarten' ) ),
				'trans_status'     => esc_html( __( 'Status', 'kleingarten' ) ),
				'trans_action'     => esc_html( __( 'Action', 'kleingarten' ) ),
				'trans_remove'     => esc_html( __( 'Remove', 'kleingarten' ) ),
				'trans_eg_water'    => esc_html( __( 'e.g. Water', 'e.g. Water' ) ),
				'trans_eg_l'    => esc_html( __( 'e.g. l', 'kleingarten' ) ),
				'trans_eg_245'    => esc_html( __( 'e.g. 2.45', 'kleingarten' ) ),
				'trans_eg_at_least_one_row' => esc_html( __( 'There must be at least one row.', 'kleingarten' ) ),
			)
		);

		wp_enqueue_script( $this->_token . '-admin-color-picker', esc_url( $this->assets_url ) . 'js/' . 'colorpicker.min.js', array( 'wp-color-picker' ), $this->_version, true  );

	}

	/**
	 * Load plugin localisation
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function load_localisation() {
		load_plugin_textdomain( 'kleingarten', false,
			dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__,
			esc_html( __( 'Cloning of Kleingarten is forbidden',
				'kleingarten' ) ), esc_attr( $this->_version ) );

	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__,
			esc_html( __( 'Unserializing instances of Kleingarten is forbidden',
				'kleingarten' ) ), esc_attr( $this->_version ) );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function install() {
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	private function _log_version_number() { //phpcs:ignore
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()

	/**
	 * Change "Private" prefix
	 *
	 * @access  public
	 * @return  string Custom prefix
	 * @since   1.0.0
	 */
	public function change_private_title_prefix() {
		// translators: Post title
		return __( 'For members only: %s', 'kleingarten' );
	}

	/**
	 * Disable comments for non-logged-in users
	 *
	 * @access  public
	 * @return  bool To enable or disable comments
	 * @since   1.0.0
	 */
	public function disable_comments( $open ) {

		// If user is logged in display comments as usual...
		if ( is_user_logged_in() ) {
			return $open;
		} // ... if user is not logged in hide comments area.
		else {
			return false;
		}

	}

	/**
	 * Enrich comments area with plot numbers after commenting gardener's name and hide comments area from non-logged-in visitors
	 *
	 * @access  public
	 *
	 * @param $author
	 * @param $comment_id
	 * @param $comment
	 *
	 * @return array|string Comments Array
	 * @since   1.0.0
	 */
	public function extend_comment_author( $author, $comment_id, $comment
	): array|string {

		do_action( 'qm/debug', 'User ID: ' . $comment->user_id );

		if ( isset( $comment->user_id ) && $comment->user_id > 0 ) {

			$author_id   = $comment->user_id;
			$author_plot = get_user_meta( $author_id, 'plot', true );

			if ( $author_plot != 0 && $author_plot != null
			     && $author_plot != '' ) {

				$author .= ' (' . esc_html__( 'Garden No.', 'kleingarten' )
				           . ' ' . esc_html( get_the_title( $author_plot ) )
				           . ')';

			}

		}

		return $author;

	}

	/**
	 * Logout pending gardeners
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function logout_pending_gardeners( $user_login, $user ) {

		// If user is pending, log him out!
		if ( in_array( 'kleingarten_pending', (array) $user->roles ) ) {
			wp_logout();
		}

	}

	/**
	 * Hide admin bar from gardeners
	 *
	 * @access  public
	 * @return  bool
	 * @since   1.0.0
	 */
	public function hide_admin_bar() {

		// Hide admin bar for kleingarten specific roles.
		// Native WordPress roles will still be shown the bar.
		$roles = [ 'kleingarten_pending', 'kleingarten_allotment_gardener' ];

		$response = false;

		if ( is_user_logged_in() ) {

			$user             = wp_get_current_user();
			$currentUserRoles = $user->roles;
			$isMatching       = array_intersect( $currentUserRoles, $roles );
			if ( ! $isMatching ) {
				$response = true;
			}

		}

		return $response;
	}

	/**
	 * Hide admin dashboard from gardeners.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function hide_admin_dashboard() {

		if ( is_admin() && ! current_user_can( 'administrator' )    // Calling admin page but being no admin?
		     && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX )     // Not doing AJAX stuff?
             && isset( $_SERVER['PHP_SELF'] )
             && ! str_contains( sanitize_urL( wp_unslash( $_SERVER['PHP_SELF'] ) ), 'admin-post.php' )     // Not just using admin-post.php for form processing?
		) {

            // Then redirect to homepage. /wp-admin is not the right place for you.
			wp_safe_redirect( home_url() );
			exit;
		}

	}

	/**
	 * Send email notifications on new post.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function send_new_post_nofification( $new_status, $old_status, $post
	) {

		$post_ID = $post->ID;

		// If notification on new post is disabled, stop here:
		if ( ! get_option( 'kleingarten_send_new_post_notification' ) ) {
			return;
		}

		// If post status did not change, stop here:
		if ( $old_status == $new_status ) {
			return;
		}

		// If this post is anything else than being published or being published as private, stop here:
		if ( $new_status != 'publish' && $new_status != 'private' ) {
			return;
		}

		// If making a published post private, stop here:
		if ( $old_status == 'publish' && $new_status == 'private' ) {
			return;
		}

		// If publishing a former private private, stop here:
		if ( $old_status == 'private' && $new_status == 'publish' ) {
			return;
		}

		// If this is just a revision, stop here:
		if ( wp_is_post_revision( $post_ID ) ) {
			return;
		}

		// If this is an auto save, stop here:
		if ( $post->post_status == 'auto-draft' ) {
			return;
		}

		// If post type is selected (see settings), stop now 
		$post_type = $post->post_type;
		if ( $post_type
		     == 'revision'
		) {                                // If this is a revision ...
			$post_type
				= get_post_type( $post->post_parent );        // ... get the intended post type from parent.
		}
		$seleted_post_types
			= get_option( 'kleingarten_send_new_post_notification_post_type_selection' );
		if ( ! $seleted_post_types ) {
			$seleted_post_types = array();
		}    // Build an empty array if nothing was selected via settings.
		if ( ! in_array( $post_type, $seleted_post_types ) ) {
			return;
		}

		// Build a list of recipients
		$recipients
			= Kleingarten_Gardeners::get_new_post_notification_recipients();

		// Set email subject
		$subject
			= esc_html( get_option( 'kleingarten_new_post_notification_subject' ) );
		if ( $subject == '' ) {
			// translators: Website title
			$subject = sprintf( __( 'There is a new post! - %s',
				'kleingarten' ), esc_html( get_bloginfo( 'name' ) ) );
		} else {
			$subject = sprintf( $subject, esc_html( get_bloginfo( 'name' ) ) );
		}

		// Build email body
		$body = get_option( 'kleingarten_new_post_notification_message' );
		if ( $body == '' ) {
			// translators: Website title
			$body = sprintf( __( 'There is a new post! - %s', 'kleingarten' ),
				esc_html( get_bloginfo( 'name' ) ) );
			ob_start();
			?>
            <p><strong><?php echo esc_html( __( 'We have a new post for you',
						'kleingarten' ) ) ?></strong></p>
            <p><?php echo esc_html( $post->post_title ); ?> &mdash; <a
                        href="<?php echo esc_url( get_permalink( $post_ID ) ); ?>"><?php echo esc_html( __( 'Read now!',
						'kleingarten' ) ); ?></a></p>
			<?php
			$body = ob_get_clean();
		} else {
			$body = sprintf( $body, esc_html( $post->post_title ),
				esc_url( get_permalink( $post_ID ) ),
				esc_html( get_bloginfo( 'name' ) ) );
		}

		// Set mail content type to text/html
		$site_name   = get_bloginfo( 'name' );
		$admin_email = get_bloginfo( 'admin_email' );
		$headers[]   = 'From: ' . $site_name . ' <' . $admin_email . '>';
		$headers[]   = 'Content-Type: text/html';
		$headers[]   = 'charset=UTF-8';

		// Finally, send  mails
		foreach ( $recipients as $recipient ) {
			wp_mail( $recipient->user_email, $subject, $body, $headers );
		}


		do_action( 'qm/debug', 'Finished.' );

	}

	/**
	 * Add likes area to selected post types
	 *
	 * @access  public
	 * @return  string Post content with likes shortcode added
	 * @since   1.0.0
	 */
	public function add_likes_shortcode_to_selected_posttypes( $content ) {

		global $post;
		if ( ! $post instanceof WP_Post ) {
			return $content;
		}

		$post_type = $post->post_type;

		if ( $post_type
		     == 'revision'
		) {                                // If this is a revision ...
			$post_type
				= get_post_type( $post->post_parent );        // ... get the intended post type from parent.
		}

		$post_types_with_auto_likes_shortcode
			      = get_option( 'kleingarten_post_types_with_auto_likes_shortcode' );
		$position = get_option( 'kleingarten_auto_likes_shortcode_position' );

		//      if ( is_array( $post_types_with_auto_likes_shortcode ) && empty ( $post_types_with_auto_likes_shortcode ) ) {

		if ( $post_types_with_auto_likes_shortcode
		     && in_array( $post_type,
				$post_types_with_auto_likes_shortcode ) ) {
			return match ( $position ) {
				'bottom' => $content . '[kleingarten_likes]',
				default => '[kleingarten_likes]' . $content,
			};

		} else {
			return $content;
		}

//        }

		// Just to be safe: Return the given content.
		//return $post_type . '  ' . $content;

	}

	/**
	 * Print credits in footer. To be used as callback.
	 *
	 * @since   1.1.1
	 */
	public function printer_footer_credits() {

		$option = get_option( 'kleingarten_show_footer_credits', 'on' );

		if ( $option === 'on' ) {

			?>

            <div style="background-color: black; text-align: center;">
                <a style="color: #48BB48;"
                   href="https://www.wp-kleingarten.de"><?php esc_html_e( 'Powered by Kleingarten &mdash; The WordPress Plugin for allotment gardeners',
						'kleingarten' ); ?></a>
            </div>

			<?php

		}

	}

	/**
	 * Prevent 404 for private posts. Redirect to login page instead.
	 *
	 * @since   1.1.1
	 */
	public function redirect_private_post_404_to_login_page() {

		global $wp_query, $wpdb;

		$cache_key = 'kleingarten_current_request';
		$cache_group = 'kleingarten';

		// Attempt to get cached data:
		$row = wp_cache_get( $cache_key, $cache_group );

		if ( false === $row ) {

			// Escaping by $wpdb->prepare unnecessary. There ist nothing to replace
			// and query string comes directly from $wp_query and therefore is
			// considered safe.
			$row = $wpdb->get_row( $wp_query->request );


			// Store data in cache
			wp_cache_set( $cache_key, $row, $cache_group, 30 ); // Cache for 1 minute
		}


		$statuses = array( 'private', 'inherit' );

		// If we encounter a 404...
		if ( is_404() ) {

			// ... and if we are trying to show a private post..
			if ( ! empty( $row->post_status )
			     && in_array( $row->post_status, $statuses ) ) {

				// ... redirect to login page in case it is defined:
				$login_page = get_option( 'kleingarten_login_page' );
				if ( $login_page ) {
					wp_safe_redirect( get_permalink( $login_page ) );
					// ... or redirect to home page:
				} else {
					//wp_safe_redirect( wp_login_url( get_permalink( $page_id ) ) );
					wp_safe_redirect( get_home_url() );
				}

				exit;     // Stops execution. 404 template won't be shown.

			}

		}

	}

}
