<?php
/**
 * Post Types file.
 *
 * @package Kleingarten/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kleingarten Post Types class.
 */
class Kleingarten_Post_Types {

	private $allotment_plot_args;
	private $allotment_plot_labels;
	private $meter_labels;
	private $meter_args;
	private $task_labels;
	private $task_args;

	/**
	 * Post types constructor.
	 *
	 */
	public function __construct() {

		// Moved init of allotment_plot_labels and $allotment_plot_args
		// to register_allotment_plot_post_type() to deffer use of
		// translation functions to init hook.

		add_action( 'init',
			array( $this, 'register_allotment_plot_post_type' ) );
		add_action( 'init',
			array( $this, 'register_meter_post_type' ) );
		add_action( 'init',
			array( $this, 'register_task_post_type' ) );

		add_action( 'init',
			array( $this, 'register_project_taxonomy' ) );

		add_action( 'kleingarten_project_add_form_fields', array( $this, 'add_color_selection_to_project_taxonomy_for_new_projects' ) );
		add_action( 'kleingarten_project_edit_form_fields', array( $this, 'add_color_selection_to_project_taxonomy_for_existing_projects' ) );

		add_action( 'created_kleingarten_project', array( $this, 'save_project_color' ) );
		add_action( 'edited_kleingarten_project', array( $this, 'save_project_color' ) );

		add_action( 'init',
			array( $this, 'register_status_taxonomy' ) );
		add_action( 'init',
			array( $this, 'create_default_status' ) );

		add_action( 'wp_trash_post',
			array( 'Kleingarten_Meter', 'purge_meter' ) );
		// Alternatively if you want to clean when meter is deleted from DB instead:
		// add_action( 'delete_post', array( $this, 'purge_meter' ) );

		add_filter( 'manage_kleingarten_meter_posts_columns',
			array( $this, 'filter_meter_posts_columns' ) );
		add_action( 'manage_kleingarten_meter_posts_custom_column',
			array( $this, 'print_meter_posts_columns' ), 10, 2 );
		add_filter( 'manage_edit-kleingarten_meter_sortable_columns',
			array( $this, 'set_meter_posts_sortable_columns' ) );

		add_action( 'pre_get_posts', array( $this, 'handle_meter_orderby' ) );

		//add_action( 'post_updated_messages', array( $this, 'customize_meter_admin_notices' ) );
		add_action( 'post_updated_messages',
			array( $this, 'customize_admin_notices' ) );
		add_action( 'bulk_post_updated_messages',
			array( $this, 'customize_plot_bulk_admin_notices' ), 10, 2 );
		add_action( 'bulk_post_updated_messages',
			array( $this, 'customize_meter_bulk_admin_notices' ), 10, 2 );

	}

	/**
	 * Register allotment plot post type
	 *
	 * return void
	 * since 1.1.0
	 */
	public function register_allotment_plot_post_type() {

		$this->allotment_plot_labels = array(
			'name'                  => _x( 'Allotment Plots',
				'Post type general name', 'kleingarten' ),
			'singular_name'         => _x( 'Allotment Plot',
				'Post type singular name', 'kleingarten' ),
			'menu_name'             => _x( 'Allotment Plots', 'Admin Menu text',
				'kleingarten' ),
			'name_admin_bar'        => _x( 'Allotment Plot',
				'Add New on Toolbar', 'kleingarten' ),
			'add_new'               => __( 'Add New', 'kleingarten' ),
			'add_new_item'          => __( 'Add New Plot', 'kleingarten' ),
			'new_item'              => __( 'New Plot', 'kleingarten' ),
			'edit_item'             => __( 'Edit Plot', 'kleingarten' ),
			'view_item'             => __( 'View Plot', 'kleingarten' ),
			'all_items'             => __( 'All Plots', 'kleingarten' ),
			'search_items'          => __( 'Search Plots', 'kleingarten' ),
			'parent_item_colon'     => __( 'Parent Plots:', 'kleingarten' ),
			'not_found'             => __( 'No plots found.', 'kleingarten' ),
			'not_found_in_trash'    => __( 'No plots found in Trash.',
				'kleingarten' ),
			'featured_image'        => _x( 'Plot Cover Image',
				'Overrides the “Featured Image” phrase for this post type. Added in 4.3',
				'kleingarten' ),
			'set_featured_image'    => _x( 'Set plot image',
				'Overrides the “Set featured image” phrase for this post type. Added in 4.3',
				'kleingarten' ),
			'remove_featured_image' => _x( 'Remove plot image',
				'Overrides the “Remove featured image” phrase for this post type. Added in 4.3',
				'kleingarten' ),
			'use_featured_image'    => _x( 'Use as plot image',
				'Overrides the “Use as featured image” phrase for this post type. Added in 4.3',
				'kleingarten' ),
			'archives'              => _x( 'Plot archives',
				'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4',
				'kleingarten' ),
			'insert_into_item'      => _x( 'Insert into plot',
				'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4',
				'kleingarten' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this plot',
				'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4',
				'kleingarten' ),
			'filter_items_list'     => _x( 'Filter plots list',
				'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4',
				'kleingarten' ),
			'items_list_navigation' => _x( 'Plots list navigation',
				'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4',
				'kleingarten' ),
			'items_list'            => _x( 'Plots list',
				'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4',
				'kleingarten' ),
		);

		$this->allotment_plot_args = array(
			'labels'              => $this->allotment_plot_labels,
			'description'         => __( 'Allotment Plot Description',
				'kleingarten' ),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'query_var'           => true,
			'can_export'          => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'show_in_rest'        => false,
			'supports'            => array( 'title' ),
			'menu_position'       => 30,
			'menu_icon'           => 'dashicons-tagcloud',
		);

		register_post_type( 'kleingarten_plot', $this->allotment_plot_args );
	}

	/**
	 * Register meter post type
	 *
	 * @return void
	 * @since 1.1.0
	 */
	public function register_meter_post_type() {

		$this->meter_labels = array(
			'name'                  => _x( 'Meters',
				'Post type general name', 'kleingarten' ),
			'singular_name'         => _x( 'Meter',
				'Post type singular name', 'kleingarten' ),
			'menu_name'             => _x( 'Meters', 'Admin Menu text',
				'kleingarten' ),
			'name_admin_bar'        => _x( 'Meter',
				'Add New on Toolbar', 'kleingarten' ),
			'add_new'               => __( 'Add New', 'kleingarten' ),
			'add_new_item'          => __( 'Add New Meter', 'kleingarten' ),
			'new_item'              => __( 'New Meter', 'kleingarten' ),
			'edit_item'             => __( 'Edit Meter', 'kleingarten' ),
			'view_item'             => __( 'View Meter', 'kleingarten' ),
			'all_items'             => __( 'All Meters', 'kleingarten' ),
			'search_items'          => __( 'Search Meters', 'kleingarten' ),
			//'parent_item_colon'     => __( 'Parent Meters:', 'kleingarten' ),
			'not_found'             => __( 'No meters found.', 'kleingarten' ),
			'not_found_in_trash'    => __( 'No meters found in Trash.',
				'kleingarten' ),
			//'featured_image'        => _x( 'Meter Cover Image',
			//	'Overrides the “Featured Image” phrase for this post type. Added in 4.3',
			//	'kleingarten' ),
			//'set_featured_image'    => _x( 'Set meter image',
			//	'Overrides the “Set featured image” phrase for this post type. Added in 4.3',
			//	'kleingarten' ),
			//'remove_featured_image' => _x( 'Remove meter image',
			//	'Overrides the “Remove featured image” phrase for this post type. Added in 4.3',
			//	'kleingarten' ),
			//'use_featured_image'    => _x( 'Use as meter image',
			//	'Overrides the “Use as featured image” phrase for this post type. Added in 4.3',
			//	'kleingarten' ),
			'archives'              => _x( 'Meter archives',
				'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4',
				'kleingarten' ),
			//'insert_into_item'      => _x( 'Insert into meter',
			//	'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4',
			//	'kleingarten' ),
			//'uploaded_to_this_item' => _x( 'Uploaded to this meter',
			//	'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4',
			//	'kleingarten' ),
			'filter_items_list'     => _x( 'Filter meters list',
				'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4',
				'kleingarten' ),
			'items_list_navigation' => _x( 'Meters list navigation',
				'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4',
				'kleingarten' ),
			'items_list'            => _x( 'Meters list',
				'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4',
				'kleingarten' ),
		);

		$this->meter_args = array(
			'labels'              => $this->meter_labels,
			'description'         => __( 'Meter Description',
				'kleingarten' ),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'query_var'           => true,
			'can_export'          => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'show_in_rest'        => false,
			'supports'            => array( 'title' ),
			'menu_position'       => 30,
			'menu_icon'           => 'dashicons-performance',
		);

		register_post_type( 'kleingarten_meter', $this->meter_args );
	}

	/**
	 * Register task post type
	 *
	 * @return void
	 * @since 1.1.0
	 */
	public function register_task_post_type() {

		$this->task_labels = array(
			'name'                  => _x( 'Tasks',
				'Post type general name', 'kleingarten' ),
			'singular_name'         => _x( 'Task',
				'Post type singular name', 'kleingarten' ),
			'menu_name'             => _x( 'Tasks', 'Admin Menu text',
				'kleingarten' ),
			'name_admin_bar'        => _x( 'Task',
				'Add New on Toolbar', 'kleingarten' ),
			'add_new'               => __( 'Add New', 'kleingarten' ),
			'add_new_item'          => __( 'Add New Task', 'kleingarten' ),
			'new_item'              => __( 'New Task', 'kleingarten' ),
			'edit_item'             => __( 'Edit Task', 'kleingarten' ),
			'view_item'             => __( 'View Task', 'kleingarten' ),
			'all_items'             => __( 'All Tasks', 'kleingarten' ),
			'search_items'          => __( 'Search Tasks', 'kleingarten' ),
			//'parent_item_colon'     => __( 'Parent Meters:', 'kleingarten' ),
			'not_found'             => __( 'No tasks found.', 'kleingarten' ),
			'not_found_in_trash'    => __( 'No tasks found in Trash.',
				'kleingarten' ),
			'archives'              => _x( 'Task archives',
				'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4',
				'kleingarten' ),
			'filter_items_list'     => _x( 'Filter tasks list',
				'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4',
				'kleingarten' ),
			'items_list_navigation' => _x( 'Tasks list navigation',
				'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4',
				'kleingarten' ),
			'items_list'            => _x( 'Tasks list',
				'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4',
				'kleingarten' ),
		);

		$this->task_args = array(
			'labels'              => $this->task_labels,
			'description'         => __( 'Task Description',
				'kleingarten' ),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'query_var'           => true,
			'can_export'          => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'show_in_rest'        => false,
			'supports'            => array( 'title' ),
			'menu_position'       => 30,
			'menu_icon'           => 'dashicons-hammer',
		);

		register_post_type( 'kleingarten_task', $this->task_args );
	}

	/**
	 * Registers project taxonomy for tasks.
	 *
	 * @return void
	 */
	public function register_project_taxonomy() {

		$labels = array(
			'name'              => _x( 'Projects', 'taxonomy general name', 'kleingarten' ),
			'singular_name'     => _x( 'Project', 'taxonomy singular name', 'kleingarten' ),
			'search_items'      => __( 'Search Projects', 'kleingarten' ),
			'all_items'         => __( 'All Projects', 'kleingarten' ),
			//'parent_item'       => __( 'Parent Projects', 'kleingarten' ),
			//'parent_item_colon' => __( 'Parent Project:', 'kleingarten' ),
			'edit_item'         => __( 'Edit Project', 'kleingarten' ),
			'update_item'       => __( 'Update Project', 'kleingarten' ),
			'add_new_item'      => __( 'Add New Project', 'kleingarten' ),
			'new_item_name'     => __( 'New Project Name', 'kleingarten' ),
			'menu_name'         => __( 'Projects', 'kleingarten' ),
		);

		$args   = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => [ 'slug' => __( 'kleingarten-project', 'kleingarten' ) ],
		);

		register_taxonomy( 'kleingarten_project', [ 'kleingarten_task' ], $args );
	}

	/**
	 * Registers status taxonomy for tasks.
	 *
	 * @return void
	 */
	public function register_status_taxonomy() {

		$labels = array(
			'name'              => _x( 'Statuses', 'taxonomy general name', 'kleingarten' ),
			'singular_name'     => _x( 'Status', 'taxonomy singular name', 'kleingarten' ),
			'search_items'      => __( 'Search Statuses', 'kleingarten' ),
			'all_items'         => __( 'All Statuses', 'kleingarten' ),
			//'parent_item'       => __( 'Parent Projects', 'kleingarten' ),
			//'parent_item_colon' => __( 'Parent Project:', 'kleingarten' ),
			'edit_item'         => __( 'Edit Status', 'kleingarten' ),
			'update_item'       => __( 'Update Status', 'kleingarten' ),
			'add_new_item'      => __( 'Add New Status', 'kleingarten' ),
			'new_item_name'     => __( 'New Status Name', 'kleingarten' ),
			'menu_name'         => __( 'Statuses', 'kleingarten' ),
		);

		$args   = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => false,
			//'show_in_menu'      => false,
			//'show_admin_column' => false,
			'query_var'         => true,
			'rewrite'           => [ 'slug' => __( 'kleingarten-status', 'kleingarten' ) ],
		);

		register_taxonomy( 'kleingarten_status', [ 'kleingarten_task' ], $args );
	}

	/**
     * Re-creates project terms if the do not exist to provide something like
     * undeletable default projects. To be used with a hook.
     *
	 * @return void
	 */
	public function create_default_status() {

        // If status has been deleted...
		if ( ! term_exists( 'todo', 'kleingarten_status' ) ) {
            // ... re-insert it:
			$term_data = wp_insert_term( __( 'To Do', 'kleingarten' ), 'kleingarten_status', array( 'slug' => 'todo' ) );
            // ... an set its order:
			if ( ! is_wp_error( $term_data ) ) {
				update_term_meta( $term_data['term_id'],
					'kleingarten_project_order', 1 );
			}
		}

		if ( ! term_exists( 'next', 'kleingarten_status' ) ) {
			$term_data = wp_insert_term( __( 'Next', 'kleingarten' ), 'kleingarten_status', array( 'slug' => 'next' ) );
			if ( ! is_wp_error( $term_data ) ) {
				update_term_meta( $term_data['term_id'],
					'kleingarten_project_order', 2 );
			}
		}

		if ( ! term_exists( 'waiting', 'kleingarten_status' ) ) {
			$term_data = wp_insert_term( __( 'Waiting', 'kleingarten' ), 'kleingarten_status', array( 'slug' => 'waiting' ) );
			if ( ! is_wp_error( $term_data ) ) {
				update_term_meta( $term_data['term_id'],
					'kleingarten_project_order', 3 );
			}
		}

		if ( ! term_exists( 'done', 'kleingarten_status' ) ) {
			$term_data = wp_insert_term( __( 'Done', 'kleingarten' ), 'kleingarten_status', array( 'slug' => 'done' ) );
			if ( ! is_wp_error( $term_data ) ) {
				update_term_meta( $term_data['term_id'],
					'kleingarten_project_order', 4 );
			}
		}

	}

	/**
	 * Builds HTML of a color selection. To be used as a callback for project
	 * terms being created.
	 *
	 * @param $term
	 *
	 * @return void
	 */
	public function add_color_selection_to_project_taxonomy_for_new_projects( $term ) {

		wp_nonce_field( 'save_kleingarten_project', 'save_kleingarten_project_nonce' );

		?>
        <div class="form-field kleingarten-project-color-wrap">
            <label for="kleingarten-project-color"><?php esc_html_e( 'Color', 'kleingarten' ); ?></label>
            <input type="text" name="kleingarten-project-color" value="#00FF00" class="kleingarten-color-field kleingarten-project-color" />
            <p id="description-description"><?php esc_html_e( 'The colour selection helps you to distinguish projects in the overview.', 'kleingarten' ); ?></p>
        </div>

		<?php
	}

	/**
	 * Builds HTML of a color selection. To be used as a callback for project
     * terms being edited.
	 *
	 * @param $term
	 *
	 * @return void
	 */
	public function add_color_selection_to_project_taxonomy_for_existing_projects( $term ) {

        wp_nonce_field( 'save_kleingarten_project', 'save_kleingarten_project_nonce' );

		$current_color = get_term_meta( $term->term_id, 'kleingarten_project_color', true );

		?>
        <tr class="form-field form-required term-name-wrap">
            <th scope="row"><label for="kleingarten-project-color"><?php esc_html_e( 'Color', 'kleingarten' ); ?></label></th>
            <td><input type="text" name="kleingarten-project-color" value="<?php echo esc_attr( $current_color ); ?>" class="kleingarten-color-field kleingarten-project-color" />
                <p id="description-description"><?php esc_html_e( 'The colour selection helps you to distinguish projects in the overview.', 'kleingarten' ); ?></p>
            </td>
        </tr>

		<?php
	}

	/**
     * Saves a given project color. To be used as callback for project colors
     * being saved.
     *
	 * @param $term_id
	 *
	 * @return void
	 */
	public function save_project_color( $term_id ) {

	    if ( ! isset ( $_POST['save_kleingarten_project_nonce'] )
	         || ! wp_verify_nonce( sanitize_key( wp_unslash ( $_POST['save_kleingarten_project_nonce'] ) ),
			    'save_kleingarten_project' )
	    ) {
		    return;
	    } elseif ( isset( $_POST['kleingarten-project-color'] )) {

            $project = new Kleingarten_Project( $term_id );
            $project->set_color( sanitize_text_field( wp_unslash( $_POST['kleingarten-project-color'] ) ) );

	    }

    }

	/**
	 * Callback to customize columns for meter post type in admin area.
	 *
	 * @return array
	 * @since 1.1.0
	 */
	public function filter_meter_posts_columns( $columns ) {

		$columns = array(
			'cb'                 => $columns['cb'],
			'title'              => $columns['title'],
			'assignments'        => __( 'Associated parcels', 'kleingarten' ),
			'last_reading_value' => __( 'Last Reading', 'kleingarten' ),
			'meter_unit'         => __( 'Unit/Type', 'kleingarten' ),
			'last_reading_date'  => __( 'Last Reading Date', 'kleingarten' ),
		);

		return $columns;

	}

	function print_meter_posts_columns( $column, $meter_id ) {

		$wp_date_format
			= get_option( 'date_format' );    // Get WordPress date format from settings.

		$meter = new Kleingarten_Meter( $meter_id );

		// Get the recent reading value and date:
		/*
		$readings = has_meta( $meter_id,
			'kleingarten_meter_reading' );
		$most_recent = 0;                       // Helper for comparing
		$most_recent_reading_value = null;         // Latest value
		$most_recent_reading_date  = '';         // Latest date
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
		*/
		$most_recent_reading       = $meter->get_most_recent_reading();
		$most_recent_reading_value = $most_recent_reading['reading'];
		$most_recent_reading_date  = $most_recent_reading['date'];

		if ( 'meter_unit' === $column ) {
			//$unit = get_post_meta( $meter_id, 'kleingarten_meter_unit', true );
			$unit = $meter->get_unit();
			if ( $unit != '' ) {
				echo esc_html( $unit );
			} else {
				esc_html_e( 'No unit defined.', 'kleingarten' );
			}
		}

		if ( 'last_reading_value' === $column ) {

			if ( $most_recent_reading_value !== null ) {
				echo esc_html( $most_recent_reading_value );
			} else {
				esc_html_e( '-', 'kleingarten' );
			}

		}

		if ( 'last_reading_date' === $column ) {

			if ( $most_recent_reading_date !== '' ) {
				echo esc_html( wp_date( $wp_date_format,
					$most_recent_reading_date ) );
				//echo esc_html( date_format( date_create( $most_recent_reading_date ), $wp_date_format ) );
			} else {
				esc_html_e( '-', 'kleingarten' );
			}

		}

		if ( 'assignments' === $column ) {

			//$assignments = $this->get_meter_assignments( $meter_id );
			$assignments = $meter->get_meter_assignments();

			if ( $assignments ) {

				// If we need to print multiple associated plots...
				$assignments_number = count( $assignments );
				if ( $assignments_number > 1 ) {

					foreach ( $assignments as $j => $assignment ) {

						$plot = new Kleingarten_Plot( $assignment );

						echo '<a href="'
						     . esc_url( get_edit_post_link( $assignment ) )
						     . '">';
						echo esc_html( get_the_title( $assignment ) );
						echo esc_html( $plot->get_title() );
						echo '</a>';

						if ( $j < $assignments_number - 1 ) {
							echo ', ';
						}

					}

					// ... or if there is only one plot linked
				} else {
					$plot = new Kleingarten_Plot( $assignments[0] );
					echo '<a href="'
					     . esc_url( get_edit_post_link( $assignments[0] ) )
					     . '">';
					echo esc_html( $plot->get_title() );
					echo '</a>';
				}


			} else {
				echo '-';
			}

		}


	}

	/**
	 * Callback to set sortable columns for meter post type in admin area.
	 *
	 * @param $columns
	 *
	 * @return array
	 * @sine 1.1.0
	 */
	public function set_meter_posts_sortable_columns( $columns ) {

		//$columns['last_reading_date'] = 'last_reading_date';        // Sort by reading date
		//$columns['last_reading_value'] = 'last_reading_value';      // Sort by reading value
		$columns['meter_unit'] = 'meter_unit';      // Sort by unit

		//unset( $columns['title'] );     // Do not sort by title

		return $columns;
	}

	/**
	 * Callback to equip query with the right parameters to sort post tables in admin area in the desired order.
	 *
	 * @param $query
	 *
	 * @since 1.1.0
	 */
	public function handle_meter_orderby( $query ) {

		if ( ! is_admin() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'meter_unit' == $orderby ) {

			$query->set( 'meta_key', 'kleingarten_meter_unit' );
			$query->set( 'orderby', 'meta_value' );
		}

	}

	/**
	 * Callback to print custom messages on saving the custom post types when editing single post.
	 *
	 * @return array
	 * @since 1.1.0
	 */
	public function customize_admin_notices() {

		global $post, $post_ID;

		$messages['kleingarten_plot'] = array(
			0  => '',
			// Unused. Messages start at index 1.
			//1 => sprintf( __('Plot updated. <a href="%s">View Plot</a>'), esc_url( get_permalink($post_ID) ) ),
			1  => __( 'Plot updated.', 'kleingarten' ),
			2  => __( 'Custom field updated.', 'kleingarten' ),
			3  => __( 'Custom field deleted.', 'kleingarten' ),
			4  => __( 'Plot updated.', 'kleingarten' ),
			/* translators: %s: Date and time of the revision */
			5  => isset( $_GET['revision'] )
				/* translators: Revision the posed is being restored from */
				? sprintf( __( 'Plot restored to revision from %s',
					'kleingarten' ),
					wp_post_revision_title( (int) $_GET['revision'], false ) )
				: false,
			//6 => sprintf( __('Plot published. <a href="%s">View plot</a>', 'kleingarten'  ), esc_url( get_permalink($post_ID) ) ),
			6  => __( 'Plot published.', 'kleingarten' ),
			7  => __( 'Plot saved.', 'kleingarten' ),
			//8 => sprintf( __('Plot submitted. <a target="_blank" href="%s">Preview plot</a>', 'kleingarten' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			8  => __( 'Plot submitted.', 'kleingarten' ),
			/* translators: %1$s: date and time the plot is scheduled for */
			9  => sprintf( __( 'Plot scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview plot</a>',
				'kleingarten' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'kleingarten' ),
					strtotime( $post->post_date ) ),
				esc_url( get_permalink( $post_ID ) ) ),
			//10 => sprintf( __('Plot draft updated. <a target="_blank" href="%s">Preview plot</a>', 'kleingarten' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			10 => __( 'Plot draft updated.', 'kleingarten' ),
		);

		$messages['kleingarten_meter'] = array(
			0  => '',
			// Unused. Messages start at index 1.
			//1 => sprintf( __('Meter updated. <a href="%s">View Plot</a>'), esc_url( get_permalink($post_ID) ) ),
			1  => __( 'Meter updated.', 'kleingarten' ),
			2  => __( 'Custom field updated.', 'kleingarten' ),
			3  => __( 'Custom field deleted.', 'kleingarten' ),
			4  => __( 'Meter updated.', 'kleingarten' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] )
				/* translators: Revision the post is being restored from */
				? sprintf( __( 'Meter restored to revision from %s',
					'kleingarten' ),
					wp_post_revision_title( (int) $_GET['revision'], false ) )
				: false,
			//6 => sprintf( __('Meter published. <a href="%s">View plot</a>', 'kleingarten'  ), esc_url( get_permalink($post_ID) ) ),
			6  => __( 'Meter published.', 'kleingarten' ),
			7  => __( 'Meter saved.', 'kleingarten' ),
			//8 => sprintf( __('Plot submitted. <a target="_blank" href="%s">Preview plot</a>', 'kleingarten' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			8  => __( 'Meter submitted.', 'kleingarten' ),
			/* translators: %1$s: date and time the meter is scheduled for */
			9  => sprintf( __( 'Meter scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview meter</a>',
				'kleingarten' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'kleingarten' ),
					strtotime( $post->post_date ) ),
				esc_url( get_permalink( $post_ID ) ) ),
			//10 => sprintf( __('Meter draft updated. <a target="_blank" href="%s">Preview plot</a>', 'kleingarten' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			10 => __( 'Meter draft updated.', 'kleingarten' ),
		);

		return $messages;

	}

	/**
	 * Callback to print custom messages on saving plots when bulk editing.
	 *
	 * @return array
	 * @since 1.1.0
	 */
	public function customize_plot_bulk_admin_notices(
		$bulk_messages, $bulk_counts
	) {

		$bulk_messages['kleingarten_plot'] = array(
			/* translators: %s: Number of plots updated */
			'updated'   => _n( '%s plot updated.', '%s plots updated.',
				$bulk_counts['updated'], 'kleingarten' ),
			/* translators: %s: Number of plots not updated */
			'locked'    => _n( '%s plot not updated, somebody is editing it.',
				'%s plots not updated, somebody is editing them.',
				$bulk_counts['locked'], 'kleingarten' ),
			/* translators: %s: Number of plots deleted */
			'deleted'   => _n( '%s plot permanently deleted.',
				'%s plots permanently deleted.', $bulk_counts['deleted'],
				'kleingarten' ),
			/* translators: %s: Number of plots moved to trash */
			'trashed'   => _n( '%s plot moved to the Trash.',
				'%s plots moved to the Trash.', $bulk_counts['trashed'],
				'kleingarten' ),
			/* translators: %s: Number of plots restored from trash */
			'untrashed' => _n( '%s plot restored from the Trash.',
				'%s plots restored from the Trash.',
				$bulk_counts['untrashed'], 'kleingarten' ),
		);

		return $bulk_messages;

	}

	/**
	 * Callback to print custom messages on saving meters when bulk editing.
	 *
	 * @return array
	 * @since 1.1.0
	 */
	public function customize_meter_bulk_admin_notices(
		$bulk_messages, $bulk_counts
	) {

		$bulk_messages['kleingarten_meter'] = array(
			/* translators: %s: Number of meters updated */
			'updated'   => _n( '%s meter updated.', '%s meters updated.',
				$bulk_counts['updated'], 'kleingarten' ),
			/* translators: %s: Number of meters updated */
			'locked'    => _n( '%s meter not updated, somebody is editing it.',
				'%s meters not updated, somebody is editing them.',
				$bulk_counts['locked'], 'kleingarten' ),
			/* translators: %s: Number of meters updated */
			'deleted'   => _n( '%s meter permanently deleted.',
				'%s meters permanently deleted.', $bulk_counts['deleted'],
				'kleingarten' ),
			/* translators: %s: Number of meters updated */
			'trashed'   => _n( '%s meter moved to the Trash.',
				'%s meters moved to the Trash.', $bulk_counts['trashed'],
				'kleingarten' ),
			/* translators: %s: Number of meters updated */
			'untrashed' => _n( '%s meter restored from the Trash.',
				'%s meters restored from the Trash.',
				$bulk_counts['untrashed'], 'kleingarten' ),
		);

		return $bulk_messages;

	}

}
