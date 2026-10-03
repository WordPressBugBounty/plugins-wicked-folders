<?php

namespace Wicked_Folders;

use Wicked_Folders;

// Disable direct load
defined( 'ABSPATH' ) || exit;

final class Admin {

    private static $instance;

    private $admin_notices = array();

    private function __construct() {
        add_action( 'admin_enqueue_scripts',				array( $this, 'admin_enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts',				array( $this, 'maybe_enqueue_scripts_for_settings_pages' ) );
        add_action( 'pre_get_posts',						array( $this, 'pre_get_posts' ) );
        add_action( 'admin_notices', 						array( $this, 'admin_notices' ) );
        add_action( 'network_admin_notices', 				array( $this, 'admin_notices' ) );
        add_action( 'manage_pages_custom_column',           array( $this, 'page_custom_column_content' ), 10, 2 );
        add_action( 'manage_posts_custom_column', 			array( $this, 'post_custom_column_content' ), 10, 2 );
        add_action( 'wp_enqueue_media', 					array( $this, 'wp_enqueue_media' ) );
        add_action( 'admin_footer', 						array( $this, 'admin_footer' ) );

        add_filter( 'wpseo_primary_term_taxonomies', 		array( $this, 'wpseo_primary_term_taxonomies' ), 3, 10 );
        add_filter( 'wp_terms_checklist_args', 				array( $this, 'wp_terms_checklist_args' ), 10, 2 );
        add_filter( 'admin_body_class', 					array( $this, 'admin_body_class' ) );
        add_filter( 'manage_posts_columns', 				array( $this, 'manage_posts_columns' ) );
        add_filter( 'manage_pages_columns', 				array( $this, 'manage_posts_columns' ) );
        add_filter( 'post_column_taxonomy_links', 			array( $this, 'post_column_taxonomy_links' ), 10, 3 );

        // Add move-to-folder column to LifterLMS Lessons
        add_filter( 'manage_edit-lesson_columns', 			array( $this, 'manage_posts_columns' ) );
        add_filter( 'manage_edit-pretty-link_columns', 		array( $this, 'manage_posts_columns' ) );

        // Add move-to-folder to Ed School theme types
        add_filter( 'manage_edit-agc_course_columns', 			array( $this, 'manage_posts_columns' ), 100 );
        add_filter( 'manage_edit-layout_block_columns', 		array( $this, 'manage_posts_columns' ), 100 );

        // Add move-to-folder to Testimonial Rotator plugin types
        add_filter( 'manage_edit-testimonial_columns', 			array( $this, 'manage_posts_columns' ), 100 );
        add_filter( 'manage_edit-testimonial_rotator_columns', 	array( $this, 'manage_posts_columns' ), 100 );

        // Add move-to-folder to Woody Code Snippets types
        add_filter( 'manage_edit-wbcr-snippets_columns', 	array( $this, 'manage_posts_columns' ), 20 );

        add_filter( 'plugin_action_links_wicked-folders/wicked-folders.php', array( $this, 'add_settings_to_plugin_action_links' ) );
    }

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new Admin();
        }

        return self::$instance;
    }

    public function add_admin_notice( $message, $class = 'notice notice-success' ) {
        // notice-success
        // notice-warning
        // notice-error
        $this->admin_notices[] = array(
            'message' 	=> $message,
            'class' 	=> $class,
        );

    }

    public function admin_notices() {
        foreach ( $this->admin_notices as $notice ) {
            printf( '<div class="%1$s"><p>%2$s</p></div>', $notice['class'], $notice['message'] );
        }
    }

    public function admin_body_class( $classes ) {
        $state = $this->get_screen_state();

        if ( $this->is_folder_pane_enabled_page() ) {
            $classes .= ' wicked-folders-enabled ';
        }

        return $classes;
    }

    public function admin_enqueue_scripts() {
        global $typenow;
        static $done = false;

        // Only run this once per request
        if ( $done ) return;

        $version 					= Wicked_Folders::plugin_version();
        $is_wider_admin_menu_active = false;
        $in_footer 					= apply_filters( 'wicked_folders_enqueue_scripts_in_footer', true );
        $dist_url 					= untrailingslashit( apply_filters( 'wicked_folders_build_directory_url', plugin_dir_url( dirname( __FILE__ ) ) . 'dist' ) );
        $deps 						= array(
            'jquery',
            'jquery-ui-resizable',
            'jquery-ui-draggable',
            'jquery-ui-droppable',
            'jquery-ui-sortable',
            'lodash',
            'wp-element',
            'wp-data',
            'wp-data-controls',
            'wp-polyfill',
            'wp-i18n',
            'wp-api-fetch',
            'wp-hooks',
            'wp-components'
        );

        if ( function_exists( 'is_plugin_active' ) ) {
            $is_wider_admin_menu_active = is_plugin_active( 'wider-admin-menu/wider-admin-menu.php' );
        }

        wp_register_script( 'wicked-folders-select2', plugin_dir_url( dirname( __FILE__ ) ) . 'vendor/select2/js/select2.full.min.js', array(), $version, $in_footer );
        wp_register_script( 'wicked-folders-admin', plugin_dir_url( dirname( __FILE__ ) ) . 'js/admin.js', array(), $version, $in_footer );

        wp_register_style( 'wicked-folders-select2', plugin_dir_url( dirname( __FILE__ ) ) . 'vendor/select2/css/select2.min.css', array(), $version );
        wp_register_style( 'wicked-folders-admin', plugin_dir_url( dirname( __FILE__ ) ) . 'css/admin.css', array( 'wp-components' ), $version );

        if ( defined( 'WICKED_FOLDERS_APP' ) ) {
            wp_register_script( 'wicked-folders-app', WICKED_FOLDERS_APP, $deps, $version, $in_footer );
        } else {
            wp_register_script( 'wicked-folders-app', "{$dist_url}/folders.js", $deps, $version, $in_footer );
            wp_register_style( 'wicked-folders-app', "{$dist_url}/folders.css", array(), $version );
        }

        if ( $this->is_folder_pane_enabled_page() ) {
            // Note: get_screen_state uses get_current_screen which will lead to
            // errors when calling before get_current_screen is defined; therefore
            // this call has been moved inside of the current if condition (which
            // returns false if get_current_screen is undefined). This can happen
            // from calling wp_enqueue_media too early which some themes might do.
            // See https://wordpress.org/support/topic/fatal-error-after-upgrading-to-2-7-1/
            $folders    = new All_Folders_Collection();
            $state      = $this->get_screen_state();
            $post_type  = $this->get_current_screen_post_type();
            $folder     = Folder_Factory::get_folder( $state->folder, $post_type );
            $taxonomy   = Wicked_Folders::get_tax_name( $post_type );

            // Attachment folders are fetched in wp_enqueue_media when media library is in grid view.
            // Don't fetch attachment folders here to avoid duplicate queries.
            // TODO: consider refactoring this function so this check isn't needed
            if ( ! ( 'attachment' == $post_type &&  'grid' == $this->get_media_library_mode() ) ) {
                $folders->post_type = $post_type;

                $folders->fetch();
                $folders->sort( $state->sort_mode );

                if ( $state->show_item_counts ) {
                    $folders->fetch_item_counts();
                }                
            }

            $state->include_children = Wicked_Folders::include_children( $post_type );

            $state->maybe_change_selected_folder( $folders );
            $state->can_add_folders = apply_filters( 'wicked_folders_can_create_folders', true, get_current_user_id(), $taxonomy );

            wp_localize_script( 'wicked-folders-app', 'wickedFoldersSettings', array(
                'ajaxURL' 			=> admin_url( 'admin-ajax.php' ),
                'restURL' 			=> rest_url(),
                'afterAjaxScripts' 	=> apply_filters( 'wicked_folders_after_ajax_scripts', array() ),
                'instances' => array(
                    'folderPane' => array(
                        'folders' 	=> $folders,
                        'state' 	=> $state,
                        'postType' 	=> $post_type,
                    ),
                ),
            ) );

            wp_enqueue_style( 'wicked-folders-admin' );
            wp_enqueue_style( 'wicked-folders-select2' );
            wp_enqueue_style( 'wicked-folders-app' );

            /*
            wp_add_inline_script(
                'wicked-folders-app',
                'wp.apiFetch.use( wp.apiFetch.createPreloadingMiddleware( ' . wp_json_encode( array( "/wicked-folders/v1/folders?post_type={$post_type}" => $folders ) ) . ' ) )',
            );
            */

            $html_css = "--wicked-folders-tree-pane-width: {$state->tree_pane_width}px;";

            if ( $is_wider_admin_menu_active ) {
                $wider_admin_menu_settings = get_option( 'wpmwam_options' );

                if ( isset( $wider_admin_menu_settings['wpmwam_width'] ) ) {
                    $menu_width = $wider_admin_menu_settings['wpmwam_width'];

                    $html_css .= "\n\t--wicked-folders-sidebar-width: {$menu_width}px;";
                }
            }
                        
            wp_add_inline_style( 'wicked-folders-app', "html {\n\t{$html_css}\n}" );

            wp_enqueue_script( 'wicked-folders-admin' );
            wp_enqueue_script( 'wicked-folders-app' );
            wp_enqueue_script( 'wicked-folders-select2' );		
        }

        // Allow this function to run again if the buffer contains a ACF WYSIWYG field;
        // otherwise the scripts won't be enqueued in some cases (such as when a block
        // renders an ACF WYSIWYG field with media buttons inside the block editor).
        if ( ! $this->output_buffer_contains_acf_wysiwyg_field() ) {
            $done = true;
        }
    }

    /**
     * 'admin_enqueue_scripts' action. Enqueues styles for the folder collection
     * policy and upload new media screens.
     */
    public function maybe_enqueue_scripts_for_settings_pages() {
        global $typenow;

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;

        if ( 'wf_collection_policy' == $typenow || ( false !== $screen && isset( $screen->base ) && isset( $screen->action ) && 'media' == $screen->base && 'add' == $screen->action ) ) {
            // Registered in main admin_enqueue_scripts method
            wp_enqueue_style( 'wicked-folders-admin' );
        }
    }

    public function wp_enqueue_media() {

        // TODO: refactor and enqueue scripts here as well rather than calling
        // admin_enqueue_scripts which may load unnecessary scripts
        $this->admin_enqueue_scripts();

    }

    public function get_screen_state( $screen_id = false, $user_id = false, $lang = false ) {
        // In rare instances, it appears this function is possibly being called before screen.php is included
        // which causes a fatal error. Not sure how that is possible though as it appears this file is included
        // as part of the admin bootstrapping process. See email from John B. on 11/3/2025 for additional details. 
		if ( ! function_exists( 'get_current_screen' ) ) {
			require_once ABSPATH . 'wp-admin/includes/screen.php';
		}

        $screen = get_current_screen();

        // TODO: consider statically caching screen states
        if ( ! $screen_id ) $screen_id = $screen ? get_current_screen()->id : false;
        if ( ! $user_id ) $user_id = get_current_user_id();
        if ( ! $lang ) $lang = Wicked_Folders::get_language();

        return new Screen_State( $screen_id, $user_id, $lang );

    }

    /**
     * Returns the post type for the current screen.
     */
    public static function get_current_screen_post_type() {
        global $typenow;

        $type = $typenow;
        $page = basename( $_SERVER['PHP_SELF'] );

        // $typenow isn't always set
        if ( ! $type && 'upload.php' == $page && empty( $_GET['page'] ) ) $type = 'attachment';

        // Assume post if we're on the edit page and no post type is available
        if ( ! $type && 'edit.php' == $page && empty( $_GET['page'] ) ) $type = 'post';

        if ( 'admin.php' == $page && isset( $_GET['page'] ) && 'tablepress' == $_GET['page'] && ( empty( $_GET['action'] ) || ( isset( $_GET['action'] ) && 'list' == $_GET['action'] ) ) ) $type = 'tablepress_table';

        if ( 'admin.php' == $page && isset( $_GET['page'] ) && 'wc-orders' == $_GET['page'] ) $type = 'shop_order';

        // Give pro version (and perhaps others) a chance to determine the post type
        $type = apply_filters( 'wicked_folders_get_current_screen_post_type', $type );

        return $type;
    }

    /**
     * Checks if we are on the posts page in the admin and folders are enabled
     * for the post type being viewed.
     *
     * @return bool
     */
    public function is_folder_pane_enabled_page() {
        // Handle AJAX quick/inline edit scenario
        if ( isset( $_POST['action'] ) && 'inline-save' == $_POST['action'] &&  isset( $_POST['post_type'] ) && Wicked_Folders::enabled_for( $_POST['post_type'] ) ) {
            return true;
        }

        $enabled        = true;
        $type 			= $this->get_current_screen_post_type();
        $page 			= basename( $_SERVER['PHP_SELF'] );
        $allowed_pages  = array( 'edit.php', 'upload.php', 'users.php', 'plugins.php', 'admin.php' );

        // Only enable folder pane on certain pages
        if ( ! in_array( $page, $allowed_pages ) ) $enabled = false;

        $enabled = apply_filters( 'wicked_folders_enable_object_folder_pane', $enabled );

        if ( ! $enabled ) return false;

        return Wicked_Folders::enabled_for( $type );
    }

    /**
     * Column headers for 'post' type.
     */
    public function manage_posts_columns( $columns ) {
        global $typenow;

        if ( $this->is_folder_pane_enabled_page() ) {
            $taxonomy = get_taxonomy( Wicked_Folders::get_tax_name( $typenow ) );

            $a = array( 'wicked_move' => '<div class="wicked-move-multiple" title="' . __( 'Move selected items', 'wicked-folders' ) . '"><span class="wicked-move-file dashicons dashicons-move"></span><div class="wicked-items"></div><span class="screen-reader-text">' . __( 'Move to Folder', 'wicked-folders' ) . '</span></div>' );
            $columns = $a + $columns;

            // Some plugins completely override the columns for their post type;
            // re-add the folder taxonomy columns for these instances
            if ( isset( $taxonomy->show_admin_column ) && $taxonomy->show_admin_column ) {
                $columns[ 'taxonomy-' . $taxonomy->name ] = $taxonomy->label;
            }
        }
        return $columns;
    }

    public function page_custom_column_content( $column_name, $post_id ) {
        if ( 'wicked_move' == $column_name ) {
            $title = get_the_title();

            if ( ! $title ) $title = __( '(no title)', 'wordpress' );

            echo '<div class="wicked-move-multiple" data-object-id="' . esc_attr( $post_id ) . '"><span class="wicked-move-file dashicons dashicons-move"></span><div class="wicked-items"><div class="wicked-item" data-object-id="' . esc_attr( $post_id ) . '">' . esc_html( $title ) . '</div></div>';
        }
        if ( 'wicked_sort' == $column_name ) {
            echo '<a class="wicked-sort" href="#"><span class="dashicons dashicons-menu"></span></a>';
        }
    }

    public function post_custom_column_content( $column_name, $post_id ) {
        if ( 'wicked_move' == $column_name ) {
            $title = get_the_title();

            if ( ! $title ) $title = __( '(no title)', 'wordpress' );

            echo '<div class="wicked-move-multiple" data-object-id="' . esc_attr( $post_id ) . '"><span class="wicked-move-file dashicons dashicons-move"></span><div class="wicked-items"><div class="wicked-item" data-object-id="' . esc_attr( $post_id ) . '">' . esc_html( $title ) . '</div></div>';
        }

        if ( 'wicked_sort' == $column_name ) {
            echo '<a class="wicked-sort" href="#"><span class="dashicons dashicons-menu"></span></a>';
        }
    }

    public function pre_get_posts( $query ) {
        // Only filter admin queries
        if ( is_admin() ) {
            // Initalize variables
            $filter_query 		= false;
            $folder_id 			= false;
            $taxonomy 			= false;
            $action 			= isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : false;
            $post_type 			= $query->get( 'post_type' );
            $include_children 	= false;

            // Only filter certain queries...
            if ( $query->is_main_query() ) $filter_query = true;
            if ( 'query-attachments' == $action && isset( $_REQUEST['query']['wf_attachment_folders'] ) ) $filter_query  = true;

            // Skip all other queries
            if ( ! $filter_query ) return;

            if ( Wicked_Folders::enabled_for( $post_type ) ) {
                $taxonomy   = Wicked_Folders::get_tax_name( $post_type );
                $folder_id  = isset( $_GET['folder'] ) ? sanitize_text_field( $_GET['folder'] ) : false;

                // Check for taxonomy filter
                if ( isset( $_GET[ $taxonomy ] ) ) {
                    $folder_id = sanitize_text_field( $_GET[ $taxonomy ] );
                }

                // Folder parameter is named differently on post list pages
                if ( isset( $_GET["wicked_{$post_type}_folder_filter"] ) ) {
                    $folder_id = sanitize_text_field( $_GET["wicked_{$post_type}_folder_filter"] );
                }

                // Folder parameter in different in query attachment requests
                if ( isset( $_REQUEST['query']['wf_attachment_folders'] ) ) {
                    $folder_id = sanitize_text_field( $_REQUEST['query']['wf_attachment_folders'] );
                }

                // If we don't have a folder, check the screen state
                if ( false === $folder_id && $this->is_folder_pane_enabled_page() ) {
                    $state 			= $this->get_screen_state();
                    $folder_id 		= $state->folder;
                }

                // Bail if we don't have a folder to filter by.  Note that folder ID '0' (i.e. 'All Folders') is
                // still considered a folder
                if ( false === $folder_id ) return;

                // Get the folder object
                $folder = Folder_Factory::get_folder( $folder_id, $post_type );

                // Term folders
                if ( $folder && 'Wicked_Folders\Term_Folder' == get_class( $folder ) ) {
                    $include_children = Wicked_Folders::include_children( $post_type, $folder->id );

                    // If the query is being filtered by a taxonomy query string
                    // parameter, make sure it respects the include children
                    // setting (by default, WordPress includes child terms when
                    // the query is filtered via the taxonomy query string
                    // parameter)
                    if ( isset( $_GET[ $taxonomy ] ) ) {
                        if ( isset( $query->tax_query->queries ) && is_array( $query->tax_query->queries ) ) {
                            $tax_queries = $query->tax_query->queries;

                            for ( $index = count( $tax_queries ) - 1; $index > -1; $index-- ) {
                                if ( $tax_queries[ $index ]['taxonomy'] == $taxonomy ) {
                                    $tax_queries[ $index ]['include_children'] = $include_children;
                                }
                            }

                            $query->set( 'tax_query', $tax_queries );
                        }
                    } else {
                        $tax_query = array(
                            array(
                                'taxonomy' 			=> $taxonomy,
                                'field' 			=> 'term_id',
                                'terms' 			=> $folder->id,
                                'include_children' 	=> $include_children,
                            ),
                        );

                        $query->set( 'tax_query', $tax_query );
                    }

                    if ( isset( $query->tax_query->queries ) && is_array( $query->tax_query->queries ) ) {
                        foreach ( $query->tax_query->queries as $index => $tax_query ) {
                            // $tax_query can sometimes be a relation string (e.g. 'AND' or 'OR') so
                            // make sure the taxonomy key is actually set before comparing it
                            if ( isset( $tax_query['taxonomy'] ) && $tax_query['taxonomy'] == $taxonomy ) {
                                $query->tax_query->queries[ $index ]['include_children'] = $include_children;
                            }
                        }
                    }
                }

                // Dynamic folders
                if ( $folder && $folder->is_dynamic ) {
                    // Folder tax queries won't work with dynamic folders so remove
                    Wicked_Folders::remove_tax_query( $query, 'wf_attachment_folders' );

                    $folder->pre_get_posts( $query );
                }

                $can_view_others_items = apply_filters( 'wicked_folders_can_view_others_items', true, get_current_user_id(), $taxonomy );

                if ( ! $can_view_others_items ) {
                    $query->set( 'author', get_current_user_id() );
                }
            }
        }
    }

    /**
     * wp_terms_checklist_args filter.
     */
    public function wp_terms_checklist_args( $args, $post_id ) {
        $post_types = Wicked_Folders::post_types();

        // WordPress displays hierarchical taxonomies as a flat list in the
        // category meta box when some categories are selected; disable this to
        // preserve the folder hierarchy in the folder category meta boxes
        foreach ( $post_types as $post_type ) {
            if ( isset( $args['taxonomy'] ) ) {
                if ( $args['taxonomy'] == Wicked_Folders::get_tax_name( $post_type ) ) {
                    $args['checked_ontop'] = false;
                }
            }
        }

        return $args;
    }

    /**
     * wpseo_primary_term_taxonomies filter.
     */
    public function wpseo_primary_term_taxonomies( $taxonomies, $post_type, $all_taxonomies ) {
        $folder_taxonomies = Wicked_Folders::taxonomies();

        // Remove Yoast primary category feature from folder taxonomies
        foreach ( $folder_taxonomies as $taxonomy ) {
            if ( isset( $taxonomies[ $taxonomy ] ) ) {
                unset( $taxonomies[ $taxonomy ] );
            }
        }

        return $taxonomies;
    }

    public function add_settings_to_plugin_action_links( $links ) {
        $settings_link = '<a href="' . esc_url( menu_page_url( 'wicked_folders_settings', 0 ) ) . '">' . __( 'Settings', 'wicked-folders' ) . '</a>';

        array_unshift( $links, $settings_link );

        return $links;
    }

    public function admin_footer() {
        $screen = get_current_screen();

        // Don't output the folder pane on the media grid view page; the folder
        // pane is part of the media library itself
        if ( 'upload' == $screen->base && 'grid' == $this->get_media_library_mode() ) return;

        // Only add folder pane to edit page
        if ( $this->is_folder_pane_enabled_page() ) {
            echo '<div id="wicked-folder-pane"></div>';
        }
    }

    /**
     * Helper function to get the mode that the media library is in.
     */
    public function get_media_library_mode() {
        $mode  = get_user_option( 'media_library_mode', get_current_user_id() );
        $mode  = $mode ? $mode : 'grid';
        $modes = array( 'grid', 'list' );

        if ( isset( $_GET['mode'] ) && in_array( $_GET['mode'], $modes ) ) {
            $mode = sanitize_text_field( $_GET['mode'] );
        }

        return $mode;
    }

    /**
     * 'post_column_taxonomy_links' filter.
     *
     */
    public function post_column_taxonomy_links( $term_links, $taxonomy, $terms = array() ) {
        // This should be false if not a folder taxonomy
        $post_type = Wicked_Folders::get_post_name_from_tax_name( $taxonomy );

        if ( $post_type ) {
            if ( true == get_option( 'wicked_folders_show_hierarchy_in_folder_column', false ) ) {
                $term_links 		= array();
                $taxonomy_object 	= get_taxonomy( $taxonomy );
                $separator 			= apply_filters( 'wicked_folders_breadcrumb_separator', ' <span class="wicked-folders-breacrumb-separator">&rsaquo;</span> ' );

                foreach ( $terms as $term ) {
                    $link_class = '';
                    $crumbs 	= array();
                    $url_args 	= array();
                    $parents 	= get_ancestors( $term->term_id, $taxonomy, 'taxonomy' );
                    $parents 	= array_reverse( $parents );
                    $parents[] 	= $term->term_id;

                    foreach ( $parents as $parent_term_id ) {
                        $parent_term = get_term( $parent_term_id, $taxonomy );

                        // Note: much of the following logic is copied from
                        // WP_Posts_List_Table::column_default()
                        if ( 'post' != $post_type ) {
                            $url_args['post_type'] = $post_type;
                        }

                        if ( $taxonomy_object->query_var ) {
                            $url_args[ $taxonomy_object->query_var ] = $parent_term->slug;
                        } else {
                            $url_args['taxonomy'] = $taxonomy;
                            $url_args['term']     = $parent_term->slug;
                        }

                        if ( $term->term_id == $parent_term_id ) {
                            $link_class = ' class="wicked-folders-in-folder"';
                        }

                        $url 	= add_query_arg( $url_args, 'edit.php' );

                        $label 	= esc_html( sanitize_term_field( 'name', $parent_term->name, $parent_term->term_id, $taxonomy, 'display' ) );

                        $crumbs[] = sprintf(
                            '<a%s href="%s">%s</a>',
                            $link_class,
                            esc_url( $url ),
                            $label
                        );
                    }

                    $term_links[] = '<span class="wicked-folders-taxonomy-breadcrumb-links">' . join( $separator, $crumbs ) . '</span>';

                    $term_links = apply_filters( 'wicked_folders_post_column_taxonomy_links', $term_links, $taxonomy, $terms );
                }
            }
        }

        return $term_links;
    }

    /**
     * Checks the output buffer for the presesnce of an ACF WYSIWYG field
     * and returns true if found, false otherwise.
     */
    public function output_buffer_contains_acf_wysiwyg_field() {
        if ( true === apply_filters( 'wicked_folders_enable_output_buffer_check', true ) ) {
            $buffer = ob_get_contents();

            if ( false !== strpos( $buffer, 'acf-field-wysiwyg' ) ) {
                return true;
            }
        }

        return false;
    }
}
