<?php /** @noinspection PhpUndefinedConstantInspection */
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
		echo    '<h1 class="wp-heading-inline">' . esc_html( $title ) . '</h1>';
		echo '<a href="' . esc_attr( admin_url('post-new.php?post_type=kleingarten_task') ) . '" class="page-title-action">' . __( 'Add New Task', 'kleingarten' ) . '</a>';
		echo    '<hr class="wp-header-end">';

        // Build a list for every available status:
		echo    '<div class="kleingarten-tasks-kanban-wrapper">';
		$all_available_status = Kleingarten_Tasks::get_all_available_status();
		foreach ( $all_available_status as $available_status ) {

            // Print a list header:
			echo '<div class="kleingarten-tasks-kanban-list-wrapper">';
			echo    '<h2>' . $available_status->name . '</h2>';

            // Build a list of all tasks associated with the status we are
            // currently looking at:
            $posts_with_current_status = Kleingarten_Tasks::get_tasks_with_status( $available_status->slug );
            if ( ! empty( $posts_with_current_status ) ) {
                echo '<ul class="kleingarten-tasks-kanban-list">';
	            foreach ( $posts_with_current_status as $post_with_current_status ) {

                    // Print the task:
                    $task = new Kleingarten_Task( $post_with_current_status->ID );
		            echo '<li class="kleingarten-tasks-kanban-list-item">';
                    echo '<a href="' . esc_attr( get_edit_post_link( $task->get_post_ID() ) ) . '">' . esc_html( $task->get_title() ) . '</a>';

		            // Build a list of all projekts associated with the task we
		            // are currently looking at:
                    $projects = $task->get_associated_projects();
                    if ( $projects && ! is_wp_error( $projects ) ) {
	                    echo '<ul>';
                        foreach ( $projects as $project ) {
                            echo '<li>';
                            echo $project->name;
                            echo '</li>';
                        }
	                    echo '</ul>';
                    }

                    echo '</li>';
	            }
                echo '</ul>';
            }

			echo '</div>';
		}
        echo    '</div>';

        // Build a list of all projects:
        $projects = Kleingarten_Tasks::get_all_available_projects();
        if ( $projects ) {
            echo '<ul>';
            foreach ( $projects as $project ) {
	            $color = get_term_meta( $project->term_id, 'kleingarten_project_color', true );
	            echo '<li><span style="margin-right: 5px; color: ' . $color . ';">&#9632;</span>' . $project->name . '</li>';
            }
            echo '</ul>';
        }

		echo '</div>';
	}

}