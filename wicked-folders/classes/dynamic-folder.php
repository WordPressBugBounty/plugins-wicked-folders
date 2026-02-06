<?php

namespace Wicked_Folders;

// Disable direct load
defined( 'ABSPATH' ) || exit;

/**
 * Represents a dynamic folder.
 */
abstract class Dynamic_Folder extends Folder {

    public $movable = false;
    
    public $editable = false;

    public $assignable = false;

    public $deletable = false;
    
    public $is_dynamic = true;
    
    public function __construct( $args = array() ) {
        parent::__construct( $args );
    }

    public function pre_get_posts( $query ) {
        // Default implementation does nothing.
        return $query;
    }

    public static function add_to_collection( $collection ) {
        $folder = new static( array(
            'post_type' => $collection->post_type,
        ) );

        // Used by the pro version to disable certain dynamic folder types for
        // some post types (e.g. disable author dynamic folders for plugin folders).
        $enabled = apply_filters(
            'wicked_folders_enable_dynamic_folder',
            true,
            $folder
        );

        if ( $enabled ) {
            $collection->add( $folder );
        }
    }
}
