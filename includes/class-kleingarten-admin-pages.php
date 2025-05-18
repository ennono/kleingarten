<?php
/* Admin pages class file. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Pages class.
 */
class Kleingarten_Admin_Pages {

	/**
	 * The single instance of Kleingarten_Admin_Pages.
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
	 * Available admin pages for plugin.
	 *
	 * @var     array
	 * @access  public
	 * @since   1.1.2
	 */
	public $admin_pages = array();

	/**
	 * Constructor function.
	 *
	 * @param   object  $parent  Parent object.
	 */
	public function __construct( $parent ) {

		$this->parent = $parent;

		add_action( 'admin_menu', array( $this, 'add_tasks_kanban_page' ) );
		add_action( 'wp_ajax_kleingarten_set_task_status_token', array( $this, 'set_task_status_ajax_callback' ) );

	}

	/**
	 * Main Kleingarten_Admin_Pages Instance
	 *
	 * Ensures only one instance of Kleingarten_Admin_Pages is loaded or can be loaded.
	 *
	 * @param   object  $parent  Object instance.
	 *
	 * @return object Kleingarten_Admin_Pages instance
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

	public function add_tasks_kanban_page()
	{
		add_submenu_page(
            'edit.php?post_type=kleingarten_task',
			__( 'Tasks Overview', 'kleingarten' ),
			__( 'Tasks Overview', 'kleingarten' ),
			'manage_options',
			'kleingarten_tasks_overview',
			array( $this, 'render_tasks_kanban_page' ),
            -1
		);

	}

	public function render_tasks_kanban_page()
	{
		global $title;

        // Build a page header:
		echo '<div class="wrap">';

		echo '<div class="kleingarten-admin-wrapper">';
		echo '<div class="kleingarten-admin-main-section">';

		echo '<h1 class="wp-heading-inline">' . esc_html( $title ) . '</h1>';
		echo '<a href="' . esc_attr( admin_url('post-new.php?post_type=kleingarten_task') ) . '" class="page-title-action">' . esc_html( __( 'Add New Task', 'kleingarten' ) ) . '</a>';
		echo    '<hr class="wp-header-end">';

		// Build a list of all projects:
		$projects = Kleingarten_Tasks::get_all_available_projects();
		if ( $projects ) {
			//echo    '<h2>' . __( 'Projects', 'kleingarten' ) . ':</h2>';
			echo '<ul class="kleingarten-kanban-project-list">';
			echo '<span class="kleingarten-kanban-project-list-label">';
			esc_html_e( 'Projects', 'kleingarten' );
			echo ':</span>';
			foreach ( $projects as $project ) {
				$project_obj = new Kleingarten_Project( $project->term_id );
				$color = $project_obj->get_color();
				echo '<li class="kleingarten-kanban-project-list-item"><span style="margin-right: 5px; color: ' . esc_attr( $color ) . ';">&#9632;</span><a href="' . esc_url( $project_obj->get_edit_term_url() ) . '">' . esc_htmL( $project->name ) . '</a> (' . esc_html( $project_obj->count_tasks() ) . ')' . '</li>';
			}
			echo '</ul>';
		} else {
			echo '<p>' . esc_htmL( __( 'You have not created any projects yet.', 'kleingarten' ) ) . '</p>';
		}

        // Build a list for every available status:
		echo    '<div class="kleingarten-tasks-kanban-wrapper">';
		$all_available_status = Kleingarten_Tasks::get_all_available_status();
		foreach ( $all_available_status as $available_status ) {

            // Print a list header:
			echo '<div class="kleingarten-tasks-kanban-list-wrapper">';
			echo    '<h2>' . esc_html( $available_status->name ) . '</h2>';
			echo    '<p>' . esc_html( $available_status->description ) . '</p>';
			if ( $available_status->slug == 'todo' ) {
				echo '<a href="' . esc_attr( admin_url('post-new.php?post_type=kleingarten_task') ) . '">+ ' . esc_html( __( 'Add New Task', 'kleingarten' ) ) . '</a>';
			}
            // Build a list of all tasks associated with the status we are
            // currently looking at:
            $posts_with_current_status = Kleingarten_Tasks::get_tasks_with_status( $available_status->slug );
			echo '<ul class="kleingarten-tasks-kanban-list kleingarten-status-slug-' . esc_attr( $available_status->slug ) . '">';
            if ( ! empty( $posts_with_current_status ) ) {
                //echo '<ul class="kleingarten-tasks-kanban-list kleingarten-status-slug-' . esc_attr( $available_status->slug ) . '">';
	            foreach ( $posts_with_current_status as $post_with_current_status ) {

                    // Print the task:
                    $task = new Kleingarten_Task( $post_with_current_status->ID );
		            echo '<li id="" class="kleingarten-tasks-kanban-list-item kleingarten-task-id-' . esc_attr( $post_with_current_status->ID ) . '">';
                    echo '<strong><a href="' . esc_attr( get_edit_post_link( $task->get_post_ID() ) ) . '">' . esc_html( $task->get_title() ) . '</a></strong>';

		            // Build a list of all projekts associated with the task we
		            // are currently looking at:
                    $projects = $task->get_associated_projects();
                    if ( $projects && ! is_wp_error( $projects ) ) {
	                    echo '<p>' . esc_html( __( 'Belongs to projects', 'kleingarten' ) ) . ':</p>';
	                    echo '<ul>';
                        foreach ( $projects as $project ) {
	                        $project_obj = new Kleingarten_Project( $project->term_id );
	                        $color = $project_obj->get_color();
	                        echo '<li><span style="margin-right: 5px; color: ' . esc_attr( $color ) . ';">&#9632;</span>' . esc_html( $project->name ) . '</li>';
                        }
	                    echo '</ul>';
                    }

		            // Build a list of all status this task can be moved to:
		            echo '<p>' . esc_html( __( 'Move', 'kleingarten' ) ) . ':</p>';
		            echo '<ul class="kleingarten-tasks-kanban-list-item-status-list kleingarten-status-list-' . esc_attr( $post_with_current_status->ID ) . '">';
					foreach ( $all_available_status as $available_status_to_move ) {
						if ( $available_status->slug != $available_status_to_move->slug ) {
							echo '<li class="kleingarten-tasks-kanban-list-item-status-list-item kleingarten-status-list-item-' . esc_attr( $post_with_current_status->ID ) . '-'. esc_attr( $available_status_to_move->slug ) .'"><a data-task_id="' . esc_attr( $post_with_current_status->ID ) . '" data-status="' . esc_attr( $available_status_to_move->slug ) . '" id="kleingarten-set-task-status" href="#">' . esc_html( $available_status_to_move->name )  . '</a></li>';
						}
					}
					echo '</ul>';

                    echo '</li>';
	            }
            }
			echo '</ul>';

			echo '</div>';
		}

		echo '</div>';
		echo '</div>';

		echo '<div class="kleingarten-admin-sidebar">';
		echo '<a target="_blank" href="https://www.wp-kleingarten.de">';
		echo '<img src=' . esc_url( plugin_dir_url( __DIR__ ) )
		     . 'assets/Kleingarten_Logo_200px.png>';
		echo '</a>';
		echo '</div>';  // class="kleingarten-admin-main-section"

		echo '</div>';  // class="kleingarten-admin-wrapper"

        echo  '</div>';

		//echo '</div>';
	}

	/**
	 * Sets the status of a given task. To be used as an AJAX callback.
	 *
	 * @return void
	 */
	public function set_task_status_ajax_callback() {

		// Check nonce and kill script if check fails:
		if ( ! isset ( $_POST['nonce'] )
		     || ! wp_verify_nonce( sanitize_key( wp_unslash ( $_POST['nonce'] ) ), 'kleingarten-admin-ajax-nonce' ) ) {
			die ( 'Busted!');
		}

		if ( isset ( $_POST['task_ID'] ) && isset ( $_POST['new_status'] ) ) {

			// Set the task status...
			$task = new Kleingarten_Task( absint( $_POST['task_ID'] ) );
			$old_status =  $task->get_status();
			$task->set_status( sanitize_text_field( wp_unslash( $_POST['new_status'] ) ) );
			$new_status = $task->get_status();

		}

		$status_to_remove_from_list['slug'] = $new_status->slug;
		$status_to_remove_from_list['name'] = $new_status->name;
		$status_to_remove_from_list['ID'] = $new_status->term_id;

		$status_to_add_to_list['slug'] = $old_status->slug;
		$status_to_add_to_list['name'] = $old_status->name;
		$status_to_add_to_list['ID'] = $old_status->term_id;

		$json_response['task_ID_updated'] = $task->get_post_ID();
		$json_response['new_status'] = $task->get_status();
		$json_response['status_to_remove_from_list'] = $status_to_remove_from_list;
		$json_response['status_to_add_to_list'] = $status_to_add_to_list;
		wp_send_json_success( $json_response, 200 );

		wp_die(); // Ajax call must die to avoid trailing 0 in your response.

	}

}