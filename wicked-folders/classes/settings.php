<?php

namespace Wicked_Folders;

use Wicked_Folders;

// Disable direct load
defined( 'ABSPATH' ) || exit;

/**
 * Settings page. The page is a single page app that is rendered from a schema
 * of tabs, groups and fields. Fields are stored and retrieved through the
 * settings REST API.
 *
 * The schema can be extended using the following filters:
 *
 * - wicked_folders_settings_tabs
 * - wicked_folders_settings_groups
 * - wicked_folders_settings_fields
 * - wicked_folders_settings_app_data
 *
 * Fields support the following properties:
 *
 * - id                 Unique ID of the field (required).
 * - group              ID of the group the field belongs to (required).
 * - type               toggle, checkboxes, select, colors or the name of a
 *                      custom field type registered in JavaScript using the
 *                      'wickedFolders.settings.fieldTypes' filter.
 * - label              Field label.
 * - help               Help text displayed below the field.
 * - hide_label         Visually hide the label.
 * - options            Array of options for checkboxes and select fields.
 *                      Each option is an array with 'value' and 'label' and
 *                      optionally 'disabled' and 'badge'.
 * - option             Name of the option the field's value is stored in.
 * - default            Default value of the field.
 * - get_callback       Callable used to get the field's value instead of
 *                      reading 'option'.
 * - save_callback      Callable used to save the field's value instead of
 *                      updating 'option'. Receives the sanitized value.
 * - sanitize_callback  Callable used to sanitize the field's value instead of
 *                      the default sanitization for the field's type.
 *
 * Fields without an 'option' or 'save_callback' are display only. Any other
 * properties are passed to the field's JavaScript component.
 */
final class Settings {

    const PAGE_SLUG = 'wicked_folders_settings';

    private static $instance;

    private function __construct() {
        add_action( 'admin_menu',                   array( $this, 'admin_menu' ), 10000 );
        add_action( 'admin_enqueue_scripts',        array( $this, 'admin_enqueue_scripts' ) );
        add_filter( 'wicked_folders_settings_fields', array( $this, 'pro_locked_post_types' ), 1000, 2 );
    }

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new Settings();
        }

        return self::$instance;
    }

    /**
     * WordPress 'admin_menu' action.
     */
    public function admin_menu() {
        add_submenu_page(
            'options-general.php',
            __( 'Wicked Folders Settings', 'wicked-folders' ),
            __( 'Wicked Folders', 'wicked-folders' ),
            'manage_options',
            self::PAGE_SLUG,
            array( $this, 'render_page' )
        );
    }

    /**
     * Returns true if Wicked Folders Pro is active.
     */
    public static function is_pro_active() {
        return class_exists( 'Wicked_Folders\Pro\Plugin' );
    }

    /**
     * Returns the capability required to manage settings for a context.
     *
     * @param string $context
     *  'site' for the site settings page or 'network' for the network
     *  settings page.
     */
    public static function get_capability( $context = 'site' ) {
        return 'network' == $context ? 'manage_network_options' : 'manage_options';
    }

    /**
     * Returns the tabs displayed on the settings page.
     *
     * @param string $context
     *  'site' or 'network'.
     */
    public function get_tabs( $context = 'site' ) {
        $tabs = array();

        if ( 'site' == $context ) {
            $tabs[] = array(
                'id'    => 'general',
                'label' => __( 'General', 'wicked-folders' ),
            );

            $tabs[] = array(
                'id'    => 'dynamic',
                'label' => __( 'Dynamic Folders', 'wicked-folders' ),
            );
        }

        return array_values( apply_filters( 'wicked_folders_settings_tabs', $tabs, $context ) );
    }

    /**
     * Returns the groups of settings displayed on the settings page.
     *
     * @param string $context
     *  'site' or 'network'.
     */
    public function get_groups( $context = 'site' ) {
        $groups = array();

        if ( 'site' == $context ) {
            $groups = array(
                array(
                    'id'            => 'post_types',
                    'tab'           => 'general',
                    'title'         => __( 'Where folders appear', 'wicked-folders' ),
                    'description'   => __( 'Choose what content can be organized into folders. You can turn these on or off at any time without losing your folders.', 'wicked-folders' ),
                ),
                array(
                    'id'            => 'folder_pane',
                    'tab'           => 'general',
                    'title'         => __( 'Folder pane', 'wicked-folders' ),
                    'description'   => __( 'Control what is shown in the folder pane.', 'wicked-folders' ),
                ),
                array(
                    'id'            => 'lists',
                    'tab'           => 'general',
                    'title'         => __( 'Lists and navigation', 'wicked-folders' ),
                    'description'   => __( 'How folders work with your lists of posts, pages and other items.', 'wicked-folders' ),
                ),
                array(
                    'id'            => 'colors',
                    'tab'           => 'general',
                    'title'         => __( 'Folder colors', 'wicked-folders' ),
                    'description'   => __( 'The palette available when choosing a color for a folder.', 'wicked-folders' ),
                ),
                array(
                    'id'            => 'dynamic_folders',
                    'tab'           => 'dynamic',
                    'title'         => __( 'Dynamic folders', 'wicked-folders' ),
                    'description'   => __( 'Dynamic folders are generated on the fly based on your content. They are useful for finding content based on things like date, author, etc. Not all content types work with dynamic folders.', 'wicked-folders' ),
                ),
                array(
                    'id'            => 'dynamic_performance',
                    'tab'           => 'dynamic',
                    'title'         => __( 'Performance', 'wicked-folders' ),
                    'description'   => __( 'Control when dynamic folders are loaded.', 'wicked-folders' ),
                ),
            );
        }

        return array_values( apply_filters( 'wicked_folders_settings_groups', $groups, $context ) );
    }

    /**
     * Returns the settings fields.
     *
     * @param string $context
     *  'site' or 'network'.
     */
    public function get_fields( $context = 'site' ) {
        $fields = array();

        if ( 'site' == $context ) {
            $post_types = $this->get_post_types();
            $options    = array();

            foreach ( $post_types as $post_type ) {
                $options[] = array(
                    'value' => $post_type->name,
                    'label' => $post_type->label,
                );
            }

            // Post types that don't support dynamic folders
            $no_dynamic_folders = array(
                'wf_gf_form',
                'wf_gf_entry',
                'tablepress_table',
                'wf_rcp_membership',
                'wf_rcp_customer',
                'wf_rcp_level',
                'wf_afi_integration',
                'wpforms',
                'wf_plugin',
                'wf_wc_product_review',
            );

            $dynamic_options = array_values( array_filter( $options, function( $option ) use ( $no_dynamic_folders ) {
                return ! in_array( $option['value'], $no_dynamic_folders );
            } ) );

            $fields = array(
                array(
                    'id'            => 'post_types',
                    'group'         => 'post_types',
                    'type'          => 'checkboxes',
                    'label'         => __( 'Enable folders for', 'wicked-folders' ),
                    'hide_label'    => true,
                    'options'       => $options,
                    'option'        => 'wicked_folders_post_types',
                    'default'       => array(),
                ),
                array(
                    'id'            => 'show_item_counts',
                    'group'         => 'folder_pane',
                    'type'          => 'toggle',
                    'label'         => __( 'Show number of items in each folder', 'wicked-folders' ),
                    'help'          => __( "Displays the number of items assigned to each folder next to the folder's name.", 'wicked-folders' ),
                    'option'        => 'wicked_folders_show_item_counts',
                    'default'       => true,
                ),
                array(
                    'id'            => 'show_unassigned_folder',
                    'group'         => 'folder_pane',
                    'type'          => 'toggle',
                    'label'         => __( 'Show unassigned items folder', 'wicked-folders' ),
                    'help'          => __( "Adds an 'Unassigned Items' folder that displays items that have not been assigned to a folder.", 'wicked-folders' ),
                    'option'        => 'wicked_folders_show_unassigned_folder',
                    'default'       => true,
                ),
                array(
                    'id'            => 'show_folder_search',
                    'group'         => 'folder_pane',
                    'type'          => 'toggle',
                    'label'         => __( 'Show folder search', 'wicked-folders' ),
                    'help'          => __( 'Displays a search field above the folder tree that lets you search folders by name.', 'wicked-folders' ),
                    'option'        => 'wicked_folders_show_folder_search',
                    'default'       => true,
                ),
                array(
                    'id'            => 'enable_context_menus',
                    'group'         => 'folder_pane',
                    'type'          => 'toggle',
                    'label'         => __( 'Enable folder context menus', 'wicked-folders' ),
                    'help'          => __( "Displays a button next to each folder's name that opens a menu with actions such as rename, edit, and delete.", 'wicked-folders' ),
                    'option'        => 'wicked_folders_enable_context_menus',
                    'default'       => true,
                ),
                array(
                    'id'            => 'show_breadcrumbs',
                    'group'         => 'lists',
                    'type'          => 'toggle',
                    'label'         => __( 'Show folder breadcrumbs', 'wicked-folders' ),
                    'help'          => __( 'Displays a breadcrumb trail at the top of post lists.', 'wicked-folders' ),
                    'option'        => 'wicked_folders_show_breadcrumbs',
                    'default'       => true,
                ),
                array(
                    'id'            => 'show_hierarchy_in_folder_column',
                    'group'         => 'lists',
                    'type'          => 'toggle',
                    'label'         => __( 'Show folder hierarchy in folder column', 'wicked-folders' ),
                    'help'          => __( 'When off, the folder column in post lists shows a comma-separated list of folders. When on, it shows the full path of each folder the item is assigned to.', 'wicked-folders' ),
                    'option'        => 'wicked_folders_show_hierarchy_in_folder_column',
                    'default'       => false,
                ),
                array(
                    'id'            => 'include_children',
                    'group'         => 'lists',
                    'type'          => 'toggle',
                    'label'         => __( 'Include items from child folders', 'wicked-folders' ),
                    'help'          => __( "When off, selecting a folder only shows items assigned to that folder. When on, items in the folder's child folders are shown as well.", 'wicked-folders' ),
                    'option'        => 'wicked_folders_include_children',
                    'default'       => false,
                ),
                array(
                    'id'            => 'enable_ajax_nav',
                    'group'         => 'lists',
                    'type'          => 'toggle',
                    'label'         => __( "Don't reload page when navigating folders", 'wicked-folders' ),
                    'help'          => __( 'When on, moving between folders updates the list without reloading the page.', 'wicked-folders' ),
                    'option'        => 'wicked_folders_enable_ajax_nav',
                    'default'       => true,
                ),
                array(
                    'id'            => 'colors',
                    'group'         => 'colors',
                    'type'          => 'colors',
                    'label'         => __( 'Colors', 'wicked-folders' ),
                    'hide_label'    => true,
                    'option'        => 'wicked_folders_colors',
                    'default'       => array(),
                ),
                array(
                    'id'            => 'dynamic_folder_post_types',
                    'group'         => 'dynamic_folders',
                    'type'          => 'checkboxes',
                    'label'         => __( 'Enable dynamic folders for', 'wicked-folders' ),
                    'hide_label'    => true,
                    'options'       => $dynamic_options,
                    'option'        => 'wicked_folders_dynamic_folder_post_types',
                    'default'       => array(),
                ),
                array(
                    'id'            => 'enable_lazy_dynamic_folders',
                    'group'         => 'dynamic_performance',
                    'type'          => 'toggle',
                    'label'         => __( 'Lazy load dynamic folders', 'wicked-folders' ),
                    'help'          => __( 'Improves performance by only loading dynamic folders when needed. When off, dynamic folders are loaded immediately.', 'wicked-folders' ),
                    'option'        => 'wicked_folders_enable_lazy_dynamic_folders',
                    'default'       => true,
                ),
            );
        }

        return array_values( apply_filters( 'wicked_folders_settings_fields', $fields, $context ) );
    }

    /**
     * Returns the post types that can be enabled on the settings page.
     */
    public function get_post_types() {
        $unsupported_types  = array( 'shop_webhook', 'wf_collection_policy', 'nf_sub', 'wp_navigation' );
        $post_types         = get_post_types( array(
            'show_ui' => true,
        ), 'objects' );

        if ( null !== $wpforms_post_type = get_post_type_object( 'wpforms' ) ) {
            $post_types['wpforms'] = $wpforms_post_type;
        }

        if ( $tablepress_post_type = get_post_type_object( 'tablepress_table' ) ) {
            $tablepress_post_type->show_ui = true;

            $post_types['tablepress_table'] = $tablepress_post_type;
        }

        // Exclude unsupported types
        foreach ( $unsupported_types as $type ) {
            unset( $post_types[ $type ] );
        }

        if ( isset( $post_types['elementor_library'] ) ) {
            $post_types['elementor_library']->label = __( 'Elementor Templates', 'wicked-folders' );
        }

        $post_types = apply_filters( 'wicked_folders_settings_post_types', $post_types );

        usort( $post_types, function( $a, $b ) {
            return strcmp( $a->label, $b->label );
        } );

        return $post_types;
    }

    /**
     * 'wicked_folders_settings_fields' filter. When Wicked Folders Pro isn't
     * active, removes post types that require pro from the post type fields
     * and, if upselling is enabled, displays them as locked options.
     */
    public function pro_locked_post_types( $fields, $context ) {
        if ( self::is_pro_active() ) return $fields;

        $pro_post_types = array(
            'attachment',
            'acf',
            'shop_order',
            'shop_coupon',
            'tablepress_table',
            'wpforms',
        );

        $upsell = Wicked_Folders::is_upsell_enabled();

        foreach ( $fields as &$field ) {
            if ( ! in_array( $field['id'], array( 'post_types', 'dynamic_folder_post_types' ) ) ) continue;

            $options = array();
            $locked  = array();

            foreach ( $field['options'] as $option ) {
                if ( in_array( $option['value'], $pro_post_types ) ) {
                    $locked[] = $option;
                } else {
                    $options[] = $option;
                }
            }

            if ( $upsell && 'post_types' == $field['id'] ) {
                $locked[] = array( 'value' => 'wf_plugin', 'label' => __( 'Plugins', 'wicked-folders' ) );
                $locked[] = array( 'value' => 'wf_user', 'label' => __( 'Users', 'wicked-folders' ) );

                if ( class_exists( 'GFAPI' ) ) {
                    $locked[] = array( 'value' => 'wf_gf_entry', 'label' => __( 'Gravity Forms Entries', 'wicked-folders' ) );
                    $locked[] = array( 'value' => 'wf_gf_form', 'label' => __( 'Gravity Forms Forms', 'wicked-folders' ) );
                }

                foreach ( $locked as $option ) {
                    $options[] = array(
                        'value'     => $option['value'],
                        'label'     => $option['label'],
                        'disabled'  => true,
                        'badge'     => __( 'Pro', 'wicked-folders' ),
                        'badgeHint' => __( 'Requires Wicked Folders Pro', 'wicked-folders' ),
                    );
                }
            }

            $field['options'] = $options;
        }

        return $fields;
    }

    /**
     * Returns the settings schema in the format expected by the settings app.
     *
     * @param string $context
     *  'site' or 'network'.
     */
    public function get_schema( $context = 'site' ) {
        $private_keys   = array( 'option', 'default', 'get_callback', 'save_callback', 'sanitize_callback' );
        $fields         = array();

        foreach ( $this->get_fields( $context ) as $field ) {
            $field['storable'] = $this->is_storable( $field );

            foreach ( $private_keys as $key ) {
                unset( $field[ $key ] );
            }

            if ( isset( $field['hide_label'] ) ) {
                $field['hideLabel'] = ( bool ) $field['hide_label'];

                unset( $field['hide_label'] );
            }

            $fields[] = $field;
        }

        return array(
            'tabs'      => $this->get_tabs( $context ),
            'groups'    => $this->get_groups( $context ),
            'fields'    => $fields,
        );
    }

    /**
     * Returns the current values of all storable fields keyed by field ID.
     *
     * @param string $context
     *  'site' or 'network'.
     */
    public function get_values( $context = 'site' ) {
        $values = array();

        foreach ( $this->get_fields( $context ) as $field ) {
            if ( ! $this->is_storable( $field ) ) continue;

            $default = isset( $field['default'] ) ? $field['default'] : null;

            if ( isset( $field['get_callback'] ) && is_callable( $field['get_callback'] ) ) {
                $value = call_user_func( $field['get_callback'], $field );
            } else {
                $value = get_option( $field['option'], $default );
            }

            $values[ $field['id'] ] = $this->cast_value( $field, $value );
        }

        return ( object ) apply_filters( 'wicked_folders_settings_values', $values, $context );
    }

    /**
     * Saves settings.
     *
     * @param array $values
     *  Field values keyed by field ID. Fields that aren't included are left
     *  unchanged.
     * @param string $context
     *  'site' or 'network'.
     */
    public function save_values( $values, $context = 'site' ) {
        $saved = array();

        foreach ( $this->get_fields( $context ) as $field ) {
            if ( ! $this->is_storable( $field ) ) continue;
            if ( ! array_key_exists( $field['id'], $values ) ) continue;

            $value = $this->sanitize_value( $field, $values[ $field['id'] ] );

            if ( isset( $field['save_callback'] ) && is_callable( $field['save_callback'] ) ) {
                call_user_func( $field['save_callback'], $value, $field );
            } else {
                // Booleans are cast to integers because passing false when
                // the option doesn't already exist will result in WordPress
                // not adding the option
                update_option( $field['option'], is_bool( $value ) ? ( int ) $value : $value );
            }

            $saved[ $field['id'] ] = $value;
        }

        do_action( 'wicked_folders_settings_saved', $saved, $context );

        return $saved;
    }

    /**
     * Returns true if a field's value is stored.
     */
    private function is_storable( $field ) {
        return ! empty( $field['option'] ) || ( isset( $field['save_callback'] ) && is_callable( $field['save_callback'] ) );
    }

    /**
     * Casts a stored value to the type expected for the field.
     */
    private function cast_value( $field, $value ) {
        switch ( $field['type'] ) {
            case 'toggle':
                return ( bool ) $value;
            case 'checkboxes':
            case 'colors':
                return is_array( $value ) ? array_values( array_map( 'strval', $value ) ) : array();
            case 'select':
                return null === $value || false === $value ? '' : ( string ) $value;
        }

        return $value;
    }

    /**
     * Sanitizes a field value.
     */
    private function sanitize_value( $field, $value ) {
        if ( isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
            return call_user_func( $field['sanitize_callback'], $value, $field );
        }

        switch ( $field['type'] ) {
            case 'toggle':
                return rest_sanitize_boolean( $value );
            case 'checkboxes':
                $allowed = array();

                foreach ( $field['options'] as $option ) {
                    if ( empty( $option['disabled'] ) ) $allowed[] = ( string ) $option['value'];
                }

                $value = is_array( $value ) ? array_map( 'sanitize_key', $value ) : array();

                return array_values( array_intersect( $value, $allowed ) );
            case 'colors':
                $value = is_array( $value ) ? array_map( 'sanitize_hex_color', $value ) : array();

                return array_values( array_unique( array_filter( $value ) ) );
            case 'select':
                $value = is_scalar( $value ) ? ( string ) $value : '';

                foreach ( $field['options'] as $option ) {
                    if ( ( string ) $option['value'] === $value ) return $value;
                }

                return isset( $field['default'] ) ? $field['default'] : '';
        }

        return is_scalar( $value ) ? sanitize_text_field( $value ) : null;
    }

    /**
     * Returns the data used to render the settings app.
     *
     * @param string $context
     *  'site' or 'network'.
     */
    public function get_app_data( $context = 'site' ) {
        $basename   = 'wicked-folders/wicked-folders.php';
        $upsell     = Wicked_Folders::is_upsell_enabled() && ! self::is_pro_active();
        $pro_url    = 'https://wickedplugins.com/plugins/wicked-folders/?utm_source=core_settings&utm_campaign=wicked_folders&utm_content=';

        $data = array(
            'context'       => $context,
            'namespace'     => 'wicked-folders/v1',
            'pageUrl'       => 'network' == $context ? network_admin_url( 'settings.php?page=' . self::PAGE_SLUG ) : admin_url( 'options-general.php?page=' . self::PAGE_SLUG ),
            'schema'        => $this->get_schema( $context ),
            'values'        => $this->get_values( $context ),
            'header'        => array(
                'title'         => __( 'Wicked Folders', 'wicked-folders' ),
                'description'   => __( 'Organize pages, posts and custom post types into folders.', 'wicked-folders' ),
                'cta'           => $upsell ? array(
                    'label' => __( 'Get Wicked Folders Pro', 'wicked-folders' ),
                    'url'   => $pro_url . 'header_cta',
                ) : false,
            ),
            'plugin'        => array(
                'name'      => __( 'Wicked Folders', 'wicked-folders' ),
                'version'   => Wicked_Folders::plugin_version(),
                'basename'  => $basename,
            ),
            'upsell'        => $upsell ? array(
                'title'         => __( 'Get more with Wicked Folders Pro', 'wicked-folders' ),
                'description'   => __( 'Organize more content and access advanced features.', 'wicked-folders' ),
                'features'      => array(
                    __( 'Media library folders', 'wicked-folders' ),
                    __( 'User folders', 'wicked-folders' ),
                    __( 'Plugin folders', 'wicked-folders' ),
                    __( 'Folders for WooCommerce (products, orders, and discounts)', 'wicked-folders' ),
                    __( 'Folders for Gravity Forms', 'wicked-folders' ),
                    __( 'Folders for WPForms', 'wicked-folders' ),
                    __( 'Folder permissions', 'wicked-folders' ),
                ),
                'button'        => __( 'Upgrade to Pro', 'wicked-folders' ),
                'url'           => $pro_url . 'sidebar_upsell',
            ) : false,
            'links'         => array(
                array(
                    'id'    => 'changelog',
                    'icon'  => 'list',
                    'label' => __( 'Changelog', 'wicked-folders' ),
                    'url'   => 'https://wordpress.org/plugins/wicked-folders/#developers',
                ),
                array(
                    'id'    => 'documentation',
                    'icon'  => 'book',
                    'label' => __( 'Documentation', 'wicked-folders' ),
                    'url'   => 'https://wickedplugins.com/support/wicked-folders/?utm_source=core_settings&utm_campaign=wicked_folders&utm_content=documentation_link',
                ),
                array(
                    'id'    => 'support',
                    'icon'  => 'help',
                    'label' => __( 'Get support', 'wicked-folders' ),
                    'url'   => 'https://wickedplugins.com/contact/',
                ),
            ),
            'review'        => array(
                'title'         => __( 'Enjoying Wicked Folders?', 'wicked-folders' ),
                'description'   => __( 'A quick review on WordPress.org helps others find the plugin.', 'wicked-folders' ),
                'button'        => __( 'Leave a review', 'wicked-folders' ),
                'url'           => 'https://wordpress.org/support/plugin/wicked-folders/reviews/#new-post',
            ),
            'crossSells'    => Wicked_Folders::is_upsell_enabled() ? array(
                'title'         => __( 'More from Wicked Plugins', 'wicked-folders' ),
                'description'   => __( 'Check out our other plugins.', 'wicked-folders' ),
                'items'         => array(
                    array(
                        'icon'          => 'image',
                        'name'          => __( 'Wicked Alt Text AI', 'wicked-folders' ),
                        'description'   => __( 'Automatically generate alt text for your images.', 'wicked-folders' ),
                        'url'           => 'https://wickedplugins.com/plugins/wicked-alt-text-ai/?utm_source=core_settings&utm_campaign=wicked_alt_text_ai&utm_content=wicked_folders_settings',
                    ),
                    array(
                        'icon'          => 'branch',
                        'name'          => __( 'Wicked Block Conditions', 'wicked-folders' ),
                        'description'   => __( 'Show or hide blocks based on rules you set.', 'wicked-folders' ),
                        'url'           => 'https://wickedplugins.com/plugins/wicked-block-conditions/?utm_source=core_settings&utm_campaign=wicked_block_conditions&utm_content=wicked_folders_settings',
                    ),                    
                    array(
                        'icon'          => 'blocks',
                        'name'          => __( 'Wicked Block Builder', 'wicked-folders' ),
                        'description'   => __( 'Create custom blocks without writing code.', 'wicked-folders' ),
                        'url'           => 'https://wickedplugins.com/plugins/wicked-block-builder/?utm_source=core_settings&utm_campaign=wicked_block_builder&utm_content=wicked_folders_settings',
                    ),                    
                ),
            ) : false,
        );

        $data = apply_filters( 'wicked_folders_settings_app_data', $data, $context );

        $data['plugin']['update'] = $this->get_update_status( $data['plugin']['basename'] );

        return $data;
    }

    /**
     * Returns the update status of a plugin based on WordPress's last update
     * check.
     *
     * @param string $basename
     *  The plugin's basename.
     *
     * @return array|false
     *  False if the plugin hasn't been checked for updates.
     */
    private function get_update_status( $basename ) {
        $updates = get_site_transient( 'update_plugins' );

        if ( isset( $updates->response[ $basename ] ) ) {
            return array(
                'available' => true,
                'version'   => isset( $updates->response[ $basename ]->new_version ) ? $updates->response[ $basename ]->new_version : '',
                'url'       => current_user_can( 'update_plugins' ) ? self_admin_url( 'update-core.php' ) : false,
            );
        }

        if ( isset( $updates->checked[ $basename ] ) ) {
            return array(
                'available' => false,
            );
        }

        return false;
    }

    /**
     * WordPress 'admin_enqueue_scripts' action.
     */
    public function admin_enqueue_scripts( $hook_suffix ) {
        $page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : false;

        if ( self::PAGE_SLUG != $page || 0 !== strpos( $hook_suffix, 'settings_page_' ) ) return;

        $this->enqueue_app( is_network_admin() ? 'network' : 'site' );
    }

    /**
     * Enqueues the settings app scripts and styles.
     *
     * @param string $context
     *  'site' or 'network'.
     */
    public function enqueue_app( $context = 'site' ) {
        $version    = Wicked_Folders::plugin_version();
        $dist_dir   = apply_filters( 'wicked_folders_build_directory', dirname( dirname( __FILE__ ) ) . '/dist' );
        $dist_url   = untrailingslashit( apply_filters( 'wicked_folders_build_directory_url', plugin_dir_url( dirname( __FILE__ ) ) . 'dist' ) );
        $asset_file = $dist_dir . '/settings.asset.php';
        $asset      = file_exists( $asset_file ) ? include( $asset_file ) : array(
            'dependencies'  => array( 'wp-a11y', 'wp-api-fetch', 'wp-components', 'wp-dom-ready', 'wp-element', 'wp-hooks', 'wp-i18n' ),
            'version'       => $version,
        );

        wp_enqueue_script( 'wicked-folders-settings', "{$dist_url}/settings.js", $asset['dependencies'], $asset['version'], true );
        wp_enqueue_style( 'wicked-folders-settings', "{$dist_url}/settings.css", array( 'wp-components' ), $asset['version'] );
        wp_style_add_data( 'wicked-folders-settings', 'rtl', 'replace' );

        wp_add_inline_script(
            'wicked-folders-settings',
            'var wickedFoldersSettingsData = ' . wp_json_encode( $this->get_app_data( $context ) ) . ';',
            'before'
        );

        wp_set_script_translations( 'wicked-folders-settings', 'wicked-folders' );

        do_action( 'wicked_folders_enqueue_settings_app', $context );
    }

    /**
     * Renders the settings page.
     */
    public function render_page() {
        ?>
        <div class="wicked-ui-settings-page">
            <hr class="wp-header-end" />
            <div id="wicked-folders-settings">
                <noscript>
                    <p><?php esc_html_e( 'Wicked Folders settings require JavaScript to be enabled.', 'wicked-folders' ); ?></p>
                </noscript>
            </div>
        </div>
        <?php
    }
}
