<?php

namespace Wicked_Folders;

use Exception;
use DirectoryIterator;
use Wicked_Folders;
use JsonSerializable;

// Disable direct load
defined( 'ABSPATH' ) || exit;

/**
 * Includes all enabled types of folders for a post type including
 * standard folders, dynamic folders, and the "Unassigned" folder.
 */
class All_Folders_Collection extends Folder_Collection {

    public function fetch() {
        if ( ! $this->post_type ) {
            throw new Exception(
                __(
                    'Post type is required to fetch folders.',
                    'wicked-folders'
                )
            );
        }

        $taxonomy 				= Wicked_Folders::get_tax_name( $this->post_type );
        $enable_dynamic_folders = Wicked_Folders::dynamic_folders_enabled_for( $this->post_type );

        // Add dynamic root if enabled
        if ( $enable_dynamic_folders ) {
            $this->add( new Folder( array(
                    'id' 			=> 'dynamic_root',
                    'name' 			=> __( 'Dynamic Folders', 'wicked-folders' ),
                    'parent' 		=> 'root',
                    'post_type' 	=> $this->post_type,
                    'taxonomy' 		=> $taxonomy,
                    'order' 		=> -100,
                    'editable' 		=> false,
                    'deletable' 	=> false,
                    'assignable' 	=> false,
                    'movable' 		=> false,
            ) ) );          
        }

        // Add root folder
        $this->add( new Folder( array(
            'id' 				=> 0,
            'name' 				=> __( 'All Folders', 'wicked-folders' ),
            'parent' 			=> 'root',
            'post_type' 		=> $this->post_type,
            'taxonomy' 			=> $taxonomy,
            'show_item_count' 	=> true,
            'editable' 			=> false,
            'deletable' 		=> false,
            'assignable' 		=> false,
            'movable' 			=> false,
        ) ) );

        parent::fetch();

        // For backwards compatibility
        $this->items = apply_filters( 'wicked_folders_get_folders', $this->items, array(
            'post_type' => $this->post_type,
            'taxonomy'  => $taxonomy,
        ) );

        // Give others a chance to modify the collection. Used by the pro plugin to add
        // the media extension dynamic folder.
        do_action( 'Wicked_Folders\All_Folders_Collection\fetch', $this );
    }
}