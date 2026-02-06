<?php

namespace Wicked_Folders;

// Disable direct load
defined( 'ABSPATH' ) || exit;

/**
 * Holds a collection of dynamic folders.
 */
class Dynamic_Folder_Collection extends Folder_Collection {

    public $lazy = true;

    public static $folder_class = Dynamic_Folder::class;

    public static function add_to_collection( $collection ) {
        // Create root folder
        $parent_folder = new static::$folder_class( array(
            'post_type' => $collection->post_type,
        ) );

        // Used by the pro version to disable certain dynamic folder types for
        // some post types (e.g. disable author dynamic folders for plugin folders).
        $enabled = apply_filters(
            'wicked_folders_enable_dynamic_folder',
            true,
            $parent_folder
        );

        $lazy = apply_filters(
            'wicked_folders_enable_lazy_dynamic_folders',
            get_option( 'wicked_folders_enable_lazy_dynamic_folders', true ),
            static::$folder_class,
            $collection->post_type
        );

        if ( $enabled ) {
            $collection->add( $parent_folder );

            // If lazy dynamic folders are disabled, fetch the folders now
            if ( ! $lazy ) {
                $folders            = new static();
                $folders->lazy      = $lazy;
                $folders->post_type = $collection->post_type;
                $folders->fetch();

                foreach ( $folders as $folder ) {
                    $collection->add( $folder );
                }
            }
        }
    }

}