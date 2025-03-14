<?php
/**
 * Post type Admin API file.
 *
 * @package Kleingarten/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin API class.
 */
class Kleingarten_Admin_API {

	/**
	 * Constructor function
	 */
	public function __construct() {

	}

	/**
	 * Generate HTML for displaying settings fields.
	 *
	 * @param   array    $data  Data array.
	 * @param   object   $post  Post object.
	 * @param   boolean  $echo  Whether to echo the field HTML or return it.
	 *
	 * @return string
	 */
	public function display_field( $data = array(), $post = null, $echo = true
	) {

		// Get field info.
		$field = $data['field'] ?? $data;

		// Check for prefix on option name.
		$option_name = '';
		if ( isset( $data['prefix'] ) ) {
			$option_name = $data['prefix'];
		}

		// Get saved data.
		$data        = '';
		$option_name .= $field['id'];
		if ( $post ) {

			// Get saved field data.
			$option = get_post_meta( $post->ID, $field['id'], true );

			// Get data to display in field.
		} else {

			// Get saved option.
			$option = get_option( $option_name );

			// Get data to display in field.
		}
		if ( isset( $option ) ) {
			$data = $option;
		}

		// Show default data if no option saved and default is supplied.
		if ( false === $data && isset( $field['default'] ) ) {
			$data = $field['default'];
		} elseif ( false === $data ) {
			$data = '';
		}

		$html = '';

		switch ( $field['id'] ) {

			case 'available_positions':
				$html .= '<textarea id="' . esc_attr( $field['id'] )
				         . '" rows="10" cols="60" name="'
				         . esc_attr( $option_name ) . '" placeholder="'
				         . esc_attr( $field['placeholder'] ) . '">'
				         . esc_html( $data )
				         . '</textarea><br/><p class="description">'
				         . esc_html__( 'List all positins here a member can hold. One position per line.',
						'kleingarten' ) . '<br>'
				         . $this->check_unavaliable_positions() . '</p>';
				break;

			case 'login_page':
				$current_login_page = get_option( 'kleingarten_login_page' );
				$html               .= '<select name="'
				                       . esc_attr( $option_name ) . '" id="'
				                       . esc_attr( $field['id'] ) . '">';
				if ( $current_login_page == '' ) {
					$html .= '<option value="">' . esc_html( __( 'None',
							'kleingarten' ) ) . '</option>';
				} else {
					$html .= '<option value="' . $current_login_page . '">'
					         . get_the_title( $current_login_page )
					         . '</option>';
				}
				$pages = get_pages();
				foreach ( $pages as $page ) {
					if ( $page->ID != $current_login_page ) {
						$html .= '<option value="' . $page->ID . '">'
						         . $page->post_title . '</option>';
					}
				}
				if ( $current_login_page != '' ) {
					$html .= '<option value="">' . esc_html( __( 'None',
							'kleingarten' ) ) . '</option>';
				}
				$html .= '</select> ';
				break;

			case 'post_types_with_auto_likes_shortcode':

				// Build a list of available post types:
				$post_types = get_post_types( array(), 'objects' );

				// Clean up list:
				foreach ( $post_types as $i => $post_type ) {

					// We do not need:
					if ( $this->post_type_not_affected( $post_type->name ) ) {
						unset ( $post_types[ $i ] );
					}
				}

				// Build HTML for post type list:
				$html .= '<select size="' . count( $post_types ) . '" name="'
				         . esc_attr( $option_name ) . '[]" id="'
				         . esc_attr( $field['id'] ) . '" multiple="multiple">';
				foreach ( $post_types as $k => $post_type ) {
					$selected = false;
					if ( in_array( $k, (array) $data, true ) ) {
						$selected = true;
					}
					$html .= '<option ' . selected( $selected, true, false )
					         . ' value="' . $post_type->name . '">'
					         . $post_type->labels->singular_name . '</option>';
				}
				$html .= '</select><p class="description">'
				         . $field['description'] . '</p>';
				break;

			case 'auto_likes_shortcode_position':

				$checked_top = false;
				if ( $data == 'top' ) {
					$checked_top = true;
				}
				$html .= '<label for="' . esc_attr( $field['id'] . '_' . '1' )
				         . '"><input type="radio"' . checked( $checked_top,
						true, false ) . ' name="' . esc_attr( $option_name )
				         . '" value="' . 'top' . '" id="'
				         . esc_attr( $field['id'] . '_' . '1' ) . '" /> '
				         . esc_html( __( 'Top', 'kleingarten' ) ) . '</label> ';

				$checked_bottom = false;
				if ( $data == 'bottom' ) {
					$checked_bottom = true;
				}
				$html .= '<br>';
				$html .= '<label for="' . esc_attr( $field['id'] . '_' . '2' )
				         . '"><input type="radio"' . checked( $checked_bottom,
						true, false ) . ' name="' . esc_attr( $option_name )
				         . '" value="' . 'bottom' . '" id="'
				         . esc_attr( $field['id'] . '_' . '2' ) . '" /> '
				         . esc_html( __( 'Bottom', 'kleingarten' ) )
				         . '</label> ';
				break;

			case 'send_account_registration_notification':
				$checked = '';
				if ( $data && 'on' === $data ) {
					$checked = 'checked="checked"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] )
				         . '" type="checkbox" name="' . esc_attr( $option_name )
				         . '" ' . $checked . '/>';
				break;

			case 'account_registration_notification_subject':
				if ( ! get_option( 'kleingarten_send_account_registration_notification' ) ) {
					$html .= '<em>'
					         . esc_html__( 'Please activate registration notification first.',
							'kleingarten' ) . '</em>';
					$html .= '<input style="display: none;" '
					         . 'id="' . esc_attr( $field['id'] )
					         . '" type="text" name="' . esc_attr( $option_name )
					         . '" placeholder="'
					         . esc_attr( $field['placeholder'] ) . '" value="'
					         . esc_attr( $data ) . '" />';
				} else {
					$html .= '<input id="' . esc_attr( $field['id'] )
					         . '" type="text" name="' . esc_attr( $option_name )
					         . '" placeholder="'
					         . esc_attr( $field['placeholder'] ) . '" value="'
					         . esc_attr( $data ) . '" />';
					$html .= '<p class="description">'
					         . esc_html( $field['description'] )
					         . '</p>';
				}
				break;

			case 'account_registration_notification_message':
				if ( ! get_option( 'kleingarten_send_account_registration_notification' ) ) {
					$html .= '<em>'
					         . esc_html__( 'Please activate registration notification first.',
							'kleingarten' ) . '</em>';
					$html .= '<textarea style="display: none;" '
					         . 'id="' . esc_attr( $field['id'] )
					         . '" rows="10" cols="60" name="'
					         . esc_attr( $option_name ) . '" placeholder="'
					         . esc_attr( $field['placeholder'] ) . '">'
					         . wp_kses_post( $data )
					         . '</textarea>';
				} else {
					$html .= '<textarea id="' . esc_attr( $field['id'] )
					         . '" rows="10" cols="60" name="'
					         . esc_attr( $option_name ) . '" placeholder="'
					         . esc_attr( $field['placeholder'] ) . '">'
					         . wp_kses_post( $data )
					         . '</textarea><p class="description">'
					         . esc_html( $field['description'] ) . '</p>';
				}
				break;

			case 'send_account_activation_notification':
				$checked = '';
				if ( $data && 'on' === $data ) {
					$checked = 'checked="checked"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] )
				         . '" type="checkbox" name="' . esc_attr( $option_name )
				         . '" ' . $checked . '/>';
				break;

			case 'account_activation_notification_subject':
				if ( ! get_option( 'kleingarten_send_account_activation_notification' ) ) {
					$html .= '<em>'
					         . esc_html__( 'Please activate activation notification first.',
							'kleingarten' ) . '</em>';
					$html .= '<input style="display: none;" '
					         . 'id="' . esc_attr( $field['id'] )
					         . '" type="text" name="' . esc_attr( $option_name )
					         . '" placeholder="'
					         . esc_attr( $field['placeholder'] ) . '" value="'
					         . esc_attr( $data ) . '" />';
				} else {
					$html .= '<input id="' . esc_attr( $field['id'] )
					         . '" type="text" name="' . esc_attr( $option_name )
					         . '" placeholder="'
					         . esc_attr( $field['placeholder'] ) . '" value="'
					         . esc_attr( $data ) . '" /><p class="description">'
					         . esc_html( $field['description'] ) . '</p>';
				}
				break;

			case 'account_activation_notification_message':
				if ( ! get_option( 'kleingarten_send_account_activation_notification' ) ) {
					$html .= '<em>'
					         . esc_html__( 'Please activate activation notification first.',
							'kleingarten' ) . '</em>';
					$html .= '<textarea style="display: none;" '
					         . 'id="' . esc_attr( $field['id'] )
					         . '" rows="10" cols="60" name="'
					         . esc_attr( $option_name ) . '" placeholder="'
					         . esc_attr( $field['placeholder'] ) . '">'
					         . wp_kses_post( $data )
					         . '</textarea><br/>';
				} else {
					$html .= '<textarea id="' . esc_attr( $field['id'] )
					         . '" rows="10" cols="60" name="'
					         . esc_attr( $option_name ) . '" placeholder="'
					         . esc_attr( $field['placeholder'] ) . '">'
					         . wp_kses_post( $data )
					         . '</textarea><br/><p class="description">'
					         . esc_html( $field['description'] ) . '</p>';
				}
				break;

			case 'send_new_post_notification':
				$checked = '';
				if ( $data && 'on' === $data ) {
					$checked = 'checked="checked"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] )
				         . '" type="checkbox" name="' . esc_attr( $option_name )
				         . '" ' . $checked . '/>';

				if ( get_option( 'kleingarten_send_new_post_notification' ) ) {

					// Build a list of recipients
					$recipients = Kleingarten_Gardeners::get_new_post_notification_recipients();

					$html .= '<br><br><p><strong>' . esc_html__( 'Recipients',
							'kleingarten' ) . ':</strong></p>';
					$html .= '<details>';
					$html .= '<summary>' . esc_html__( 'Click to unfold.',
							'kleingarten' )
					         . '</summary>';
					$html .= '<ul>';
					foreach ( $recipients as $recipient ) {
						$html .= '<li>' . esc_html( $recipient->display_name )
						         . ' (' . $recipient->user_email . ')</li>';
					}
					$html .= '<ul>';
					$html .= '</details>';

				}

				break;

			case 'send_new_post_notification_post_type_selection':

				// Build a list of available post types:
				$post_types = get_post_types( array(), 'objects', 'and' );

				// Clean up list:
				foreach ( $post_types as $i => $post_type )
				{                // We do not need:
					if ( $this->post_type_not_affected( $post_type->name ) ) {
						unset ( $post_types[ $i ] );
					}
				}

				if ( get_option( 'kleingarten_send_new_post_notification' ) ) {

					// Build HTML for post type list:
					$html .= '<select size="' . count( $post_types )
					         . '" name="' . esc_attr( $option_name )
					         . '[]" id="' . esc_attr( $field['id'] )
					         . '" multiple="multiple">';
					foreach ( $post_types as $k => $post_type ) {
						$selected = false;
						if ( in_array( $k, (array) $data, true ) ) {
							$selected = true;
						}
						$html .= '<option ' . selected( $selected, true, false )
						         . ' value="' . esc_attr( $post_type->name )
						         . '">'
						         . $post_type->labels->singular_name
						         . '</option>';
					}
					$html .= '</select><p class="description">'
					         . esc_html( $field['description'] ) . '</p>';

				} else {
					$html .= '<em>'
					         . esc_html__( 'Please activate new post notification first.',
							'kleingarten' ) . '</em>';
				}
				break;

			case 'new_post_notification_subject':
				if ( ! get_option( 'kleingarten_send_new_post_notification' ) ) {
					$html .= '<em>'
					         . esc_html__( 'Please activate new post notification first.',
							'kleingarten' ) . '</em>';
					$html .= '<input style="display: none;" '
					         . 'id="' . esc_attr( $field['id'] )
					         . '" type="text" name="' . esc_attr( $option_name )
					         . '" placeholder="'
					         . esc_attr( $field['placeholder'] ) . '" value="'
					         . esc_attr( $data ) . '" />';
				} else {
					$html .= '<input id="' . esc_attr( $field['id'] )
					         . '" type="text" name="' . esc_attr( $option_name )
					         . '" placeholder="'
					         . esc_attr( $field['placeholder'] ) . '" value="'
					         . esc_attr( $data ) . '" /><p class="description">'
					         . esc_html( $field['description'] ) . '</p>';
				}
				break;

			case 'new_post_notification_message':
				if ( ! get_option( 'kleingarten_send_new_post_notification' ) ) {
					$html .= '<em>'
					         . esc_html__( 'Please activate new post notification first.',
							'kleingarten' ) . '</em>';
					$html .= '<textarea style="display: none;" '
					         . 'id="' . esc_attr( $field['id'] )
					         . '" rows="10" cols="60" name="'
					         . esc_attr( $option_name ) . '" placeholder="'
					         . esc_attr( $field['placeholder'] ) . '">'
					         . wp_kses_post( $data )
					         . '</textarea><br/>';
				} else {
					$html .= '<textarea id="' . esc_attr( $field['id'] )
					         . '" rows="10" cols="60" name="'
					         . esc_attr( $option_name ) . '" placeholder="'
					         . esc_attr( $field['placeholder'] ) . '">'
					         . wp_kses_post( $data )
					         . '</textarea><br/><p class="description">'
					         . esc_html( $field['description'] ) . '</p>';
				}
				break;

			case 'units_available_for_meters':
				$html .= '<textarea id="' . esc_attr( $field['id'] )
				         . '" rows="10" cols="60" name="'
				         . esc_attr( $option_name ) . '" placeholder="'
				         . esc_attr( $field['placeholder'] ) . '">'
				         . esc_html( $data )
				         . '</textarea><br/><p class="description">'
				         . esc_html( $field['description'] )
				         . '</p>';
				break;

			case 'meter_reading_submission_token_time_to_live':
				$html .= '<input type="number" name="'
				         . esc_attr( $option_name ) . '" '
				         . 'id="' . esc_attr( $field['id'] )
				         . '" placeholder="'
				         . esc_attr( $field['placeholder'] ) . '" value="'
				         . esc_attr( $data ) . '" />'
				         . '<p class="description">'
				         . esc_html( $field['description'] )
				         . '</p>';
				break;

			case 'show_footer_credits':
				$checked = '';
				if ( $data && 'on' === $data ) {
					$checked = 'checked="checked"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] )
				         . '" type="checkbox" name="' . esc_attr( $option_name )
				         . '" ' . $checked . '/>'
				         . '<p class="description">'
				         . esc_html( $field['description'] )
				         . '</p>';
				break;

		}

		if ( ! $echo ) {
			return $html;
		}

		// Nothing to escape here.
		// Everything was perfectly escaped before.
		echo $html; //phpcs:ignore

		return true;
	}

	/**
	 * Check for users holding unavailable positions.
	 *
	 * @return string
	 */
	private function check_unavaliable_positions() {

		$html                = '';
		$available_positions = explode( "\r\n",
			get_option( 'kleingarten_available_positions' ) );

		// Get all users holding at least one position
		$args  = array(
			//'orderby' => 'display_name',
			'meta_key'     => 'positions',
			'meta_value'   => 'a:0:{}',
			// This represents an empty positions array an SQL DB.
			'meta_compare' => '!=',
		);
		$users = get_users( $args );

		// Check if these users are holding unavailable positions
		foreach ( $users as $key => $user ) {
			$positions = get_user_meta( $user->ID, 'positions', true );
			foreach ( $positions as $position ) {
				if ( in_array( $position, $available_positions ) ) {
					unset ( $users[ $key ] );
				}
			}
		}

		// Return a warning and list users with unavailable positions
		if ( count( $users ) > 0 ) {
			$html
				= wp_kses_post( __( 'Warning! These users hold unavailble positions:',
					'kleingarten' ) . '<br>' );
			foreach ( $users as $user ) {
				$html .= '<a href="'
				         . esc_url( get_edit_user_link( $user->ID ) ) . '">'
				         . $user->nickname . '</a><br>';
			}

		}

		return $html;

	}

	/**
	 * Returns true if post type shall not be part of selection.
	 * To be used while building selections for auto actions (e.g. auto like box).
	 *
	 * @return bool
	 */
	private function post_type_not_affected( $post_type ) {

		if ( $post_type == 'revision'
		     ||                // Revisions
		     $post_type == 'wp_font_face'
		     ||            // Font Faces
		     $post_type == 'wp_font_family'
		     ||            // Font Families
		     $post_type == 'wp_template'
		     ||            // Templates
		     $post_type == 'wp_template_part'
		     ||        // Templates
		     $post_type == 'wp_block'
		     ||                // Blocks
		     $post_type == 'wp_block'
		     ||                // Blocks
		     $post_type == 'wp_global_styles'
		     ||        // Styles
		     $post_type == 'oembed_cache'
		     ||            // oEmbed Stuff
		     $post_type == 'user_request'
		     ||            // User requests
		     $post_type == 'customize_changeset'
		     ||    // Customozations
		     $post_type == 'custom_css'
		     ||                // Custom CSS
		     $post_type == 'custom_css'
		     ||                // Custom CSS
		     $post_type == 'wp_navigation'
		     ||            // Navigation menus
		     $post_type == 'nav_menu_item'
		     ||            // Menu Items
		     $post_type == 'wpcf7_contact_form'        // Contact Form 7 Forms
		) {
			return true;
		} else {
			return false;
		}

	}

}
