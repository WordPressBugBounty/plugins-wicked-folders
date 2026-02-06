<?php

use Wicked_Folders\Common;
use Wicked_Folders\REST_API\v1\Folder_API;
use Wicked_Folders\REST_API\v1\Screen_State_API;
use Wicked_Folders\Admin;
use Wicked_Folders\Author_Dynamic_Folder;
use Wicked_Folders\Author_Dynamic_Folder_Collection;
use Wicked_Folders\Date_Dynamic_Folder;
use Wicked_Folders\Date_Dynamic_Folder_Collection;
use Wicked_Folders\Folder;
use Wicked_Folders\Post_Hierarchy_Dynamic_Folder;
use Wicked_Folders\Term_Dynamic_Folder;
use Wicked_Folders\Term_Dynamic_Folder_Collection;
use Wicked_Folders\Term_Folder;
use Wicked_Folders\Unassigned_Dynamic_Folder;

// Disable direct load
defined( 'ABSPATH' ) || exit;

final class Wicked_Folders {

    private static $instance;

    private function __construct() {
        // Register autoload function
        spl_autoload_register( array( $this, 'autoload' ) );

        // Increased priority to 15 to accomodate Pods plugin which registers
        // its custom post types at priority 11
        // Increased priority to 25 to accomodate Barn2 Document Library plugin
        // which registers its Document post type at priority 15
        add_action( 'init',				array( $this, 'init' ), 25 );
        add_action( 'rest_api_init', 	array( $this, 'rest_api_init' ) );

        // Keep folder order in sync with sort order changes made by Category
        // Order and Taxonomy Terms Order plugin
        add_action( 'tto/update-order', array( $this, 'migrate_folder_order' ) );

        add_action( 'Wicked_Folders\All_Folders_Collection\fetch', array( Author_Dynamic_Folder_Collection::class,  'add_to_collection' ) );
        add_action( 'Wicked_Folders\All_Folders_Collection\fetch', array( Date_Dynamic_Folder_Collection::class,    'add_to_collection' ) );
        add_action( 'Wicked_Folders\All_Folders_Collection\fetch', array( Unassigned_Dynamic_Folder::class,         'add_to_collection' ) );
        add_action( 'Wicked_Folders\All_Folders_Collection\fetch', array( Post_Hierarchy_Dynamic_Folder::class,     'add_to_collection' ) );
        add_action( 'Wicked_Folders\All_Folders_Collection\fetch', array( Term_Dynamic_Folder_Collection::class,    'add_to_collection' ) );

        add_filter( 'et_common_should_enqueue_react', '__return_false' );
        
        // Initalize admin singleton
        Admin::get_instance();

        new Wicked_Folders\Integrations\WPML\Integrator();
    }

    /**
     * Plugin activation hook.
     */
    public static function activate() {

        // Check for multisite
        if ( is_multisite() && is_plugin_active_for_network( dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'wicked-folders.php' ) ) {
            $sites = get_sites( array( 'fields' => 'ids', 'number' => 999 ) );
            foreach ( $sites as $id ) {
                switch_to_blog( $id );
                Wicked_Folders::activate_site();
                restore_current_blog();
            }
        } else {
            Wicked_Folders::activate_site();
        }

    }

    /**
     * Activates/initalizes settings for a single site.
     */
    public static function activate_site() {
        $post_types = get_option( 'wicked_folders_post_types', false );
        $taxonomies = get_option( 'wicked_folders_taxonomies', false );
        $state 		= get_user_meta( get_current_user_id(), 'wicked_folders_plugin_state', true );

        // Enable folders for pages by default
        if ( ! $post_types ) {
            $post_types = array( 'page' );
            update_option( 'wicked_folders_post_types', $post_types );
            update_option( 'wicked_folders_dynamic_folder_post_types', $post_types );
        }

        if ( ! $taxonomies ) {
            $taxonomies = array( 'wf_page_folders' );
            update_option( 'wicked_folders_taxonomies', $taxonomies );
        }

        if ( ! $state ) {
            $state = array();
            update_user_meta( get_current_user_id(), 'wicked_folders_plugin_state', $state );
        }
    }

    public static function autoload( $class ) {

        $file 	= false;
        $files  = array(
            'Wicked_Folders\Screen_State' 					    => 'classes/screen-state.php',
            'Wicked_Folders\Admin' 							    => 'classes/admin.php',
            'Wicked_Folders\Folder' 						    => 'classes/folder.php',
            'Wicked_Folders\Tree_View' 						    => 'classes/tree-view.php',
            'Wicked_Folders\Term_Folder' 					    => 'classes/term-folder.php',
            'Wicked_Folders\Dynamic_Folder'   				    => 'classes/dynamic-folder.php',
            'Wicked_Folders\Dynamic_Folder_Collection'   	    => 'classes/dynamic-folder-collection.php',
            'Wicked_Folders\Author_Dynamic_Folder'  		    => 'classes/author-dynamic-folder.php',
            'Wicked_Folders\Author_Dynamic_Folder_Collection'   => 'classes/author-dynamic-folder-collection.php',
            'Wicked_Folders\Date_Dynamic_Folder'   			    => 'classes/date-dynamic-folder.php',
            'Wicked_Folders\Date_Dynamic_Folder_Collection'     => 'classes/date-dynamic-folder-collection.php',
            'Wicked_Folders\Term_Dynamic_Folder'   			    => 'classes/term-dynamic-folder.php',
            'Wicked_Folders\Term_Dynamic_Folder_Collection'     => 'classes/term-dynamic-folder-collection.php',
            'Wicked_Folders\Common' 						    => 'classes/common.php',
            'Wicked_Folders\Unassigned_Dynamic_Folder' 		    => 'classes/unassigned-dynamic-folder.php',
            'Wicked_Folders\Post_Hierarchy_Dynamic_Folder' 	    => 'classes/post-hierarchy-dynamic-folder.php',
            'Wicked_Folders\Collection' 				        => 'classes/collection.php',
            'Wicked_Folders\Folder_Collection' 				    => 'classes/folder-collection.php',
            'Wicked_Folders\All_Folders_Collection' 		    => 'classes/all-folders-collection.php',
            'Wicked_Folders\JSON_Serializable_Object' 		    => 'classes/json-serializable-object.php',
            'Wicked_Folders\Singleton' 						    => 'classes/singleton.php',
            'Wicked_Folders\Folder_Factory' 				    => 'classes/folder-factory.php',
            'Wicked_Folders\Integrations\WPML\Integrator' 	    => 'classes/integrations/wpml/integrator.php',
            'Wicked_Folders\REST_API\v1\REST_API' 			    => 'classes/rest-api/v1/rest-api.php',
            'Wicked_Folders\REST_API\v1\Folder_API' 		    => 'classes/rest-api/v1/folder-api.php',
            'Wicked_Folders\REST_API\v1\Screen_State_API' 	    => 'classes/rest-api/v1/screen-state-api.php',
        );

        if ( array_key_exists( $class, $files ) ) {
            $file = dirname( dirname( __FILE__ ) ) . '/' . $files[ $class ];
        }

        if ( $file ) {
            $file = str_replace( '/', DIRECTORY_SEPARATOR, $file );
            include_once( $file );
        }

    }

    public function rest_api_init() {
        $folder_api 		= new Folder_API();
        $screen_state_api 	= new Screen_State_API();
    }

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new Wicked_Folders();
        }
        return self::$instance;
    }

    public function init() {

        // Folder taxonomies were originally named 'wicked_{$post_type}_folders'
        // which could lead to taxonomy names that exceeded the allowed length
        // of 32 characters. Migrate folder taxonomy names if we haven't done so
        // already.
        $tax_name_migration_done 	= get_option( 'wicked_folders_tax_name_migration_done', false );
        $db_version 				= get_option( 'wicked_folders_db_version', '0' );

        if ( false === $tax_name_migration_done ) {
            $this->migrate_folder_taxonomy_names();
        }

        $this->register_taxonomies();

        Admin::get_instance()->save_settings();

        // Update existing installs that don't have the dynamic folders option set yet
        $post_types = get_option( 'wicked_folders_dynamic_folder_post_types', false );

        if ( false === $post_types ) {
            update_option( 'wicked_folders_dynamic_folder_post_types', $this->post_types() );
        }

        if ( version_compare( $db_version, '2.17.0', '<' ) ) {
            update_option( 'wicked_folders_db_version', '2.17.0' );
        }

        if ( version_compare( $db_version, '2.17.5', '<' ) ) {
            $this->migrate_folder_order();
            update_option( 'wicked_folders_db_version', '2.17.5' );
        }

        if ( version_compare( $db_version, '2.18.13', '<' ) ) {
            // Clear dynamic folder cache
            Common::delete_transients_with_prefix( 'wicked_folders_dynamic' );
            update_option( 'wicked_folders_db_version', '2.18.13' );
        }

        if ( version_compare( $db_version, '3', '<' ) ) {
            // Clear dynamic folder cache
            Common::delete_transients_with_prefix( 'wicked_folders_dynamic' );
            update_option( 'wicked_folders_db_version', '3' );
        }		
        
        if ( version_compare( $db_version, '4', '>=' ) ) {
            delete_option( 'wicked_folders_enable_folder_pages' );
        }	      
        
        if ( version_compare( $db_version, '4.0.0', '<' ) ) {
            // Set default folder color options
            update_option( 'wicked_folders_colors', array(
                '#ff2d55',
                '#5856d6',
                '#ff9500',
                '#ffcc00',
                '#ff3b30',
                '#5ac8fa',
                '#007aff',
                '#4cd964',
                '#2271b1',
                '#8c8f94',
            ) );
            update_option( 'wicked_folders_db_version', '4.0.0' );
        }        
    }

    public function register_taxonomies() {
        static $done = false;

        // Only execute this function once per request
        if ( $done ) return false;

        $post_types = Wicked_Folders::post_type_objects();

        // Create a folder taxonomy for each post type
        foreach ( $post_types as $post_type ) {

            $tax_name = Wicked_Folders::get_tax_name( $post_type->name );

            $labels = array(
                'name'			=> sprintf( _x( '%1$s Folders', 'Taxonomy plural name', 'wicked-folders' ), $post_type->labels->singular_name ),
                'singular_name' => sprintf( _x( '%1$s Folder', 'Taxonomy singular name', 'wicked-folders' ), $post_type->labels->singular_name ),
                'all_items'		=> sprintf( __( 'All %1$s Folders', 'wicked-folders' ), $post_type->labels->singular_name ),
                'edit_item'		=> __( 'Edit Folder', 'wicked-folders' ),
                'update_item'	=> __( 'Update Folder', 'wicked-folders' ),
                'add_new_item'	=> __( 'Add New Folder', 'wicked-folders' ),
                'new_item_name' => __( 'Add Folder Name', 'wicked-folders' ),
                'menu_name'     => __( 'Folders', 'wicked-folders' ),
                'search_items'  => __( 'Search Folders', 'wicked-folders' ),
                'parent_item' 	=> __( 'Parent Folder', 'wicked-folders' ),
            );

            $args = array(
                'label'				=> _x( 'Folders', 'Taxonomy plural name', 'wicked-folders' ),
                'labels'			=> $labels,
                'show_tagcloud' 	=> false,
                'hierarchical'		=> true,
                'public'        	=> false,
                'show_ui'       	=> true,
                'show_in_menu'  	=> false,
                'show_in_rest' 		=> true,
                'show_admin_column' => true,
                'rewrite'			=> false,
            );

            register_taxonomy( $tax_name, $post_type->name, $args );

            add_action( "create_{$tax_name}", array( Term_Folder::class, 'folder_term_created' ), 10, 2 );
        }

        $done = true;
    }

    /**
     * Gets the posts types that folders are enabled for.
     *
     * @return array
     *  Array of post types.
     */
    public static function post_types() {
        $post_types = get_option( 'wicked_folders_post_types', array() );

        return apply_filters( 'wicked_folders_post_types', $post_types );
    }

    /**
     * Gets the posts type objects that folders are enabled for.
     *
     * @return array
     *  Array of WP_Post_Type Object objects.
     */
    public static function post_type_objects() {
        $post_types 		= array();
        $enabled_post_types = Wicked_Folders::post_types();
        $all_post_types 	= get_post_types( array(
            'show_ui' => true,
        ), 'objects' );

        if ( $tablepress_post_type = get_post_type_object( 'tablepress_table' ) ) {
            $all_post_types[] = $tablepress_post_type;
        }

        foreach ( $all_post_types as $post_type ) {
            if ( in_array( $post_type->name, $enabled_post_types ) ) {
                $post_types[] = $post_type;
            }
        }

        return apply_filters( 'wicked_folders_post_type_objects', $post_types );
    }

    /**
     * Gets the posts types that dynamic folders are enabled for.
     *
     * @return array
     *  Array of post types.
     */
    public static function dynamic_folder_post_types() {
        $post_types = get_option( 'wicked_folders_dynamic_folder_post_types', array() );
        return apply_filters( 'wicked_folders_dynamic_folder_post_types', $post_types );
    }

    /**
     * Gets the taxonomies that folders are enabled for.
     *
     * @return array
     *  Array of taxonomy system names.
     */
    public static function taxonomies() {
        $taxonomies = get_option( 'wicked_folders_taxonomies', array() );
        return apply_filters( 'wicked_folders_taxonomies', $taxonomies );
    }

    /**
     * Moves an object to the specified folder.
     *
     * TODO: maybe change to two functions...move folder and move post?
     *
     * @param string $object_type
     *  'folder' or 'post' for all other objects
     *
     * @param int $object_id
     *  The ID of the object being moved.
     *
     * @param int $destination_folder_id
     *  The ID of the folder that the object is being moved to.
     *
     * @param int $source_folder_id
     *  For post object types, the folder ID the object is being moved from.
     *
     * @return bool
     *  True on success, false on failure.
     */
    public static function move_object( $object_type, $object_id, $destination_folder_id, $source_folder_id = false ) {

        if ( 'folder' == $object_type ) {
            $object = get_term( $object_id );
            $result = wp_update_term( $object->term_id, $object->taxonomy, array(
                'parent' => $destination_folder_id,
            ) );
            return !! is_wp_error( $result );
        }

        if ( 'post' == $object_type ) {
            // Get the folder term
            $folder = get_term( $destination_folder_id );
            // Get the folders that the post is currently assigned to
            $terms 	= wp_get_object_terms( $object_id, $folder->taxonomy, array(
                'fields' => 'ids',
            ) );
            // Add the destination folder
            if ( 0 !== $destination_folder_id ) {
                $terms[] = $destination_folder_id;
            }
            $terms = array_unique( $terms );
            // Remove the object from the source folder
            if ( false !== $source_folder_id && $source_folder_id != $destination_folder_id ) {
                $source_folder_index = array_search( $source_folder_id, $terms );
                if ( false !== $source_folder_index ) {
                    unset( $terms[ $source_folder_index ] );
                }
            }
            $result = wp_set_object_terms( $object_id, $terms, $folder->taxonomy );
        }

    }

    /**
     * Gets a folder.
     * 
     * Deprecated as of version 4.0.0.
     *
     * @param string $id
     *  The folder's ID.
     *
     * @param string $post_type
     *  The post type name that the folder is registered with.
     *
     * @param string $taxonomy
     *  The taxonomy name to get folders from.
     *
     * @return Wicked_Folders\Folder|bool
     *  A Wicked_Folders\Folder object or false if the folder doesn't exist.
     */
    public static function get_folder( $id, $post_type, $taxonomy = false ) {
        _doing_it_wrong( __METHOD__, __( 'This function has been deprecated. Use Wicked_Folders\Folder_Factory::get_folder( $id, $post_type ) instead.', 'wicked-folders' ), '4.0.0' );
    }

    /**
     * Gets the folder objects for the specified post type and taxonomy.
     * 
     * Deprecated as of version 4.0.0.
     *
     * @param string $post_type
     *  The post type name.
     *
     * @param string $taxonomy
     *  The taxonomy name to get folders from.
     *
     * @return array
     *  Array of Wicked_Folders\Folder objects.
     */
    public static function get_folders( $post_type, $taxonomy = false ) {
        _doing_it_wrong( __METHOD__, __( 'This function has been deprecated. Use Wicked_Folders\All_Folders_Collection instead.', 'wicked-folders' ), '4.0.0' );

        return array();
    }

    /**
     * Returns true if folders are enabled for the specified post type, false
     * if not.
     *
     * @param string $post_type
     *  The post type name to check.
     *
     * @return bool
     */
    public static function enabled_for( $post_type ) {
        // Don't allow folders to be enabled for a post type that doesn't exist
        if ( ! post_type_exists( $post_type ) ) return false;

        $post_types = Wicked_Folders::post_types();

        return in_array( $post_type, $post_types );
    }

    /**
     * Returns true if dynamic folders are enabled for the specified post type,
     * false if not.
     *
     * @param string $post_type
     *  The post type name to check.
     *
     * @return bool
     */
    public static function dynamic_folders_enabled_for( $post_type ) {
        $post_types = Wicked_Folders::dynamic_folder_post_types();

        return in_array( $post_type, $post_types );
    }

    /**
     * Returns the plugin's version.
     */
    public static function plugin_version() {
        static $version = false;

        $core_plugin_file 	= dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'wicked-folders.php';
        $pro_plugin_file 	= dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'wicked-folders-pro.php';

        if ( ! $version && function_exists( 'get_plugin_data' ) ) {
            // Core plugin file is removed from pro version to avoid invalid header during activate after upload
            if ( file_exists( $core_plugin_file ) ) {
                $plugin_data 	= get_plugin_data( $core_plugin_file, false, false );
                $version 		= $plugin_data['Version'];
            } elseif ( file_exists( $pro_plugin_file ) ) {
                $plugin_data 	= get_plugin_data( $pro_plugin_file, false, false );
                $version 		= $plugin_data['Version'];
            }			
        }

        return $version;
    }

    /**
     * The timezone string set on the site's General Settings page.
     *
     * Thanks to this article on SkyVerge for handling UTC offsets:
     * https://www.skyverge.com/blog/down-the-rabbit-hole-wordpress-and-timezones/
     *
     * @return string
     *  A string that can be used to instantiate a DateTimeZone object.
     */
    public static function timezone_identifier() {
        static $cache = null;

        if ( null !== $cache ) return $cache;

        $timezones 	= timezone_identifiers_list();
        $timezone 	= get_option( 'timezone_string' );

        // If site timezone string is valid, return it
        if ( in_array( $timezone, $timezones ) ) {
            $cache = $timezone;

            return $timezone;
        }

        // Get UTC offset, if it isn't set then return UTC
        if ( 0 === ( $utc_offset = get_option( 'gmt_offset', 0 ) ) ) {
            $cache = 'UTC';

            return 'UTC';
        }

        // Round offsets like 7.5 down to 7
        // TODO: explore if this is the right approach
        $utc_offset = round( $utc_offset, 0, PHP_ROUND_HALF_DOWN );

        // Adjust UTC offset from hours to seconds
        $utc_offset *= 3600;

        // Attempt to guess the timezone string from the UTC offset
        if ( $timezone = timezone_name_from_abbr( '', $utc_offset, 0 ) ) {
            // Make sure timezone is valid
            if ( in_array( $timezone, $timezones ) ) {
                $cache = $timezone;

                return $timezone;
            }
        }

        // Last try, guess timezone string manually
        $is_dst = date( 'I' );

        foreach ( timezone_abbreviations_list() as $abbr ) {
            foreach ( $abbr as $city ) {
                if ( $city['dst'] == $is_dst && $city['offset'] == $utc_offset ) {
                    // Make sure timezone is valid
                    if ( in_array( $city['timezone_id'], $timezones ) ) {
                        $cache = $city['timezone_id'];

                        return $city['timezone_id'];
                    }
                }
            }
        }

        // Fallback to UTC
        $cache = 'UTC';

        return 'UTC';
    }

    /**
     * Utility function that removes queries for the specified taxonomy from
     * the query.
     *
     * @param WP_Query_Object $query
     *  The query to remove the tax query from.
     *
     * @param string $taxonomy
     *  The name of the taxonomy to remove
     */
    public static function remove_tax_query( $query, $taxonomy ) {
        $tax_queries = $query->get( 'tax_query' );
        if ( is_array( $tax_queries ) ) {
            for ( $i = count( $tax_queries ); $i > -1; $i-- ) {
                // Make sure index exists (index could be something non-numeric
                // like 'operator')
                if ( isset( $tax_queries[ $i ]['taxonomy'] ) ) {
                    if ( $taxonomy == $tax_queries[ $i ]['taxonomy'] ) {
                        unset( $tax_queries[ $i ] );
                    }
                }
            }
            $query->set( 'tax_query', $tax_queries );
        }
    }

    /**
     * Checks if upselling is enabled.
     */
    public static function is_upsell_enabled() {
        $upsell = true;
        if ( defined( 'WICKED_PLUGINS_ENABLE_UPSELL' ) ) {
            $upsell = WICKED_PLUGINS_ENABLE_UPSELL;
        }
        return apply_filters( 'wicked_plugins_enable_upsell', $upsell );
    }

    /**
     * Returns a folder taxonomy name for a post type ensuring that the name is
     * 32 characters or less.
     *
     * @param string $post_name
     *  The machine name of the post type.
     */
    public static function get_tax_name( $post_name ) {
        if ( null === $post_name ) {
            return false;
        }
        
        // Post names are only allowed to be 20 characters so it shouldn't be
        // necessary to trim the name but do it just in case to ensure the
        // taxonomy name never exceeds 32 characters
        return 'wf_' . substr( $post_name, 0, 20 ) . '_folders';
    }

    /**
     * Parses the name of the post type from a folder taxonomy.
     *
     * @param string $taxonomy
     *  The taxonomy name.
     * @return string|bool
     *  The name of the post type that the folder taxonomy is for or, false if
     *  the taxonomy is not a folder taxonomy.
     */
    public static function get_post_name_from_tax_name( $taxonomy ) {
        if ( 0 === strpos( $taxonomy, 'wf_' ) && '_folders' == substr( $taxonomy, -8 ) ) {
            return substr( $taxonomy, 3, -8 );
        }

        return false;
    }

    // Migrates folder taxonomy names to the new prefix of 'wf_' (instead of
    // 'wicked_').
    private function migrate_folder_taxonomy_names() {
        global $wpdb;

        $result = $wpdb->get_results( "SELECT term_taxonomy_id, taxonomy FROM `{$wpdb->prefix}term_taxonomy` WHERE taxonomy LIKE 'wicked_%_folders'" );

        foreach ( $result as $result ) {
            // Get taxonomy name
            $tax_name = $result->taxonomy;
            // Strip the 'wicked_' prefix
            $tax_name = substr( $tax_name, 7 );
            // Prepend new 'wf_' prefix
            $tax_name = 'wf_' . $tax_name;
            $wpdb->update(
                "{$wpdb->prefix}term_taxonomy",
                array( 'taxonomy' => $tax_name ),
                array( 'term_taxonomy_id' => $result->term_taxonomy_id ),
                array( '%s' ),
                array( '%d' )
            );
        }

        // Update folder order keys
        $wpdb->query( "UPDATE {$wpdb->prefix}postmeta SET meta_key = REPLACE(meta_key, '_wicked_folder_order__wicked_', '_wicked_folder_order__wf_') WHERE meta_key LIKE '_wicked_folder_order__wicked_%'" );

        update_option( 'wicked_folders_tax_name_migration_done', true );

    }

    /**
     * Migrates folder sort order from wp_terms.term_order to a term meta value
     * named 'wf_order'.  Prior to version 2.17.5, the folder sort order was
     * stored in this column; however, it appears that term_order is not part of
     * the WordPress table schema for wp_terms.  Instead, it appears that the
     * field was added by the Category Order and Taxonomy Terms Order plugin and
     * the field was mistakenly thought to be a native WordPress field.
     */
    public function migrate_folder_order() {
        global $wpdb;

        // Nothing to do if the column doesn't exist
        if ( ! $this->term_order_field_exists() ) return;

        // Fetch all Wicked Folder terms that have an order
        $results = $wpdb->get_results( "SELECT t.term_id, t.term_order FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE tt.taxonomy LIKE 'wf_%_folders' AND t.term_order <> 0" );

        // Store sort orders in term meta
        foreach ( $results as $result ) {
            update_term_meta( $result->term_id, 'wf_order', $result->term_order );
        }
    }

    /**
     * Determines whether or not the field wp_terms.term_order exists.
     *
     * @return boolean
     *  True if the field exists, false otherwise.
     */
    public function term_order_field_exists() {
        global $wpdb;

        $result = $wpdb->query( "SHOW COLUMNS FROM {$wpdb->terms} LIKE 'term_order'" );

        return 1 === $result;
    }

    /**
     * For a given post type (and optionally, a specific folder), returns
     * whether or not folders should include items from child folders (the
     * default behavior is to only include items in the currently selected
     * folder.
     *
     * @param string $post_type
     *  The post type name (e.g. post, page, attachment, etc.)
     * @param integer $folder_id
     *  The folder ID (i.e. term ID).
     * @return boolean
     *  True if children should be included, false otherwise.
     */
    public static function include_children( $post_type, $folder_id = false ) {
        $option_name = 'attachment' == $post_type ? 'wicked_folders_include_attachment_children' : 'wicked_folders_include_children';

        // This option can be changed on the Settings page
        $include_children = get_option( $option_name, false );

        // Give others a chance to override the setting
        $include_children = ( bool ) apply_filters( 'wicked_folders_include_children', $include_children, $post_type, $folder_id );

        return $include_children;
    }

    /**
     * Gets the current language being viewed.
     *
     * @return string|bool
     *  The two letter language code of the current language or false if unknown
     *  or all languages are being viewed.
     */
    public static function get_language() {
        if ( function_exists( 'pll_current_language' ) ) {
            $lang = pll_current_language();
        }

        if ( empty( $lang ) ) $lang = false;

        return apply_filters( 'wicked_folders_get_language', $lang );
    }

    public static function is_folder_taxonomy_translated( $taxonomy ) {
        $translated = false;

        if ( function_exists( 'pll_is_translated_taxonomy' ) ) {
            $translated = pll_is_translated_taxonomy( $taxonomy );
        }

        return apply_filters( 'wicked_folders_is_folder_taxonomy_translated', $translated, $taxonomy );
    }
}
